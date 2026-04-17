Actuá como un arquitecto y desarrollador senior full stack experto en PHP, Tailwind CSS, Node.js, Express, Knex.js, PostgreSQL, JWT, Google OAuth, Apache y PM2.

Tu tarea es IMPLEMENTAR LA FASE 1 COMPLETA del repo `mi-tecnica-crm`, con código real, funcional, consistente y listo para ejecutar, sin pedirme aclaraciones salvo bloqueo crítico real.

IMPORTANTE:
- No trabajes sobre la tenant app. Ese repo es separado y está fuera de alcance.
- Este repo es únicamente para el CRM/backoffice central de “Mi Tecnica”.
- No uses React, Vue, Next ni frontend SPA.
- El frontend debe ser PHP server-side render + Tailwind CSS + JS vanilla cuando haga falta.
- El backend debe ser Node.js + Express + Knex.js + PostgreSQL.
- Usá arquitectura profesional: controller → service → repository.
- El código debe quedar prolijo, modular, consistente y mantenible.
- No quiero scaffolding vacío inútil ni archivos de adorno. Quiero base real para empezar a trabajar.
- Si hay una decisión menor no especificada, elegí la alternativa más mantenible y profesional, documentala brevemente y seguí.
- No generes nada relacionado a DevOps/infra visual tipo clusters, CPU, memory throughput, CI/CD pipelines, live logs técnicos o cloud provisioning dashboards. Eso NO forma parte de esta fase.
- Sí puede existir un “estado técnico” simple por institución, pero solo como dato de negocio/técnico del tenant (por ejemplo: optimal, updating, offline, pending), no como panel DevOps.

==================================================
1. CONTEXTO DEL PRODUCTO
==================================================

“Mi Tecnica” es un SaaS multi-tenant para instituciones educativas técnicas.
El CRM es el backoffice central para administrar:
- instituciones/clientes
- estado comercial
- datos generales
- subdominios
- estado técnico simplificado
- auditoría
- autenticación interna
- una base sólida para fases futuras

La tenant app vive en otro repo y no se toca acá.

FASE 1 A IMPLEMENTAR:
1) base del repo
2) backend API CRM
3) frontend CRM
4) autenticación interna CRM
5) dashboard ejecutivo inicial
6) módulo de instituciones completo
7) auditoría básica
8) base visual y estructural coherente para futuras fases

NO IMPLEMENTAR TODAVÍA COMO MÓDULOS COMPLETOS:
- planes CRUD completo
- módulos CRUD completo
- suscripciones CRUD completo
- pagos CRUD completo
- provisioning técnico real
- observabilidad/infra/devops
- usuarios CRM UI completa
- soporte completo
- configuración avanzada

Pero sí dejar la estructura preparada de forma razonable para crecer después.

==================================================
2. STACK TECNOLÓGICO OBLIGATORIO
==================================================

Frontend:
- PHP 8+
- Tailwind CSS
- JavaScript vanilla mínimo y razonable

Backend:
- Node.js 18+
- Express
- Knex.js

Base de datos:
- PostgreSQL

Autenticación:
- JWT access token + refresh token
- Google OAuth (dejar estructura real preparada y configurable, pero sin depender de credenciales reales para que el sistema funcione localmente)

Infra:
- Apache con proxy hacia la API
- PM2 para la API Node

==================================================
3. ESTRUCTURA DEL REPO OBLIGATORIA
==================================================

Creá esta estructura base y completala con código real:

