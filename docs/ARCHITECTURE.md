# Arquitectura — Mi Tecnica CRM (Fase 1)

## Vista general

```
                                ┌──────────────────────────┐
                                │      Browser (admin)     │
                                └───────────┬──────────────┘
                                            │ HTTPS / HTTP
                                            ▼
                     ┌──────────────────────────────────────────┐
                     │                 Apache                   │
                     │  - sirve CRM frontend (PHP SSR)          │
                     │  - reverse proxy /api  → Node API (PM2)  │
                     └─────────┬─────────────────────┬──────────┘
                               │ PHP 8+              │ HTTP
                               ▼                     ▼
                 ┌──────────────────────┐   ┌───────────────────────┐
                 │   CRM Frontend       │   │    CRM API            │
                 │   PHP + Tailwind     │   │  Node + Express +     │
                 │   server-side render │   │  Knex (PostgreSQL)    │
                 └────────────┬─────────┘   └──────────┬────────────┘
                              │                        │
                              │ helpers/api_client.php │
                              └────────────────────────┤
                                                       ▼
                                              ┌────────────────┐
                                              │  PostgreSQL    │
                                              └────────────────┘
```

- El **frontend** es PHP server-side render. Todas las vistas se componen desde `crm/app/modules/*` usando un sistema de layouts + componentes (`crm/app/layouts`, `crm/app/components`).
- El **backend** expone `/api/v1` en Express y usa Knex para hablar con PostgreSQL. Arquitectura `controller → service → repository` dentro de `api/src/modules/*`.
- El **frontend** consume la API a través del helper `crm/app/helpers/api_client.php`. Los tokens JWT viven en la sesión PHP del admin.
- No hay SPA. No hay frontend JS framework. El JS vanilla es mínimo (`public/assets/js/*`).

## Estructura del repo (resumen)

```
mi-tecnica-crm/
├── crm/             # Frontend PHP (SSR)
│   ├── app/
│   │   ├── helpers/    # api_client, auth, session, csrf, flash, permissions, format, url
│   │   ├── layouts/    # main.php, auth.php, error.php
│   │   ├── components/ # sidebar, topbar, stat_card, status_badge, table, forms, modal, ...
│   │   ├── modules/    # auth, dashboard, institutions (con partials por tab)
│   │   └── routes/     # web.php — router frontend
│   ├── config/      # app.php, env.php, sidebar.php, permissions.php
│   └── public/      # entry + assets (css compilado + JS vanilla)
├── api/             # Backend Node
│   ├── src/
│   │   ├── config/     # env, db (knex), logger, jwt, cors, google
│   │   ├── middlewares/ # authMiddleware, authorizeRoles, errorHandler, requestLogger, validateRequest, rateLimiter, notFoundHandler
│   │   ├── utils/      # ApiError, apiResponse, encrypt, decrypt, pagination, slug, auditMetadata
│   │   ├── modules/    # auth, dashboard, institutions, audit (controller/service/repository/validator)
│   │   ├── routes/     # index + v1/*
│   │   └── jobs/
│   ├── migrations/  # Knex (PostgreSQL)
│   └── seeds/
├── infra/           # apache/, pm2/, env/
├── docs/            # docs técnicos
└── scripts/         # setup + build scripts
```

## Flujo de autenticación

1. El admin envía `POST /login` (PHP).
2. El helper `auth_login_api()` llama a `POST /api/v1/auth/login` con email/password.
3. El API valida credenciales, emite **access token** (`ACCESS_TOKEN_TTL`, default 15m) y **refresh token** (`REFRESH_TOKEN_TTL`, default 30d). El refresh se persiste en `crm_refresh_tokens` (hash SHA-256, no el token plano).
4. El frontend guarda ambos tokens y el payload `user` en la **sesión PHP** (`SameSite=Lax`, `HttpOnly`).
5. Cada request del PHP al API añade `Authorization: Bearer <accessToken>`.
6. Ante un `401`, el helper `api_try_refresh_token()` intenta rotar el refresh. Si falla, destruye la sesión y redirige a `/login`.
7. El logout llama `/api/v1/auth/logout` (que revoca el refresh token en DB) y limpia la sesión PHP.

**Google OAuth** está desacoplado: si faltan credenciales (`GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET`), el sistema funciona igual. El endpoint `/api/v1/auth/google` responde `enabled: false` y el botón no se muestra.

