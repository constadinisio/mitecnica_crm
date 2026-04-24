# Requisitos de modularización de la tenant app

> **Estado** (2026-04-24): este documento fue escrito antes de implementar
> la integración real. La arquitectura final es **híbrida** (webhook push +
> pull diario), no puro pull como proponía este doc. Para la guía operativa
> actualizada ver **[INTEGRATION_SETUP.md](./INTEGRATION_SETUP.md)**.
>
> Este doc se conserva porque las secciones 2.1–2.2 (FeatureGate, módulos
> autocontenidos) siguen siendo válidas del lado tenant. Las secciones sobre
> pull-only, endpoints de license-summary y fail-open/closed fueron
> reemplazadas por el diseño de `INTEGRATION_SETUP.md`.

Documento de referencia — no código. El objetivo es que la tenant app se
modularice ahora con la forma correcta, para que cuando llegue la **Fase 3
(integración CRM ↔ tenant)** el encastre sea leer un endpoint, no refactorizar.

> Este doc vive en el repo del CRM porque define el **contrato que la tenant va
> a consumir**. El código tenant vive en otro repo.

---

## 1. Contrato que el CRM ya expone

Cuando la tenant arranque (o periódicamente), va a preguntarle al CRM por su
estado de licencia. Hoy ya existen estos endpoints y **no van a cambiar de
forma en Fase 3**:

### `GET /api/v1/institutions/:id/license-summary`

Resumen condensado. Ideal para cachear y consultar al bootear.

```json
{
  "institution":  { "id": 1, "status": "active", "technical_status": "optimal" },
  "subscription": { "id": 12, "status": "active", "end_date": "...", "trial_ends_at": null },
  "plan":         { "id": 2, "code": "professional", "name": "Professional" },
  "expiration":   { "end_date": "2026-07-31", "days_remaining": 74 },
  "effective_modules_count": 6,
  "has_overrides": true
}
```

### `GET /api/v1/institutions/:id/modules-effective`

Lista completa de módulos con estado efectivo. Es la fuente de verdad para el
feature gate.

```json
{
  "modules": [
    {
      "module": { "code": "campus", "name": "Campus Virtual", "is_core": false },
      "effective_enabled": true,
      "source": "plan"
    },
    {
      "module": { "code": "analytics" },
      "effective_enabled": true,
      "source": "override"
    }
  ]
}
```

**La tenant debe poder decidir todo en base a `module.code` + `effective_enabled`.**
Cualquier módulo que no esté en esa lista se trata como **deshabilitado**.

---

## 2. Reglas que la tenant debe cumplir

### 2.1 Un único feature gate

Toda consulta sobre "¿está habilitado el módulo X?" pasa por **un solo servicio**:

```
FeatureGate.isEnabled('campus')        → boolean
FeatureGate.requireEnabled('campus')   → throws si está off
FeatureGate.list()                     → string[]  (códigos habilitados)
```

Prohibido: `if (tenant.plan === 'professional')` disperso por la app. La tenant
**no debería conocer los planes**, sólo módulos.

### 2.2 Módulos autocontenidos

Cada módulo vive en su propia carpeta y expone un único punto de entrada:

```
src/modules/
├── campus/
│   ├── index.ts          # export único: registerCampus(app, gate)
│   ├── routes/
│   ├── services/
│   └── views/
├── analytics/
│   └── index.ts
└── ...
```

Reglas duras:
- **Un módulo nunca importa de otro módulo.** Si dos módulos comparten lógica,
  esa lógica vive en `src/shared/`, no en uno de ellos.
- **Registro centralizado**: un archivo `src/modules/registry.ts` mapea
  `code → register()`. Al bootear, se registran sólo los habilitados por el gate.
- **Fallar cerrado**: si el gate dice "no", el código del módulo **no se carga
  en memoria**. Nada de `if (enabled) render()` por dentro del módulo.

### 2.3 El gate tiene una sola fuente de verdad

Hoy puede ser un JSON local de configuración. En Fase 3 va a ser el endpoint
del CRM. **La API interna del gate no cambia**:

```ts
// Fase 2 (ahora, config local)
const gate = new FeatureGate(readJson('./features.json'))

// Fase 3 (después, remoto)
const gate = new FeatureGate(await CrmClient.fetchLicense(tenantId))
```

Todo el resto de la tenant no se entera del cambio.

### 2.4 Estado y refresco

El gate necesita saber responder tres preguntas:

| Pregunta | Respuesta esperada |
|---|---|
| ¿Módulo X está habilitado? | Sí / No, instantáneo |
| ¿Hace cuánto sé esto? | Timestamp de la última sincronización |
| ¿La licencia está vigente? | `expires_at` + `days_remaining` del summary |