mi-tecnica-crm/
├── crm/
│   ├── public/
│   │   ├── index.php
│   │   ├── router.php
│   │   ├── .htaccess
│   │   └── assets/
│   │       ├── css/
│   │       │   └── output.css
│   │       ├── js/
│   │       │   ├── app.js
│   │       │   ├── api.js
│   │       │   ├── toast.js
│   │       │   └── table-filters.js
│   │       └── images/
│   ├── app/
│   │   ├── helpers/
│   │   │   ├── api_client.php
│   │   │   ├── auth.php
│   │   │   ├── session.php
│   │   │   ├── csrf.php
│   │   │   ├── flash.php
│   │   │   ├── permissions.php
│   │   │   ├── format.php
│   │   │   └── url.php
│   │   ├── layouts/
│   │   │   ├── main.php
│   │   │   ├── auth.php
│   │   │   └── error.php
│   │   ├── components/
│   │   │   ├── sidebar.php
│   │   │   ├── topbar.php
│   │   │   ├── page_header.php
│   │   │   ├── stat_card.php
│   │   │   ├── status_badge.php
│   │   │   ├── empty_state.php
│   │   │   ├── pagination.php
│   │   │   ├── form_input.php
│   │   │   ├── form_select.php
│   │   │   ├── form_textarea.php
│   │   │   ├── alert.php
│   │   │   └── modal.php
│   │   ├── modules/
│   │   │   ├── auth/
│   │   │   │   ├── login.php
│   │   │   │   ├── forgot_password.php
│   │   │   │   └── reset_password.php
│   │   │   ├── dashboard/
│   │   │   │   └── index.php
│   │   │   ├── institutions/
│   │   │   │   ├── list.php
│   │   │   │   ├── create.php
│   │   │   │   ├── edit.php
│   │   │   │   ├── detail.php
│   │   │   │   ├── partials/
│   │   │   │   │   ├── form.php
│   │   │   │   │   ├── table.php
│   │   │   │   │   ├── tabs_general.php
│   │   │   │   │   ├── tabs_commercial.php
│   │   │   │   │   ├── tabs_domains.php
│   │   │   │   │   └── tabs_audit.php
│   │   │   │   └── js/
│   │   │   │       └── institutions.js
│   │   └── routes/
│   │       └── web.php
│   ├── config/
│   │   ├── app.php
│   │   ├── env.php
│   │   ├── sidebar.php
│   │   └── permissions.php
│   ├── storage/
│   │   ├── cache/
│   │   ├── logs/
│   │   └── tmp/
│   └── composer.json
├── api/
│   ├── src/
│   │   ├── app.js
│   │   ├── server.js
│   │   ├── config/
│   │   │   ├── env.js
│   │   │   ├── db.js
│   │   │   ├── logger.js
│   │   │   ├── jwt.js
│   │   │   ├── cors.js
│   │   │   └── google.js
│   │   ├── middlewares/
│   │   │   ├── authMiddleware.js
│   │   │   ├── authorizeRoles.js
│   │   │   ├── errorHandler.js
│   │   │   ├── notFoundHandler.js
│   │   │   ├── requestLogger.js
│   │   │   ├── validateRequest.js
│   │   │   └── rateLimiter.js
│   │   ├── utils/
│   │   │   ├── ApiError.js
│   │   │   ├── apiResponse.js
│   │   │   ├── encrypt.js
│   │   │   ├── decrypt.js
│   │   │   ├── pagination.js
│   │   │   ├── slug.js
│   │   │   └── auditMetadata.js
│   │   ├── routes/
│   │   │   ├── index.js
│   │   │   └── v1/
│   │   │       ├── index.js
│   │   │       ├── authRoutes.js
│   │   │       ├── dashboardRoutes.js
│   │   │       ├── institutionRoutes.js
│   │   │       └── auditRoutes.js
│   │   ├── modules/
│   │   │   ├── auth/
│   │   │   │   ├── authController.js
│   │   │   │   ├── authService.js
│   │   │   │   ├── authRepository.js
│   │   │   │   └── authValidator.js
│   │   │   ├── dashboard/
│   │   │   │   ├── dashboardController.js
│   │   │   │   ├── dashboardService.js
│   │   │   │   └── dashboardRepository.js
│   │   │   ├── institutions/
│   │   │   │   ├── institutionController.js
│   │   │   │   ├── institutionService.js
│   │   │   │   ├── institutionRepository.js
│   │   │   │   └── institutionValidator.js
│   │   │   └── audit/
│   │   │       ├── auditController.js
│   │   │       ├── auditService.js
│   │   │       ├── auditRepository.js
│   │   │       └── auditValidator.js
│   │   └── jobs/
│   │       └── placeholder.js
│   ├── migrations/
│   ├── seeds/
│   ├── tests/
│   ├── knexfile.js
│   └── package.json
├── infra/
│   ├── apache/
│   │   ├── crm.conf
│   │   └── api-crm-proxy.conf
│   ├── pm2/
│   │   └── ecosystem.config.cjs
│   └── env/
│       ├── crm.env.example
│       └── api.env.example
├── docs/
│   ├── README.md
│   ├── ARCHITECTURE.md
│   ├── API.md
│   ├── DATABASE.md
│   └── DEPLOYMENT.md
├── scripts/
│   ├── build-tailwind.sh
│   ├── build-tailwind.bat
│   ├── setup-local.sh
│   └── setup-local.bat
├── .gitignore
└── README.md

