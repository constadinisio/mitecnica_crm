# Integración CRM ↔ Tenant app (mitecnica)

Guía operativa para conectar este CRM con la tenant app. La integración se
resuelve con dos canales complementarios:

1. **Push (webhook en tiempo real)**: cuando pasa algo relevante en el CRM
   (crear institución, cambiar plan, etc.), se encola un webhook firmado que
   viaja al tenant. Es el canal primario.
2. **Pull (reconciliación diaria)**: un job del tenant consulta al CRM el
   feed completo de instituciones y reconcilia diferencias. Es la red de
   seguridad para webhooks perdidos.

---

## 1. Arquitectura

```
                          ┌──────────────────────┐
                          │   CRM (este repo)    │
                          │                      │
  operador crea       ┌──→│  institutionService  │
  institución ────────┘   │          │           │
                          │          v           │
                          │  outbox (DB table)   │
                          │          │           │
                          │          v           │
                          │  dispatcher (30s)    │
                          │          │           │
                          └──────────┼───────────┘
                                     │ POST + HMAC firma
                                     v
                          ┌──────────────────────┐
                          │  mitecnica (tenant)  │
                          │                      │
                          │  /webhooks/crm/...   │
                          │  verify signature    │
                          │  idempotencia        │
                          │  upsert tenant       │
                          └──────────────────────┘

  mitecnica daily job ────────→ GET /api/v1/integration/institutions/sync-feed
                                (Authorization: Bearer <api_key>)
```

---

## 2. Variables de entorno

### Del lado CRM (`.env` o `infra/env/api.env`)

| Variable | Descripción | Ejemplo |
|---|---|---|
| `TENANT_WEBHOOK_URL` | URL pública del endpoint receiver de mitecnica | `https://api.mitecnica.com.ar/api/v1/webhooks/crm/tenant-sync` |
| `CRM_WEBHOOK_SECRET` | Secreto compartido para firma HMAC-SHA256. Mínimo 32 chars | `openssl rand -hex 32` |
| `MITECNICA_SYNC_API_KEY` | API key estática que el cron de mitecnica usa para consumir el sync-feed | `openssl rand -hex 32` |
| `WEBHOOK_DISPATCHER_ENABLED` | `true`/`false` — útil para apagar temporalmente en debugging | `true` (default) |
| `WEBHOOK_DISPATCHER_INTERVAL_MS` | Frecuencia del dispatcher | `30000` (default) |
| `WEBHOOK_HTTP_TIMEOUT_MS` | Timeout por intento de entrega | `10000` (default) |
| `WEBHOOK_MAX_ATTEMPTS` | Reintentos antes de marcar `dead` | `8` (default) |

### Del lado mitecnica

| Variable | Descripción |
|---|---|
| `CRM_WEBHOOK_SECRET` | **El mismo** valor que arriba. Sirve para verificar la firma entrante. |
| `CRM_BASE_URL` | URL base del CRM (ej. `https://crm.mitecnica.com.ar/api/v1`) |
| `CRM_API_KEY` | **El mismo** valor que `MITECNICA_SYNC_API_KEY`. Sirve para consumir el sync-feed. |

---

## 3. Generar secretos por primera vez

```bash
# En cualquier máquina con openssl (o Linux/macOS nativo)
echo "CRM_WEBHOOK_SECRET=$(openssl rand -hex 32)"
echo "MITECNICA_SYNC_API_KEY=$(openssl rand -hex 32)"
```

Pegá los valores en ambos `.env` (CRM y tenant) y reiniciá ambos servicios.

**Rotación**: cambiar los valores implica rotar en ambos lados simultáneamente.
Durante la transición puede haber webhooks rechazados y pulls 401 — es esperable.
Si necesitás zero-downtime, agregá soporte para una lista de keys vigentes (no
está implementado hoy).

---

## 4. Eventos emitidos por el CRM

Todos los eventos viajan como `POST` al `TENANT_WEBHOOK_URL` con headers:

```
Content-Type: application/json
X-CRM-Signature: sha256=<hex64>
X-CRM-Webhook-Id: <uuid>
X-CRM-Event: <tipo_evento>
User-Agent: mitecnica-crm-webhook-emitter/1.0
```

Body (ejemplo):

```json
{
  "webhook_id": "11111111-1111-...",
  "event": "tenant.created",
  "payload": { "crm_id": 42, "codigo": "escuela-piloto", ... },
  "emitted_at": "2026-04-24T22:30:00.000Z"
}
```

### Eventos

