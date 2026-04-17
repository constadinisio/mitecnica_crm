# Mi Tecnica CRM — Backoffice central

SaaS multi-tenant para instituciones educativas técnicas. Este repo contiene únicamente el **CRM/backoffice central** (administración de instituciones, estado comercial, subdominios, auditoría y auth interna).

> La tenant app vive en otro repositorio y no se toca acá.

---

## Stack

| Capa | Tecnología |
|------|-----------|
| Frontend | PHP 8+ server-side render + Tailwind CSS + JS vanilla |
| Backend API | Node.js 18+ + Express + Knex.js |
| Base de datos | PostgreSQL 14+ |
| Auth | JWT (access + refresh) + Google OAuth (opcional) |
| Infra | Apache (reverse proxy) + PM2 |

Arquitectura backend: **controller → service → repository** con validaciones, middlewares, manejo centralizado de errores y auditoría desde service layer.

---

## Estructura general

```
mi-tecnica-crm/
├── crm/          # Frontend PHP (backoffice SSR)
├── api/          # Backend Node.js + Express + Knex
├── infra/        # Apache, PM2, env examples
├── docs/         # Documentación técnica
└── scripts/      # Setup y build scripts
```

Ver [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) para el detalle completo.

---

## Instalación local

### 1. Clonar e instalar dependencias

```bash
# Backend API
cd api
cp ../infra/env/api.env.example .env
npm install

# Frontend CRM
cd ../crm
cp ../infra/env/crm.env.example .env
composer install   # opcional; no hay dependencias externas obligatorias
```

### 2. Configurar PostgreSQL

Crear la base de datos:

```sql
CREATE DATABASE mitecnica_crm;
CREATE USER mitecnica WITH ENCRYPTED PASSWORD 'mitecnica';
GRANT ALL PRIVILEGES ON DATABASE mitecnica_crm TO mitecnica;
```

Ajustar `api/.env` con los valores de tu PostgreSQL.

### 3. Migraciones y seeds

```bash
cd api
npx knex migrate:latest
npx knex seed:run
```

Esto crea las tablas y carga:

- Roles CRM (superadmin, support, commercial, finance, developer)
- Superadmin: `admin@mitecnica.local` / `Admin123!`
- 5 instituciones demo con distintos estados

> **Importante:** la credencial inicial es sólo para desarrollo. Cambiala inmediatamente en producción.

### 4. Compilar Tailwind CSS

```bash
# Linux/macOS
./scripts/build-tailwind.sh

# Windows
scripts\build-tailwind.bat
```

Si no tenés Node Tailwind CLI instalado, el CSS precompilado ya viene incluido en `crm/public/assets/css/output.css` para que el CRM arranque sin build.

### 5. Levantar servicios

```bash
# Backend API (puerto 4000 por defecto)
cd api
npm run dev

# Frontend CRM (puerto 8080 por defecto vía PHP built-in server)
cd crm
php -S localhost:8080 -t public public/router.php
```

Abrí http://localhost:8080/login y entrá con la credencial inicial.

---

## Scripts útiles

| Script | Descripción |
|--------|-------------|
| `api/npm run dev` | API con nodemon |
| `api/npm run start` | API en modo prod |
| `api/npm run migrate` | Corre migraciones pendientes |
| `api/npm run seed` | Ejecuta seeds |
| `api/npm run rollback` | Rollback de la última migración |
| `scripts/setup-local.sh` | Setup end-to-end (instala + migra + seed) |

---

## Deploy

Ver [docs/DEPLOYMENT.md](./docs/DEPLOYMENT.md) para la guía de Apache + PM2.

---

## Documentación

- [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) — Arquitectura y flujos
- [docs/API.md](./docs/API.md) — Endpoints y ejemplos
- [docs/DATABASE.md](./docs/DATABASE.md) — Esquema y relaciones
- [docs/DEPLOYMENT.md](./docs/DEPLOYMENT.md) — Despliegue Apache + PM2

---

## Fases

- **Fase 1** ✅ — base del repo, auth CRM, dashboard ejecutivo inicial, módulo instituciones, auditoría, fundaciones visuales.
- **Fase 2A** ✅ — núcleo comercial: planes, módulos del producto, matriz plan × módulos, suscripciones y pagos. Moneda por defecto **ARS** (producto argentino). Dashboard extendido con KPIs comerciales. Constraint de "una suscripción viva por institución" a nivel DB.

Quedan para próximas fases: provisioning técnico real, overrides por institución, billing automation, pasarela de pagos real, UI completa de usuarios CRM, soporte/tickets, observabilidad, configuración avanzada.