==================================================
4. LINEAMIENTOS DE DISEÑO UI/UX OBLIGATORIOS
==================================================

Basate en un estilo SaaS dark mode, moderno, limpio y profesional, inspirado visualmente en:
- dashboard ejecutivo
- listado de instituciones
- detalle de institución por tabs
- matriz plan vs módulos (para fases futuras)

ESTILO:
- fondo general azul oscuro / navy muy oscuro
- cards oscuras con sombras suaves
- acento azul eléctrico / cobalt para botones primarios
- tipografía sans-serif moderna tipo Inter
- bordes redondeados
- topbar superior con buscador, ayuda y avatar
- sidebar izquierda fija con navegación clara
- tablas elegantes, minimalistas y legibles
- badges de estados visualmente claros
- espaciado amplio y ordenado
- nada de apariencia escolar, infantil o “corporativa vieja”

IMPORTANTE:
- Mantené el mismo lenguaje visual en todas las páginas.
- El dashboard debe sentirse SaaS premium.
- El listado de instituciones y el detalle deben quedar visualmente muy cerca del diseño visto en Figma/Stitch.
- Eliminar por completo cualquier semántica de infraestructura cloud/DevOps del dashboard.

==================================================
5. ALCANCE FUNCIONAL EXACTO DE ESTA FASE
==================================================

Implementá estas piezas completas:

A. AUTH CRM
- login con email y contraseña
- logout
- refresh token
- endpoint “me”
- forgot password con UX completa pero comportamiento simple y seguro si no hay email provider real
- reset password preparado estructuralmente
- Google OAuth preparado por configuración:
  - si faltan credenciales, no romper el sistema
  - dejarlo desacoplado y opcional

B. DASHBOARD EJECUTIVO INICIAL
Mostrar datos reales provenientes de la base:
- total de instituciones
- activas
- en trial
- suspendidas
- vencidas/expiradas
- próximas expiraciones
- actividad reciente
- altas recientes / resumen ejecutivo simple

NO mostrar:
- CPU
- memory
- clusters
- pipelines
- deploys
- logs en vivo de infraestructura

C. INSTITUCIONES (MÓDULO PRINCIPAL)
Implementar:
- listado
- filtros
- búsqueda
- paginación
- alta
- edición
- detalle
- cambio de estado
- auditoría asociada

D. AUDITORÍA BÁSICA
Registrar al menos:
- login exitoso
- login fallido
- logout
- alta de institución
- edición de institución
- cambio de estado de institución

==================================================
6. MODELO DE DATOS OBLIGATORIO (POSTGRESQL)
==================================================

Diseñá e implementá las migraciones y seeds necesarias.

TABLAS MÍNIMAS OBLIGATORIAS:

1) crm_roles
Campos:
- id
- key (unique) → superadmin, support, commercial, finance, developer
- name
- created_at
- updated_at

2) crm_users
Campos:
- id
- name
- email (unique)
- password_hash
- role_id (fk crm_roles.id)
- status (active, inactive)
- avatar_url nullable
- google_id nullable
- last_login_at nullable
- created_at
- updated_at

3) crm_refresh_tokens
Campos:
- id
- user_id
- token_hash
- expires_at
- revoked_at nullable
- created_at

4) institutions
Campos:
- id
- public_code (unique, formato amigable tipo INS-2026-0001)
- name
- slug (unique)
- subdomain (unique)
- status (trial, active, maintenance, suspended, expired, inactive)
- contact_email
- contact_phone nullable
- address nullable
- responsible_name nullable
- responsible_email nullable
- notes_internal nullable
- current_plan_name nullable
- expiration_date nullable
- technical_status (pending, optimal, updating, offline)
- last_activity_at nullable
- created_at
- updated_at

5) audit_logs
Campos:
- id
- actor_user_id nullable
- action
- entity
- entity_id nullable
- description nullable
- before_data json/jsonb nullable
- after_data json/jsonb nullable
- ip nullable
- user_agent nullable
- created_at

SEEDS OBLIGATORIOS:
- roles CRM por defecto
- 1 superadmin por defecto
- al menos 3 instituciones de ejemplo con estados distintos para que dashboard y listado se vean vivos