| Evento | Cuando dispara | Payload |
|---|---|---|
| `tenant.created` | Se crea una institución en el CRM | `{ crm_id, codigo, nombre, subdomain, plan: null, modulos_activos: [] }` |
| `tenant.suspended` | `institution.status` pasa a `suspended` o `expired` | `{ codigo, motivo }` |
| `tenant.reactivated` | `institution.status` vuelve a activo (`trial/active/maintenance`) desde suspendido/archivado | `{ codigo }` |
| `tenant.archived` | `institution.status` pasa a `inactive` | `{ codigo }` |
| `tenant.plan_changed` | Se crea una subscription live o cambia su plan | `{ codigo, plan: "basic" \| null }` |
| `tenant.modules_changed` | Cambia el plan o los overrides de módulos | `{ codigo, modulos_activos: ["code1", ...] }` |

### Mapeo de estados

El CRM maneja 6 estados (`trial/active/maintenance/suspended/expired/inactive`),
el tenant sólo 3 buckets prácticos:

| CRM | Bucket tenant | Qué emite |
|---|---|---|
| `trial`, `active`, `maintenance` | activo | `tenant.reactivated` (si venía de suspendido/archivado) |
| `suspended`, `expired` | suspendido | `tenant.suspended` |
| `inactive` | archivado | `tenant.archived` |

Transiciones dentro de un mismo bucket (ej. `trial→active`) NO emiten evento.

---

## 5. Reintentos y estado de entrega

Tabla `crm_webhook_outbox`:

```
status ∈ { pending | sent | failed | dead }
attempts   = cantidad de intentos hechos
next_attempt_at = cuándo se vuelve a intentar
last_http_status / last_error = último resultado
```

**Backoff** (segundos entre intentos): `1, 5, 30, 300, 1800, 7200, 21600, 86400`.
Total ~32h hasta `dead`.

**Cuándo reintenta**:
- HTTP 5xx, 408, 429
- Network/timeout errors

**Cuándo NO reintenta** (marca `dead` de una):
- HTTP 4xx excepto 408/429 (el request está mal formado, reintentar no arregla).

**Observación**: si ves webhooks en `dead`, revisá `last_error` y `last_http_status`
en la tabla. Puede indicar config rota (secret desalineado, URL incorrecta, etc).

---

## 6. Sync-feed (pull)

### Endpoint

```
GET /api/v1/integration/institutions/sync-feed?limit=50&cursor=<base64>&since=<iso>
Authorization: Bearer <MITECNICA_SYNC_API_KEY>
```

### Paginación

Cursor forward-only estable. La primera llamada va sin cursor; las siguientes
mandan `next_cursor` de la respuesta anterior hasta que venga `null`.

### Response

```json
{
  "status": "success",
  "data": [
    {
      "crm_id": 42,
      "public_code": "INS-2026-0042",
      "codigo": "escuela-piloto",
      "nombre": "Escuela Piloto",
      "subdomain": "escuela-piloto",
      "status_crm": "active",
      "estado_tenant": "active",
      "plan": "basic",
      "modulos_activos": ["core", "campus"],
      "expiration_date": "2026-12-31",
      "updated_at": "2026-04-24T..."
    }
  ],
  "errors": null,
  "meta": { "count": 1, "next_cursor": "base64...", "has_more": true }
}
```

### Filtro incremental

`?since=<iso8601>` devuelve sólo instituciones con `updated_at >= since`.
Útil para pulls rápidos (cada minuto en vez de cada 24h).

---

## 7. Smoke test del CRM

```bash
cd api
npm run migrate       # la tabla crm_webhook_outbox tiene que existir
npm run smoke:integration
```

Valida en orden:
1. `enqueue()` inserta en outbox.
2. `dispatcher.tick()` procesa pending.
3. Un mock HTTP receiver local (puerto 4999) recibe POST con firma correcta.
4. Estado en outbox queda en `sent`.
5. Endpoint sync-feed responde con API key correcta y rechaza con incorrecta.

No depende de mitecnica corriendo. El smoke end-to-end real (con mitecnica
recibiendo) vive en el runbook de onboarding.

---

## 8. Troubleshooting

| Síntoma | Causa probable | Solución |
|---|---|---|
| Webhooks quedan en `pending` acumulando | `TENANT_WEBHOOK_URL` inalcanzable o dispatcher apagado | Chequear `WEBHOOK_DISPATCHER_ENABLED` y conectividad |
| Mitecnica responde 401 `signature_mismatch` | `CRM_WEBHOOK_SECRET` desalineado entre CRM y tenant | Copiar el mismo valor en ambos `.env` y reiniciar |
| Mitecnica responde 401 al sync-feed | `MITECNICA_SYNC_API_KEY` desalineada | Idem |
| Outbox marca `dead` en primer intento | Webhook devolvió 4xx por payload/headers malformados | Revisar `last_error` en outbox |
| Institución creada en CRM pero no aparece en mitecnica | Dispatcher nunca procesó o mitecnica caída | Revisar outbox + logs mitecnica. El reconciliador diario eventualmente la captura. |
