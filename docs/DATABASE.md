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

## Relaciones

```
crm_roles (1) ──< (n) crm_users (1) ──< (n) crm_refresh_tokens
                               │
                               └──< (n) audit_logs.actor_user_id

institutions (1) ──< (n) audit_logs (via entity='institutions', entity_id=institution.id)
```

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