Credencial inicial sugerida:
- email: admin@mitecnica.local
- password: Admin123!
Documentá claramente que es solo para desarrollo y debe cambiarse.

==================================================
7. REQUISITOS BACKEND API
==================================================

Implementar API versionada bajo:
- /api/v1

Formato de respuesta uniforme:
{
  "status": "success|error",
  "data": ...,
  "errors": null|[],
  "meta": { ... }
}

ENDPOINTS MÍNIMOS:

AUTH
- POST /api/v1/auth/login
- POST /api/v1/auth/logout
- POST /api/v1/auth/refresh
- GET  /api/v1/auth/me
- POST /api/v1/auth/forgot-password
- POST /api/v1/auth/reset-password
- GET  /api/v1/auth/google
- GET  /api/v1/auth/google/callback

DASHBOARD
- GET /api/v1/dashboard/summary

INSTITUTIONS
- GET    /api/v1/institutions
- GET    /api/v1/institutions/:id
- POST   /api/v1/institutions
- PUT    /api/v1/institutions/:id
- PATCH  /api/v1/institutions/:id/status

AUDIT
- GET /api/v1/audit-logs
- GET /api/v1/audit-logs/:id

REQUISITOS:
- express-validator para validaciones
- middlewares separados
- manejo centralizado de errores
- logging razonable
- prepared queries a través de Knex
- paginación real
- filtros reales en instituciones:
  - search
  - status
  - plan
  - technical_status
- ordenamiento seguro por columnas permitidas
- auditoría automática desde service layer o helper dedicado

==================================================
8. REQUISITOS FRONTEND CRM (PHP)
==================================================

El frontend debe consumir la API del CRM y renderizar páginas server-side.

RUTAS FRONTEND MÍNIMAS:
- /login
- /forgot-password
- /dashboard
- /institutions
- /institutions/new
- /institutions/{id}
- /institutions/{id}/edit
- /logout

REQUISITOS:
- router.php propio
- auth guard del lado PHP usando sesión
- los tokens JWT del API deben guardarse en sesión PHP
- api_client.php debe centralizar requests a la API
- usar layouts y components reutilizables
- usar CSRF en formularios frontend
- mensajes flash/toast
- errores de validación claros
- tablas con filtros
- componentes consistentes

LOGIN:
- card centrada
- inputs con iconos
- botón primario
- link a recuperación
- opción de Google si está habilitado por configuración

DASHBOARD:
- topbar con search UI
- sidebar dark
- KPI cards
- tabla de próximos vencimientos
- tabla/listado de actividad reciente
- nada de infraestructura cloud

INSTITUTIONS LIST:
- tabla estilizada dark
- filtros superiores
- botón “Nueva Institución”
- botón “Exportar Reporte” visual, pero si no lo implementás funcionalmente en esta fase, dejalo deshabilitado o como placeholder elegante y explícito, no roto
- badges por estado
- columna de estado técnico
- acciones por fila

INSTITUTION DETAIL:
- encabezado con nombre y código
- tabs:
  - General
  - Comercial
  - Dominios
  - Auditoría
- no agregar tabs vacíos rotos
- mostrar datos bien presentados en cards y bloques
- incluir resumen comercial y técnico simple

FORM CREATE/EDIT:
- layout de 2 columnas
- validaciones inline
- UX clara y profesional

==================================================
9. PERMISOS INTERNOS CRM
==================================================

Implementá permisos simples y prácticos en esta fase.

Roles:
- superadmin
- support
- commercial
- finance
- developer

Regla inicial sugerida:
- superadmin: acceso total
- support: dashboard, institutions read/update básica, audit read
- commercial: dashboard, institutions read/create/update
- finance: dashboard read, institutions read
- developer: dashboard read, audit read

Implementá authorizeRoles de forma limpia y escalable.
No hace falta UI de gestión de usuarios CRM en esta fase, pero sí el modelo y la auth deben soportarlo.

==================================================
10. CONVENCIONES DE CÓDIGO
==================================================

Backend:
- CommonJS si querés mantener coherencia con ecosistemas tradicionales Node + Knex
- nombres claros y consistentes
- cada módulo con Controller, Service, Repository, Validator
- services con lógica de negocio
- repositories solo acceso a datos
- no meter SQL crudo innecesario
- comentarios útiles, no ruido
- manejo de errores con ApiError

