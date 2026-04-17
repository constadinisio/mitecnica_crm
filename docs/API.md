# API Reference — Mi Tecnica CRM

Base URL: `/api/v1`

## Formato de respuesta

Todas las respuestas usan el mismo envelope:

```json
{
  "status": "success",
  "data": { ... },
  "errors": null,
  "meta": { }
}
```

En caso de error:

```json
{
  "status": "error",
  "data": null,
  "errors": [{ "code": "VALIDATION", "message": "...", "details": [...] }],
  "meta": {}
}
```

Códigos HTTP estándar. Campos comunes: `meta.pagination` para listados.

---

## Health

### `GET /health`
Respuesta:
```json
{ "status":"success", "data":{ "status":"ok","name":"mi-tecnica-crm-api","env":"development","time":"..." } }
```

---

## Auth

### `POST /api/v1/auth/login`
Body:
```json
{ "email": "admin@mitecnica.local", "password": "Admin123!" }
```
Response:
```json
{
  "status":"success",
  "data":{
    "user": { "id":1, "name":"Mi Tecnica Admin", "email":"admin@mitecnica.local", "role": { "id":1, "key":"superadmin", "name":"Superadmin" } },
    "accessToken":"eyJhbGciOi...",
    "refreshToken":"eyJhbGciOi...",
    "accessTtl":"15m",
    "refreshTtl":"30d"
  }
}
```

### `POST /api/v1/auth/refresh`
Body: `{ "refresh_token": "..." }`
Response: similar a login — emite un nuevo access + rota el refresh.

### `POST /api/v1/auth/logout`
Body (opcional): `{ "refresh_token": "..." }`
Revoca el refresh token en DB y audita el logout.

### `GET /api/v1/auth/me`
Header: `Authorization: Bearer <accessToken>`
Response:
```json
{ "status":"success", "data": { "user": { ... }, "google_enabled": false } }
```

### `POST /api/v1/auth/forgot-password`
Body: `{ "email": "foo@bar.com" }`
Siempre responde `200` (no filtra la existencia del email). En Fase 1 no envía email; sólo registra auditoría.

### `POST /api/v1/auth/reset-password`
Body: `{ "token": "...", "new_password": "..." }`
En Fase 1 devuelve `501 NOT_IMPLEMENTED` (estructura preparada, email provider fuera de alcance).

### `GET /api/v1/auth/google`
Response: `{ data: { "enabled": true/false, "url": "..." } }`. Si `enabled=false`, el frontend oculta el botón.

### `GET /api/v1/auth/google/callback?code=...`
Callback del flujo OAuth de Google. Requiere credenciales configuradas.

---

## Dashboard

### `GET /api/v1/dashboard/summary`
Header: `Authorization: Bearer <accessToken>`
Response:
```json
{
  "status":"success",
  "data": {
    "counts": {
      "total": 5,
      "by_status": { "trial":1,"active":2,"maintenance":0,"suspended":1,"expired":1,"inactive":0 },
      "by_technical_status": { "pending":1,"optimal":2,"updating":1,"offline":1 }
    },
    "upcoming_expirations": [ /* próximos 30 días */ ],
    "recent_institutions":  [ /* últimas creadas */ ],
    "recent_activity":      [ /* últimos 10 audit logs */ ]
  }
}
```

---

## Institutions

### `GET /api/v1/institutions`
Query params:

| Param | Tipo | Descripción |
|-------|------|-------------|
| `page` | int | 1 por defecto |
| `limit` | int | 1–100, default 20 |
| `search` | string | Busca en nombre, public_code, subdomain, contact_email |
| `status` | csv | `trial,active,maintenance,suspended,expired,inactive` |
| `technical_status` | csv | `pending,optimal,updating,offline` |
| `plan` | string | Match exacto contra `current_plan_name` |
| `sort` | enum | `id,name,status,technical_status,expiration_date,created_at,updated_at,last_activity_at` |
| `order` | enum | `asc,desc` (default `desc`) |
| `expiration_from`,`expiration_to` | ISO date | |

Response:
```json
{
  "status":"success",
  "data": [ /* institutions */ ],
  "meta": { "pagination": { "page":1, "limit":20, "total":5, "pages":1, "hasNext":false, "hasPrev":false } }
}
```

### `GET /api/v1/institutions/:id`
Response:
```json
{ "status":"success", "data": { "institution": { ... }, "audit": [ /* últimos 20 eventos */ ] } }
```

### `POST /api/v1/institutions`
Body mínimo:
```json
{
  "name": "Escuela X",
  "contact_email": "contacto@x.edu.ar",
  "subdomain": "escuela-x"
}
```
Opcionales: `slug`, `contact_phone`, `address`, `responsible_name`, `responsible_email`, `notes_internal`, `current_plan_name`, `expiration_date`, `status`, `technical_status`.