## Módulo Instituciones

- El API expone CRUD completo + `PATCH /:id/status` con auditoría automática.
- Los filtros soportan `search`, `status`, `technical_status`, `plan`, rango de expiración. Ordenamiento seguro limitado a columnas permitidas por el repository.
- El frontend renderiza:
  - `/institutions` — tabla con filtros, paginación, badges, acciones por fila
  - `/institutions/new` — formulario 2 columnas + panel lateral (comercial/técnico)
  - `/institutions/:id` — detalle con tabs **General · Comercial · Dominios · Auditoría**
  - `/institutions/:id/edit` — mismo form con datos precargados
  - `POST /institutions/:id/status` — cambio de estado con auditoría

## Auditoría básica

Los eventos registrados en Fase 1 (tabla `audit_logs`):

| Evento | Action key | Entity |
|--------|------------|--------|
| Login exitoso | `auth.login` | `crm_users` |
| Login fallido | `auth.login.failed` | `crm_users` |
| Login Google | `auth.login.google` | `crm_users` |
| Logout | `auth.logout` | `crm_users` |
| Forgot password request | `auth.forgot_password` | `crm_users` |
| Alta de institución | `institution.created` | `institutions` |
| Edición de institución | `institution.updated` | `institutions` |
| Cambio de estado | `institution.status_changed` | `institutions` |

Cada evento incluye `actor_user_id`, IP, user-agent, `before_data`/`after_data` (para cambios), y `description` legible.

## Permisos / RBAC

El API usa `authorizeRoles('...')`. El frontend espeja la decisión en `crm/config/permissions.php` + helper `can()`.

| Rol | Dashboard | Instituciones | Audit | Crear inst. | Cambiar estado |
|-----|:---------:|:------------:|:-----:|:-----------:|:--------------:|
| superadmin | ✔ | ✔ | ✔ | ✔ | ✔ |
| support    | ✔ | ✔ (R/U) | ✔ |   | ✔ |
| commercial | ✔ | ✔ | — | ✔ | ✔ |
| finance    | ✔ | ✔ (R) | — |   |   |
| developer  | ✔ | ✔ (R) | ✔ |   |   |

## Decisiones clave

- **Tokens**: JWT firmados con issuers/audiences distintos para access vs refresh para evitar confusion attacks. Refresh almacenado sólo como hash en DB, rotación al usar.
- **Estructura del código**: controller delgado, service con la lógica, repository con acceso a datos (sin SQL crudo innecesario, todo a través de Knex).
- **Rate limiting**: global + específico para `/auth/login` (más estricto, no cuenta los exitosos).
- **CSRF**: el frontend PHP emite tokens por sesión y los valida en cada `POST`.
- **CSS**: Tailwind 3 es la fuente canónica (configurada en `tailwind.config.js`). El repo trae un `output.css` precompilado listo para bootear sin build; `scripts/build-tailwind.{sh,bat}` regenera el archivo correctamente optimizado.
- **Sin DevOps visible**: el dashboard está diseñado a propósito sin CPU, memoria, clusters, pipelines o logs en vivo. El "estado técnico" es info de negocio.
- **Plans/Módulos/Pagos**: fuera de alcance en Fase 1. Implementados en **Fase 2A**.

## Fase 2A — Núcleo comercial

Agregada sobre la base de Fase 1 sin romper nada. Aporta:

### Módulos nuevos (backend + frontend)

| Módulo | Backend | Frontend |
|--------|---------|----------|
| `plans` | CRUD + summary + status change | `/plans`, `/plans/new`, `/plans/:id`, `/plans/:id/edit` |
| `modulesCatalog` | CRUD + status change | `/modules`, `/modules/new`, `/modules/:id`, `/modules/:id/edit` |
| `planModules` | `GET /matrix`, `GET /plans/:id/modules`, `PUT /plans/:id/modules` (reemplaza set) con auditoría de entitlements | `/plan-matrix` (filas = módulos, columnas = planes, toggle por celda, guardar por plan) |
| `subscriptions` | CRUD + status + summary + constraint única parcial en DB (una viva por institución) | `/subscriptions`, `/subscriptions/new`, `/subscriptions/:id` (con lista de pagos), `/subscriptions/:id/edit` |
| `payments` | CRUD + status + summary con filtros de rango | `/payments`, `/payments/new`, `/payments/:id`, `/payments/:id/edit` |