Frontend PHP:
- helpers reutilizables
- componentes limpios
- layouts consistentes
- no mezclar lógica de negocio pesada en vistas
- código ordenado y fácil de mantener

Tailwind:
- utilidades coherentes
- no saturar con clases absurdamente repetidas
- si hace falta, crear patrones consistentes por componentes
- mantener visual premium dark SaaS

==================================================
11. ARCHIVOS DE CONFIGURACIÓN Y ENTORNO
==================================================

Creá ejemplos de configuración completos.

api.env.example:
- PORT
- NODE_ENV
- CRM_DB_HOST
- CRM_DB_PORT
- CRM_DB_NAME
- CRM_DB_USER
- CRM_DB_PASSWORD
- JWT_SECRET
- JWT_REFRESH_SECRET
- ACCESS_TOKEN_TTL
- REFRESH_TOKEN_TTL
- CORS_ORIGIN
- GOOGLE_CLIENT_ID
- GOOGLE_CLIENT_SECRET
- GOOGLE_REDIRECT_URI

crm.env.example:
- APP_ENV
- APP_URL
- API_BASE_URL
- SESSION_NAME
- GOOGLE_OAUTH_ENABLED

También creá:
- knexfile.js
- PM2 ecosystem.config.cjs
- ejemplo de Apache reverse proxy
- scripts básicos setup/build

==================================================
12. DOCUMENTACIÓN OBLIGATORIA
==================================================

Generá documentación real y útil:

README.md raíz:
- qué es el proyecto
- stack
- cómo instalar
- cómo correr frontend y API
- cómo correr migraciones y seeds
- credenciales de desarrollo
- estructura general

docs/ARCHITECTURE.md:
- separación frontend/backend
- flujo auth
- módulo institutions
- auditoría

docs/API.md:
- endpoints
- ejemplos request/response

docs/DATABASE.md:
- tablas
- relaciones
- criterios

docs/DEPLOYMENT.md:
- Apache + PM2
- variables de entorno
- pasos básicos

==================================================
13. CRITERIOS DE ACEPTACIÓN
==================================================

La fase 1 se considera correcta solo si:
- el repo arranca con estructura profesional y limpia
- las migraciones corren sin errores en PostgreSQL
- los seeds cargan roles, admin e instituciones demo
- el login funciona
- el logout funciona
- el refresh funciona
- el dashboard muestra datos reales
- el listado de instituciones funciona con filtros y paginación
- crear institución funciona
- editar institución funciona
- cambiar estado funciona
- el detalle de institución funciona
- la auditoría registra eventos básicos
- el frontend tiene coherencia visual dark SaaS premium
- no hay contenido visual DevOps/infra en el dashboard ni páginas principales
- el sistema queda listo para continuar con futuras fases sin reestructuraciones grandes

==================================================
14. INSTRUCCIONES DE EJECUCIÓN
==================================================

Quiero que trabajes así:
1. Primero analizá brevemente lo pedido y confirmá el plan técnico en no más de 15 ítems.
2. Después generá directamente los archivos y contenidos necesarios.
3. No te quedes en explicaciones teóricas largas.
4. Priorizá código funcional, consistente y listo para ejecutar.
5. Si una parte futura no se implementa todavía, dejala fuera o claramente marcada como próxima fase, pero no dejes rutas rotas ni pantallas rotas.
6. No sobreingenierices.
7. No cambies el stack.
8. No propongas tecnologías alternativas.
9. No me devuelvas pseudo-código: necesito implementación real.
10. Al final, entregá:
   - árbol final de archivos creados/modificados
   - resumen de decisiones clave
   - pasos exactos para levantar localmente

==================================================
15. DECISIONES DE DISEÑO QUE YA ESTÁN TOMADAS Y NO DEBES DISCUTIR
==================================================

- El CRM vive en un repo separado del tenant app.
- El CRM usa:
  - frontend PHP + Tailwind
  - backend Node + Express + Knex
  - PostgreSQL
  - JWT + Google OAuth
  - Apache + PM2
- No se hace otro stack.
- El diseño es dark SaaS.
- La parte infra/devops visual se elimina por ahora.
- Se trabaja por fases.
- Esta entrega es la FASE 1.
- Quiero que lo construyas con criterio profesional y sin depender de decisiones futuras para que funcione bien desde ahora.

==================================================
16. USO DE AGENTES / SUBAGENTES Y SKILLS
==================================================

