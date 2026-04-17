# Esquema de base de datos — Mi Tecnica CRM

Motor: **PostgreSQL 14+**. Migraciones gestionadas con **Knex**. Extensiones opcionales: `pg_trgm` (usado por el índice de búsqueda por nombre de institución; su ausencia no rompe las migraciones).

## Tablas

### `crm_roles`
| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | `serial PK` | |
| `key` | `varchar(32) UNIQUE NOT NULL` | `superadmin`, `support`, `commercial`, `finance`, `developer` |
| `name` | `varchar(80) NOT NULL` | |
| `description` | `varchar(255)` | |
| `created_at` / `updated_at` | `timestamptz` | |

### `crm_users`
| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | `serial PK` | |
| `name` | `varchar(120) NOT NULL` | |
| `email` | `varchar(160) UNIQUE NOT NULL` | |
| `password_hash` | `varchar(255)` | bcrypt; nullable si el usuario sólo usa Google |
| `role_id` | `int FK crm_roles(id) NOT NULL` | `RESTRICT` al borrar |
| `status` | `enum crm_user_status` | `active` / `inactive` |
| `avatar_url` | `varchar(500)` | |
| `google_id` | `varchar(120) UNIQUE` | vinculado al iniciar con Google |
| `last_login_at` | `timestamptz` | |
| `created_at` / `updated_at` | `timestamptz` | |

Índices: `role_id`, `status`.

### `crm_refresh_tokens`
| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | `serial PK` | |
| `user_id` | `int FK crm_users(id)` | `CASCADE` al borrar |
| `token_hash` | `varchar(128) UNIQUE NOT NULL` | SHA-256 del token plano |
| `expires_at` | `timestamptz NOT NULL` | |
| `revoked_at` | `timestamptz` | |
| `user_agent`, `ip` | `varchar` | para trazabilidad |
| `created_at` | `timestamptz` | |

Índices: `user_id`, `expires_at`.

### `institutions`
Tabla principal del negocio.

| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | `serial PK` | |
| `public_code` | `varchar(24) UNIQUE NOT NULL` | Formato `INS-YYYY-NNNN` (auto-generado) |
| `name` | `varchar(180) NOT NULL` | |
| `slug` | `varchar(180) UNIQUE NOT NULL` | slug normalizado del nombre |
| `subdomain` | `varchar(120) UNIQUE NOT NULL` | usado por la tenant app |
| `status` | `enum institution_status` | `trial,active,maintenance,suspended,expired,inactive` |
| `contact_email` | `varchar(160) NOT NULL` | |
| `contact_phone` | `varchar(40)` | |
| `address` | `varchar(255)` | |
| `responsible_name` | `varchar(160)` | |
| `responsible_email` | `varchar(160)` | |
| `notes_internal` | `text` | visible solo al CRM |
| `current_plan_name` | `varchar(120)` | nombre del plan vigente (texto — el módulo de planes llega en Fase 2) |
| `expiration_date` | `date` | |
| `technical_status` | `enum institution_technical_status` | `pending,optimal,updating,offline` (dato de negocio, NO panel DevOps) |
| `last_activity_at` | `timestamptz` | |
| `created_at` / `updated_at` | `timestamptz` | |

Índices: `status`, `technical_status`, `expiration_date`, `created_at`. GIN `pg_trgm` sobre `LOWER(name)` para búsqueda (opcional).

### `audit_logs`
| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | `bigserial PK` | |
| `actor_user_id` | `int FK crm_users(id)` | `SET NULL` al borrar al usuario |
| `action` | `varchar(80) NOT NULL` | ej. `institution.created`, `auth.login.failed` |
| `entity` | `varchar(80) NOT NULL` | ej. `institutions`, `crm_users` |
| `entity_id` | `varchar(64)` | id del recurso afectado |
| `description` | `varchar(500)` | mensaje legible |
| `before_data` | `jsonb` | snapshot previo (nullable) |
| `after_data` | `jsonb` | snapshot posterior (nullable) |
| `ip` | `varchar(64)` | |
| `user_agent` | `varchar(255)` | |
| `created_at` | `timestamptz` | |

Índices: `actor_user_id`, `action`, `entity`, `created_at`, compuesto `(entity, entity_id)`.

---

## Tablas Fase 2A (núcleo comercial)

### `plans`
Plan comercial del producto. Moneda por defecto **ARS** (Argentina).

| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | `serial PK` | |
| `code` | `varchar(32) UNIQUE NOT NULL` | ej. `basic`, `professional`, `elite`, `enterprise` |
| `name` | `varchar(120) NOT NULL` | |
| `description` | `text` | |
| `billing_frequency` | `enum plan_billing_frequency` | `monthly,quarterly,yearly,custom` |
| `price_amount` | `numeric(12,2) NOT NULL` | |
| `currency_code` | `varchar(10) NOT NULL DEFAULT 'ARS'` | ISO code |
| `status` | `enum plan_status` | `active,inactive,archived` |
| `is_custom` | `boolean NOT NULL DEFAULT false` | planes a medida |
| `created_at` / `updated_at` | `timestamptz` | |

Índices: `status`, `billing_frequency`.

### `modules_catalog`
Catálogo de módulos del producto.

| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | `serial PK` | |
| `code` | `varchar(48) UNIQUE NOT NULL` | ej. `attendance`, `grades`, `analytics` |
| `name` | `varchar(120) NOT NULL` | |
| `description` | `text` | |
| `category` | `enum module_category` | `academic,communication,administration,technical,analytics,other` (nullable) |
| `status` | `enum module_status` | `active,inactive` |
| `is_core` | `boolean NOT NULL DEFAULT false` | módulo "core" |
| `created_at` / `updated_at` | `timestamptz` | |

### `plan_modules`
Tabla pivote: qué módulos incluye cada plan.

| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | `serial PK` | |
| `plan_id` | `int FK plans(id)` | `CASCADE` al borrar plan |
| `module_id` | `int FK modules_catalog(id)` | `CASCADE` al borrar módulo |
| `included` | `boolean NOT NULL DEFAULT true` | toggle |
| `created_at` / `updated_at` | `timestamptz` | |

Unique `(plan_id, module_id)`. Índices: `plan_id`, `module_id`.

### `subscriptions`
Suscripción de una institución a un plan. Puede haber historial; **sólo una vigente** por institución.

| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | `serial PK` | |
| `institution_id` | `int FK institutions(id)` | `CASCADE` |
| `plan_id` | `int FK plans(id)` | `RESTRICT` |
| `status` | `enum subscription_status` | `trial,active,suspended,expired,canceled` |
| `start_date` | `date NOT NULL` | |
| `end_date` | `date` | fin previsto |
| `trial_ends_at` | `timestamptz` | fin de trial |
| `renewal_mode` | `enum subscription_renewal_mode` | `manual,automatic` |
| `billing_notes` | `text` | |
| `created_at` / `updated_at` | `timestamptz` | |

**Índice único parcial**: `subscriptions_one_live_per_institution` sobre `institution_id` donde `status IN ('trial','active')`. Esto garantiza que sólo pueda existir UNA suscripción viva por institución a nivel DB, permitiendo historial de canceladas/expiradas.

### `payments`
Pagos manuales asociados a institución (y opcionalmente a una suscripción).

| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | `serial PK` | |
| `institution_id` | `int FK institutions(id)` | `CASCADE` |
| `subscription_id` | `int FK subscriptions(id)` | `SET NULL` (nullable) |
| `amount` | `numeric(12,2) NOT NULL` | |
| `currency_code` | `varchar(10) NOT NULL DEFAULT 'ARS'` | |
| `payment_date` | `timestamptz NOT NULL` | |
| `status` | `enum payment_status` | `pending,approved,rejected,expired,canceled` |
| `payment_method` | `varchar(80)` | ej. `Transferencia`, `Mercado Pago` |
| `reference_code` | `varchar(120)` | referencia externa |
| `notes` | `text` | |
| `created_by_user_id` | `int FK crm_users(id)` | `SET NULL` |
| `created_at` / `updated_at` | `timestamptz` | |

Índices: `status`, `payment_date`, `institution_id`, `subscription_id`.

---

## Relaciones

```
crm_roles (1) ──< (n) crm_users (1) ──< (n) crm_refresh_tokens
                               │
                               └──< (n) audit_logs.actor_user_id

institutions (1) ──< (n) subscriptions (n) >── (1) plans
        │                      │
        │                      └──< (n) payments
        └──< (n) payments (FK directa opcional)

plans (n) >──< (n) modules_catalog   via plan_modules
```

## Seeds Fase 2A

- **04_plans.js** — 4 planes demo en **ARS**: basic (25.000), professional (65.000), elite (120.000), enterprise (1.800.000/año)
- **05_modules_catalog.js** — 8 módulos (attendance, grades, campus, report_cards, families, doe, inventory, analytics)
- **06_plan_modules.js** — matriz coherente (basic 3 / professional 5 / elite 7 / enterprise 8)
- **07_subscriptions.js** — 5 suscripciones asociadas a las instituciones de Fase 1 (con estados variados)
- **08_payments.js** — 9 pagos demo en ARS con distintos estados y métodos locales (Transferencia, Mercado Pago, Link de pago, Tarjeta)

---

## Seeds

1. `01_crm_roles.js` — carga los 5 roles por defecto (idempotente).
2. `02_crm_superadmin.js` — crea `admin@mitecnica.local` con hash bcrypt de `Admin123!` (idempotente).
3. `03_institutions_demo.js` — crea 5 instituciones con estados variados para que el dashboard y el listado tengan contenido vivo.

Ejecución:

```bash
cd api
npx knex migrate:latest
npx knex seed:run
```

## Criterios

- **Idempotencia**: todas las seeds verifican existencia antes de insertar/actualizar para que correrlas varias veces no rompa datos.
- **Enums nativos** de PostgreSQL (`CREATE TYPE`). La bajada (`down`) los elimina explícitamente.
- **Timestamps**: siempre `timestamptz` para evitar ambigüedad de zona horaria.
- **Defaults seguros**: `status` de instituciones arranca en `trial`, `technical_status` en `pending`.
- **Unicidad crítica**: `email` (crm_users), `slug`, `subdomain`, `public_code` (institutions), `token_hash` (refresh).
- **Ampliable**: las columnas textuales como `current_plan_name` se migrarán a una FK hacia `plans` cuando se implemente el módulo en la Fase 2 sin disrupción.