### Moneda
Precios y pagos en **ARS (pesos argentinos)** por defecto. `USD` queda soportado como opción en los selects de moneda, pero los seeds y defaults usan ARS (producto argentino).

### Regla de negocio "una suscripción viva por institución"
- A nivel DB: índice único parcial (`WHERE status IN ('trial','active')`) sobre `subscriptions(institution_id)`.
- A nivel service: `ensureNoLiveConflict()` en `subscriptionService` valida antes de crear / cambiar estado y devuelve 409 con `existing_subscription_id`.
- Permite historial: canceladas/expiradas no bloquean nuevas altas.

### Entitlements y matriz
La matriz Plan × Módulo se guarda **por plan entero** (endpoint `PUT /plans/:id/modules`), hace el reemplazo en transacción y audita como `plan.modules_updated` sólo si cambió el set. El frontend replica esa UX: cada columna de plan tiene su botón "Guardar" y sólo commit esa columna.

### Dashboard (extensión)
Se agregaron 4 KPI cards comerciales (Planes activos / Subs vivas / Cobrado últimos 30d en ARS / Pagos pendientes) y 2 listas (Renovaciones próximas / Pagos recientes). El dashboard original no se reescribió — sólo se extendió.

## RBAC extendida (Fase 2A)

| Rol | Plans | Modules | Matriz | Subs | Pagos |
|-----|:----:|:-------:|:------:|:----:|:-----:|
| superadmin | ✔ | ✔ | ✔ | ✔ | ✔ |
| commercial | R/W + status | R/W + status | R/W | R/W + status | R/W + status |
| finance | R | R | R | R | R/W + status |
| support | R | R | R | R + status | R |
| developer | R | R | R | — | — |

## Fase 2B — Control fino por institución

Extiende Fase 2A con la capa que faltaba entre "plan genérico" e "institución concreta": **overrides**.

### Regla de composición (crítica)

El estado efectivo de un módulo para una institución es una función pura de **plan activo** + **override**:

| Plan incluye | Override | Estado final |
|:---:|:---:|:---:|
| sí | — | **enabled** (source: `plan`) |
| no | — | **disabled** (source: `plan`) |
| sí | `force_enabled` | enabled (source: `plan+override`) |
| sí | `force_disabled` | **disabled** (source: `override`) |
| no | `force_enabled` | **enabled** (source: `override`) |
| no | `force_disabled` | disabled (source: `override`) |

El plan activo se determina vía la única `subscription` viva (status ∈ {`trial`,`active`}). Sin suscripción viva: todos los módulos quedan deshabilitados por plan (los overrides siguen aplicando si existen).

### Módulo nuevo (backend)

- `institutionModules` (`api/src/modules/institutionModules`)
  - `GET /api/v1/institutions/:id/modules-effective` — lista completa con `plan_included`, `override_mode`, `effective_enabled`, `source` + summary.
  - `PUT /api/v1/institutions/:id/modules-overrides` — reemplaza el set completo de overrides (transacción). Audita si cambió.
  - `GET /api/v1/institutions/:id/license-summary` — plan + suscripción + vencimiento + módulos activos + últimos pagos.

### Nuevas vistas (frontend)

- `institutions/:id` — tabs reemplazados: **General · Licencia · Módulos · Suscripción · Pagos · Dominios · Auditoría**.
  - Tab "Módulos": tabla de estado efectivo con toggle por fila (`Sin override` / `Forzar habilitado` / `Forzar deshabilitado`) y guardado batch (`POST /institutions/:id/modules-overrides`).
- `/audit` y `/audit/:id` — lista paginada con filtros (usuario, entidad, entity_id, rango de fechas, búsqueda) y detalle con `before_data` / `after_data` / IP / User-Agent.
- Dashboard: dos tarjetas nuevas — "Instituciones por plan" (buckets con barra) y "MRR estimado" (mensualización simple por frecuencia).

### Auditoría agregada

- `institution.modules_overrides_updated` → entity `institutions`, con diff completo de overrides antes/después.

### RBAC Fase 2B

| Rol | Ver licencia | Modificar overrides | Ver auditoría |
|-----|:---:|:---:|:---:|
| superadmin | ✔ | ✔ | ✔ |
| commercial | ✔ | ✔ | — |
| support | ✔ | — | ✔ |
| finance | ✔ | — | — |
| developer | ✔ | — | ✔ |