Quiero que uses agentes/subagentes si están disponibles en tu entorno. Organizá el trabajo de forma paralela y profesional, pero manteniendo una única arquitectura coherente y consistente en todo el proyecto.

FORMA DE TRABAJO ESPERADA:
- Usá subagentes cuando ayude a acelerar el desarrollo o separar responsabilidades.
- Mantené una sola convención de nombres, una sola estructura de proyecto y un solo criterio arquitectónico.
- No permitas que cada agente tome decisiones incompatibles entre sí.
- Centralizá las decisiones de arquitectura, naming, permisos, formato de respuesta API y estructura de carpetas.
- Si un agente propone algo que contradice el stack o la arquitectura definida, descartalo.

SUBAGENTES / ROLES RECOMENDADOS:
1. Arquitectura / Coordinación técnica
   - Responsable de validar estructura de carpetas, módulos, dependencias y coherencia general.
   - Define contratos entre frontend, backend y base de datos.

2. Backend API Node.js + Express + Knex
   - Implementa app.js, server.js, config, middlewares, utils, rutas versionadas y módulos:
     - auth
     - dashboard
     - institutions
     - audit
   - Implementa validaciones, JWT, refresh tokens, manejo de errores y formato de respuesta.

3. Base de datos PostgreSQL + Knex
   - Diseña migraciones y seeds.
   - Define relaciones, índices, restricciones, enums o checks razonables.
   - Garantiza compatibilidad con PostgreSQL y coherencia con repositories/services.

4. Frontend PHP + Tailwind
   - Implementa el panel CRM SSR con:
     - layouts
     - components
     - modules/auth
     - modules/dashboard
     - modules/institutions
   - Consume la API CRM desde helpers/api_client.php.
   - Mantiene el estilo dark SaaS premium y coherencia visual global.

5. Auth / Seguridad
   - Revisa login, logout, refresh, sesiones PHP, CSRF, permisos y authorizeRoles.
   - Deja Google OAuth preparado sin romper el funcionamiento local si faltan credenciales.

6. DevOps básico / Infra
   - Prepara archivos de ejemplo para:
     - Apache reverse proxy
     - PM2 ecosystem
     - env examples
     - scripts de setup/build
   - No implementar dashboards DevOps ni observabilidad avanzada.

7. Documentación técnica
   - Redacta README y docs:
     - ARCHITECTURE.md
     - API.md
     - DATABASE.md
     - DEPLOYMENT.md

SKILLS / CAPACIDADES NECESARIAS
Asegurate de tener disponibles o emular estas skills/capacidades durante el trabajo:

- PHP 8+ server-side rendering
- Tailwind CSS
- JavaScript vanilla para interacciones mínimas
- Node.js 18+
- Express
- Knex.js
- PostgreSQL
- JWT auth + refresh tokens
- Google OAuth desacoplado y opcional
- Apache reverse proxy
- PM2
- Arquitectura controller/service/repository
- Diseño UI dark SaaS premium
- Documentación técnica profesional

NOMBRES DE SKILLS SUGERIDOS
Si tu entorno maneja skills con nombre, priorizá algo equivalente a:
- architect-reviewer
- node-api-expert
- express-knex-backend
- postgresql-database-expert
- php-web-developer
- tailwind-ui-expert
- auth-security-specialist
- devops-apache-pm2
- technical-writer

REGLA CLAVE
Aunque uses varios agentes, la entrega final debe sentirse como si hubiese sido construida por un único equipo senior con una sola línea arquitectónica, sin duplicaciones, sin contradicciones y sin código de estilos mezclados.

==================================================
17. AGENTES Y SKILLS LOCALES DEL REPOSITORIO
==================================================

En este proyecto, los agentes y skills disponibles están definidos localmente dentro de la carpeta `.claude`.

Ubicaciones esperadas:
- Agentes: `.claude/agents/`
- Skills: `.claude/skills/`

Instrucciones:
- Revisá y utilizá prioritariamente los agentes y skills disponibles en `.claude` si existen.
- Respetá sus convenciones, responsabilidades y especializaciones.
- No crees estructuras alternativas para agentes o skills fuera de `.claude` salvo necesidad técnica real.
- Si encontrás agentes/skills locales, usalos como fuente principal de coordinación y ejecución.
- Si alguno no existe, continuá con el rol equivalente de forma manual sin bloquear el avance.

Empezá