### `PUT /api/v1/institutions/:id`
Cualquier subset de los campos anteriores.

### `PATCH /api/v1/institutions/:id/status`
Body:
```json
{ "status": "suspended", "reason": "mora 30 días" }
```

---

## Audit Logs

### `GET /api/v1/audit-logs`
Query params: `page`, `limit`, `action`, `entity`, `entity_id`, `actor_user_id`, `search`, `from`, `to`, `sort`, `order`.

### `GET /api/v1/audit-logs/:id`
Detalle completo con `actor_name`, `actor_email`, `before_data`, `after_data`.

---

## Plans (Fase 2A)

Moneda por defecto **ARS**. Todos los endpoints requieren autenticación.

### `GET /api/v1/plans`
Query params: `page`, `limit`, `search`, `status` (csv), `billing_frequency`, `is_custom`, `sort`, `order`.

### `GET /api/v1/plans/:id`
### `POST /api/v1/plans`
Body: `{ code, name, description?, billing_frequency?, price_amount?, currency_code?, status?, is_custom? }`
### `PUT /api/v1/plans/:id`
### `PATCH /api/v1/plans/:id/status` — Body: `{ status: "active|inactive|archived" }`
### `GET /api/v1/plans/summary`
Retorna `{ by_status: {active,inactive,archived}, total }`.

---

## Modules Catalog (Fase 2A)

### `GET /api/v1/modules-catalog`
Filtros: `status`, `category`, `is_core`, `search`.
### `GET /api/v1/modules-catalog/:id`
### `POST /api/v1/modules-catalog`
Body: `{ code, name, description?, category?, status?, is_core? }`
### `PUT /api/v1/modules-catalog/:id`
### `PATCH /api/v1/modules-catalog/:id/status` — Body: `{ status: "active|inactive" }`

---

## Plan × Modules (Fase 2A)

### `GET /api/v1/plan-modules/matrix`
Response:
```json
{
  "status":"success",
  "data": {
    "plans":   [ {id, code, name, price_amount, currency_code, billing_frequency, status} ],
    "modules": [ {id, code, name, category, is_core, status} ],
    "relations": { "<plan_id>": { "<module_id>": true } }
  }
}
```

### `GET /api/v1/plans/:id/modules`
Response: `{ plan: {id,code,name}, module_ids: number[] }`

### `PUT /api/v1/plans/:id/modules`
Reemplaza completamente el set de módulos del plan.
Body: `{ module_ids: number[] }`
Audita como `plan.modules_updated` si hubo cambios.

---

## Subscriptions (Fase 2A)

### `GET /api/v1/subscriptions`
Filtros: `institution_id`, `plan_id`, `status`, `renewal_mode`, `search`.
Cada fila trae `institution_name`, `institution_code`, `plan_name`, `plan_price_amount`, `plan_currency_code`.

### `GET /api/v1/subscriptions/:id`
### `POST /api/v1/subscriptions`
Body: `{ institution_id, plan_id, status?, start_date, end_date?, trial_ends_at?, renewal_mode?, billing_notes? }`
Valida que no exista otra suscripción **viva** (`trial`/`active`) en la misma institución.

### `PUT /api/v1/subscriptions/:id`
### `PATCH /api/v1/subscriptions/:id/status` — Body: `{ status, reason? }`

### `GET /api/v1/subscriptions/summary`
Retorna `{ counts: {total, by_status, live}, upcoming_renewals[] }` (30 días).

---

## Payments (Fase 2A)

### `GET /api/v1/payments`
Filtros: `institution_id`, `subscription_id`, `status`, `payment_method`, `from`, `to`, `search`.

### `GET /api/v1/payments/:id`
### `POST /api/v1/payments`
Body: `{ institution_id, subscription_id?, amount, currency_code?, payment_date?, status?, payment_method?, reference_code?, notes? }`
Valida que la suscripción (si viene) pertenezca a la misma institución.

### `PUT /api/v1/payments/:id`
### `PATCH /api/v1/payments/:id/status` — Body: `{ status, reason? }`

### `GET /api/v1/payments/summary`
Retorna:
```json
{
  "counts": { "total": 9, "by_status": {"pending":1,"approved":6,...} },
  "totals_all":       [{"status":"approved","currency_code":"ARS","amount":915000}, ...],
  "totals_last_30d":  [{"status":"approved","currency_code":"ARS","amount":65000}, ...],
  "recent":           [ /* últimos 6 pagos con join a institución/plan */ ]
}
```

---

## Institution licence & module overrides (Fase 2B)

Capa que combina plan activo + overrides manuales por institución. El plan activo se resuelve por la única suscripción **viva** (`trial`/`active`); si no la hay, el plan es `null` y todos los módulos sin override quedan deshabilitados.

### `GET /api/v1/institutions/:id/modules-effective`

Matriz completa de módulos para la institución.

Response:

