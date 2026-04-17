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