Esto permite en Fase 3:
- Cachear el resultado por N minutos (reduce carga al CRM).
- Mostrar un warning "datos desactualizados" si no se pudo refrescar.
- Bloquear el acceso cuando `days_remaining < 0` sin pegarle al CRM.

### 2.5 Comportamiento ante fallo del CRM

Decidir ahora (no cuando llegue Fase 3) qué hace la tenant si el CRM no responde:

- **Política A — Fail open**: usa el último snapshot cacheado. Riesgo: si un
  override se deshabilitó, sigue habilitado hasta el próximo refresh.
- **Política B — Fail closed**: deniega todo hasta reconectar. Riesgo: un
  problema de red deja la institución sin servicio.
- **Política C — Hybrid**: snapshot con TTL corto (ej. 15 min). Si expiró y no
  hay CRM, fail closed.

Recomendación: **C**. Es lo que terminan haciendo la mayoría de los SaaS
multi-tenant.

### 2.6 Identificación de la tenant

La tenant debe saber su propio `institution_id` (o `public_code`, o
`subdomain`) para preguntarle al CRM. Opciones:

- Variable de entorno (`TENANT_INSTITUTION_ID=42`) — simple, estable.
- Deducción por `subdomain` del request — útil si hay multi-tenancy real.

Evitá hardcodearlo en código. Usá env.

---

## 3. Decisiones a cerrar antes de Fase 3

Estas no bloquean la modularización pero **sí bloquean el diseño final del
contrato de integración**. Mejor decidirlas ahora:

- [ ] **¿DB compartida o por tenant?** Si es compartida, la tenant sólo
      necesita `institution_id`. Si es por tenant, Fase 3 también debe
      provisionar el schema/DB al crear la institución.
- [ ] **¿Auth tenant → CRM: API key o HMAC?** API key es más simple; HMAC
      evita que una tenant comprometida filtre el token.
- [ ] **¿Refresh: pull o push?** Pull (la tenant pregunta cada N minutos) es
      más simple. Push (CRM manda webhook al cambiar) es más responsive, pero
      requiere que la tenant exponga un endpoint público al CRM.
- [ ] **¿Qué pasa cuando vence la licencia?** Redirigir a un landing de pago,
      mostrar banner, bloquear sólo ciertos módulos, bloquear todo... decidilo
      ahora y documentalo en el tenant.
- [ ] **¿Modo "grace period"?** Ej. 7 días después del vencimiento en modo
      solo-lectura. Muy común, vale la pena definirlo.

---

## 4. Antipatrones a evitar

- **Chequear plan en lugar de módulo.** `if (plan === 'basic')` se rompe
  cuando un cliente basic tiene un `force_enabled` de algo premium. Siempre
  chequeá módulo.
- **Leer la licencia en caliente en cada request.** Mata performance y ata
  la tenant a la disponibilidad del CRM. Cacheá.
- **Módulos que se importan entre sí.** Hace la desactivación individual
  imposible. Extraé lógica compartida a `shared/`.
- **Ignorar `expiration.days_remaining`.** Si no lo chequeás, una
  institución vencida sigue operando hasta que alguien se da cuenta.
- **Hardcodear la lista de módulos en la tenant.** El catálogo vive en el
  CRM (`modules_catalog`). La tenant sólo sabe qué hacer con cada `code`
  cuando lo recibe.

---

## 5. Checklist "listo para Fase 3"

Cuando puedas marcar todo esto, la integración CRM ↔ tenant es un día de
trabajo, no un refactor:

- [ ] Existe `FeatureGate` con las 3 operaciones (`isEnabled`,
      `requireEnabled`, `list`).
- [ ] Ningún `if (plan === ...)` en el código.
- [ ] Cada módulo vive en `src/modules/<code>/` y exporta un único
      `register(app, gate)`.
- [ ] Hay un `registry.ts` que mapea código → register.
- [ ] Al bootear, sólo se registran módulos habilitados por el gate.
- [ ] El gate lee hoy de un JSON local, pero la interfaz acepta
      intercambiar la fuente sin cambiar consumidores.
- [ ] La tenant sabe su `institution_id` vía env var.
- [ ] Existe política definida para licencia vencida y para CRM caído.
- [ ] Los módulos no se importan entre sí.

Cuando todo esto esté verde, Fase 3 es: escribir un `CrmLicenseClient` que
implementa la misma interfaz que hoy usa el JSON local. Nada más.

---

## 6. Qué no incluir en la tenant (responsabilidad del CRM)

Para que quede claro: la tenant **no** debería implementar nada de esto.
Todo vive del lado del CRM:

- Definición de planes.
- Matriz plan × módulos.
- Overrides por institución.
- Historial de suscripciones y pagos.
- Auditoría de cambios en la licencia.
- UI de administración comercial.

La tenant es pura **consumidora** del estado de licencia.
