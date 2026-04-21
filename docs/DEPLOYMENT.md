# Deployment — Mi Tecnica CRM

Guía para desplegar la **Fase 1** sobre un servidor Linux con Apache + PM2 + PostgreSQL.

## Requisitos

- Ubuntu 22.04 (u otra distro Linux)
- PHP 8.1+ con `mod_php` o PHP-FPM
- Apache 2.4+ (`mod_rewrite`, `mod_headers`, `mod_proxy`, `mod_proxy_http`, `mod_ssl` si vas con TLS)
- Node.js 18+
- PM2 (`npm i -g pm2`)
- PostgreSQL 14+
- (opcional) Composer

## 1. Preparar el servidor

```bash
sudo apt update
sudo apt install -y apache2 php php-cli php-curl php-mbstring postgresql nodejs npm
sudo a2enmod rewrite headers proxy proxy_http ssl
sudo npm install -g pm2
```

## 2. Clonar y configurar

```bash
sudo mkdir -p /var/www
sudo chown -R $USER:$USER /var/www
cd /var/www
git clone <repo> mi-tecnica-crm
cd mi-tecnica-crm
cp infra/env/api.env.example api/.env
cp infra/env/crm.env.example crm/.env
```

Editá `api/.env`:

- `NODE_ENV=production`
- `PORT=4000`
- Datos reales de PostgreSQL
- Secretos JWT fuertes (`openssl rand -hex 64` para cada uno)
- `CORS_ORIGIN=https://crm.tu-dominio.ar`
- `GOOGLE_CLIENT_ID/SECRET` si vas a usar Google OAuth (opcional; si no los ponés no se rompe)

Editá `crm/.env`:

- `APP_ENV=production`
- `APP_URL=https://crm.tu-dominio.ar`
- `API_BASE_URL=https://crm.tu-dominio.ar/api/v1` (porque Apache hace proxy al mismo host)
- `GOOGLE_OAUTH_ENABLED=true` si aplica

## 3. Base de datos

```sql
-- En psql como superuser:
CREATE USER mitecnica WITH ENCRYPTED PASSWORD '...';
CREATE DATABASE mitecnica_crm OWNER mitecnica;
\c mitecnica_crm
CREATE EXTENSION IF NOT EXISTS pg_trgm;  -- opcional, mejora búsqueda por nombre
```

Migrar y sembrar:

```bash
cd /var/www/mi-tecnica-crm/api
npm ci --only=production
npx knex migrate:latest
npx knex seed:run
```

Después del primer seed **cambiá la contraseña del admin** (`admin@mitecnica.local / Admin123!`).

## 4. PM2 para el API

```bash
cd /var/www/mi-tecnica-crm
pm2 start infra/pm2/ecosystem.config.cjs --env production
pm2 save
pm2 startup  # seguí las instrucciones que imprime
```

Monitoreo básico:

```bash
pm2 list
pm2 logs mitecnica-crm-api
pm2 reload mitecnica-crm-api   # zero-downtime reload
```

## 5. Apache

Copiá las configuraciones:

```bash
sudo cp infra/apache/api-crm-proxy.conf /etc/apache2/sites-available/
sudo cp infra/apache/crm.conf /etc/apache2/sites-available/
sudo a2ensite crm.conf
sudo systemctl reload apache2
```

Ajustá `ServerName`, `DocumentRoot` y rutas a tu servidor real.

Para TLS, usá Certbot:

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d crm.tu-dominio.ar
```

## 6. Tailwind / assets

El repo trae un `output.css` funcional listo para producción. Para regenerar el bundle optimizado:

```bash
./scripts/build-tailwind.sh
```

## 7. Checklist post-deploy

- [ ] `GET https://crm.tu-dominio.ar/health` responde `200` con `status: ok` (liveness, sin DB)
- [ ] `GET https://crm.tu-dominio.ar/ready` responde `200` con `db.status: ok` (readiness, valida PostgreSQL)
- [ ] `GET https://crm.tu-dominio.ar/api/v1/health` es idéntico a `/ready`
- [ ] Login con admin funciona
- [ ] Dashboard carga las tarjetas, tablas y card "Pulso operativo" con datos reales
- [ ] Listado de instituciones filtra, pagina, ordena y exporta CSV
- [ ] Exportaciones `/institutions/export.csv`, `/payments/export.csv`, `/audit/export.csv` devuelven UTF-8 con BOM
- [ ] Navegación cruzada funciona: institution ↔ subscription ↔ payment ↔ audit filtered
- [ ] Creación y edición funcionan, disparan auditoría
- [ ] `PATCH /:id/status` funciona y queda asentado en la auditoría
- [ ] Password del admin cambiada
- [ ] Backups de PostgreSQL configurados
- [ ] PM2 corre como servicio (`pm2 startup`)
- [ ] Logs rotando (`pm2 install pm2-logrotate`)
- [ ] Load balancer / uptime monitor apuntando a `/health` (liveness) y alertas en `/ready` (readiness)

## 8. Troubleshooting rápido

| Síntoma | Causa probable |
|---------|----------------|
| 502/504 en `/api` | PM2 caído — `pm2 logs mitecnica-crm-api` |
| 419 CSRF en forms | Cookie de sesión perdida — revisar `samesite`, `secure` y hostname |
| Login devuelve 401 | Revisar `JWT_SECRET` consistente entre procesos + hora del sistema |
| No aparecen estilos | Apache no está sirviendo `/assets/` — verificar `DocumentRoot` y `.htaccess` activo (`AllowOverride All`) |
| Migraciones fallan | Extensión `pg_trgm` no se pudo crear — el index es opcional, ignorable, pero el resto del esquema debería aplicar |