```json
{
  "status": "success",
  "data": {
    "institution": { "id": 1, "name": "...", "public_code": "INS-..." },
    "subscription": { "id": 12, "status": "active", "start_date": "...", "end_date": "...", "trial_ends_at": null, "renewal_mode": "manual" },
    "plan":         { "id": 2, "code": "professional", "name": "Professional", "billing_frequency": "monthly", "price_amount": 65000, "currency_code": "ARS" },
    "modules": [
      {
        "module": { "id": 3, "code": "campus", "name": "Campus Virtual", "category": "academic", "is_core": false, "status": "active" },
        "plan_included":     true,
        "override_mode":     null,
        "override_notes":    null,
        "effective_enabled": true,
        "source":            "plan"
      },
      {
        "module": { "id": 8, "code": "analytics", ... },
        "plan_included":     false,
        "override_mode":     "force_enabled",
        "override_notes":    "Activado manualmente por soporte",
        "effective_enabled": true,
        "source":            "override"
      }
    ],
    "summary": { "total": 8, "plan_included": 5, "override_count": 2, "effective_enabled": 6 }
  }
}
```

Roles: `support`, `commercial`, `finance`, `developer`, `superadmin`.

### `PUT /api/v1/institutions/:id/modules-overrides`

Reemplaza el set completo de overrides. Lo que no viene en el body, se elimina. Transacción completa + auditoría (`institution.modules_overrides_updated`) si hubo cambio.

Body:

```json
{
  "overrides": [
    { "module_id": 8, "override_mode": "force_enabled",  "notes": "cortesía comercial" },
    { "module_id": 3, "override_mode": "force_disabled", "notes": null }
  ]
}
```

`override_mode` ∈ `force_enabled` | `force_disabled`. Responde con la vista actualizada de `modules-effective`.

Roles: `commercial`, `superadmin`.

### `GET /api/v1/institutions/:id/license-summary`

Resumen condensado de licencia (plan + suscripción + vencimiento + módulos activos + últimos 5 pagos).

Response abreviada:

```json
{
  "status": "success",
  "data": {
    "institution": { "id": 1, "name": "...", "public_code": "...", "status": "active", "technical_status": "optimal" },
    "subscription": { "id": 12, "status": "active", "start_date": "...", "end_date": "...", "trial_ends_at": null, "renewal_mode": "manual" },
    "plan": { "id": 2, "code": "professional", "name": "Professional", "billing_frequency": "monthly", "price_amount": 65000, "currency_code": "ARS" },
    "expiration":   { "end_date": "2026-07-31", "days_remaining": 74, "trial_ends_at": null },
    "effective_modules_count": 6,
    "total_modules_count":     8,
    "has_overrides":           true,
    "recent_payments":         [ /* últimos 5 */ ]
  }
}
```

Roles: `support`, `commercial`, `finance`, `developer`, `superadmin`.

---

## Dashboard (extendido Fase 2B)

`GET /api/v1/dashboard/summary` ahora incluye la sección `commercial` además del shape histórico:

```json
{
  "commercial": {
    "institutions_by_plan": [
      { "plan_id": 2, "plan_code": "professional", "plan_name": "Professional",
        "billing_frequency": "monthly", "price_amount": 65000, "currency_code": "ARS",
        "institutions": 4 }
    ],
    "mrr_estimate": [
      { "currency_code": "ARS", "amount": 260000 }
    ],
    "recent_payments":  [ /* últimos 6 */ ],
    "payment_totals":   [ { "status": "approved", "currency_code": "ARS", "amount": 915000 } ],
    "subscriptions": {
      "by_status": { "trial": 1, "active": 4, "suspended": 0, "expired": 0, "canceled": 0 },
      "upcoming_expirations": [ /* próximos 30 días */ ]
    }
  }
}
```

MRR se estima normalizando `price_amount` por frecuencia: `monthly × 1`, `quarterly ÷ 3`, `yearly ÷ 12`, `custom` tratado como mensual.

---

## Errores comunes

| HTTP | Code | Significado |
|------|------|-------------|
| 400 | `BAD_REQUEST` | Parámetros inválidos |
| 401 | `UNAUTHORIZED` | Token faltante/expirado/inválido |
| 403 | `FORBIDDEN` | Rol sin permiso |
| 404 | `NOT_FOUND` | Recurso inexistente |
| 409 | `CONFLICT` | Duplicado (ej. subdomain en uso) |
| 422 | `VALIDATION` | Fallaron reglas de `express-validator` |
| 429 | `RATE_LIMIT` / `RATE_LIMIT_LOGIN` | Rate limit activo |
| 500 | `INTERNAL` | Error inesperado |
| 501 | `NOT_IMPLEMENTED` | Flujo planificado pero no aún disponible |

El campo `errors[0].details` puede contener un array de `{ field, message, location, value }` en respuestas `422`.
