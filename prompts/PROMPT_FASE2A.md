Actuá como un arquitecto y desarrollador senior full stack experto en PHP, Tailwind CSS, Node.js, Express, Knex.js, PostgreSQL, JWT y arquitectura modular profesional.

Tu tarea es IMPLEMENTAR LA FASE 2A COMPLETA del proyecto `mi-tecnica-crm`, continuando sobre una Fase 1 YA EXISTENTE Y FUNCIONAL.

IMPORTANTE:
- No reestructures el proyecto desde cero.
- No cambies el stack.
- No cambies la arquitectura base ya implementada.
- No rompas compatibilidad con lo construido en Fase 1.
- No toques el repo tenant app.
- No agregues features fuera del alcance.
- Quiero implementación real, consistente y lista para ejecutar.

==================================================
1. CONTEXTO ACTUAL DEL PROYECTO
==================================================

La Fase 1 ya está implementada y funcionando. Ya existen:

- autenticación CRM completa
- dashboard ejecutivo inicial
- módulo institutions completo
- auditoría básica
- RBAC
- API Node + Express + Knex
- frontend PHP SSR + Tailwind dark SaaS
- PostgreSQL con tablas base
- documentación e infraestructura mínima

NO hay que rehacer eso.
Hay que EXTENDER el sistema de forma coherente.

El objetivo de esta Fase 2A es implementar el núcleo comercial del CRM.

==================================================
2. OBJETIVO DE LA FASE 2A
==================================================

Implementar estos módulos completos del CRM:

1. Planes
2. Módulos del producto
3. Matriz Planes vs Módulos
4. Suscripciones
5. Pagos

Y dejar listo el soporte para:
- overrides por institución en una fase posterior
- licenciamiento real por tenant en fases posteriores

IMPORTANTE:
Todavía NO implementar:
- provisioning técnico real
- soporte/tickets
- observabilidad
- UI completa de CRM users
- configuración avanzada
- módulo de contactos/leads/pipeline
- overrides por institución
- billing automation
- pasarela de pagos real

==================================================
3. REGLAS DE CONTINUIDAD OBLIGATORIAS
==================================================

- Mantener la misma línea visual dark SaaS premium de Fase 1.
- Mantener la misma convención controller → service → repository.
- Mantener el mismo formato de respuesta API.
- Mantener la misma estrategia de validación y manejo de errores.
- Mantener el mismo sistema de auditoría.
- Mantener el mismo sistema de permisos.
- Mantener el mismo naming y estructura de carpetas existentes.
- Reutilizar layouts, componentes y helpers existentes siempre que sea razonable.
- No crear código duplicado innecesario.

==================================================
4. MÓDULOS A IMPLEMENTAR
==================================================

A. PLANES
CRUD completo de planes comerciales del producto.

B. MÓDULOS DEL PRODUCTO
Catálogo de módulos que Mi Tecnica puede ofrecer.

C. MATRIZ PLANES VS MÓDULOS
Relación entre planes y módulos.
Debe permitir ver y editar qué módulos incluye cada plan.

D. SUSCRIPCIONES
Relación comercial real entre institución y plan.

E. PAGOS
Registro de pagos manuales asociados a una suscripción o institución.

==================================================
5. ESTRUCTURA DEL PROYECTO A EXTENDER
==================================================

Debés trabajar sobre la estructura ya existente y agregar estos módulos.

FRONTEND PHP (crm/app/modules):
- plans/
- modules_catalog/
- plan_modules/
- subscriptions/
- payments/

BACKEND API (api/src/modules):
- plans/
- modules-catalog/
- plan-modules/
- subscriptions/
- payments/

RUTAS API NUEVAS:
- planRoutes.js
- moduleCatalogRoutes.js
- planModuleRoutes.js
- subscriptionRoutes.js
- paymentRoutes.js

Actualizar:
- api/src/routes/v1/index.js
- sidebar del frontend
- permisos frontend/backend
- documentación

==================================================
6. MODELO DE DATOS NUEVO (POSTGRESQL)
==================================================

Agregar migraciones y seeds para estas tablas nuevas.

6.1 plans
Campos:
- id
- code (unique, corto y técnico, ej: basic, pro, premium, enterprise)
- name
- description nullable
- billing_frequency (monthly, quarterly, yearly, custom)
- price_amount numeric(12,2)
- currency_code varchar(10) default 'USD'
- status (active, inactive, archived)
- is_custom boolean default false
- created_at
- updated_at

6.2 modules_catalog
Campos:
- id
- code (unique, ej: attendance, grades, campus, report_cards, families, doe, inventory, analytics)
- name
- description nullable
- category nullable (academic, communication, administration, technical, analytics, other)
- status (active, inactive)
- is_core boolean default false
- created_at
- updated_at

6.3 plan_modules
Tabla pivote.

Campos:
- id
- plan_id fk
- module_id fk
- included boolean default true
- created_at
- updated_at

Restricciones:
- unique(plan_id, module_id)

6.4 subscriptions
Campos:
- id
- institution_id fk institutions.id
- plan_id fk plans.id
- status (trial, active, suspended, expired, canceled)
- start_date date
- end_date nullable
- trial_ends_at nullable
- renewal_mode (manual, automatic)
- billing_notes nullable
- created_at
- updated_at

Regla:
- puede haber historial de suscripciones
- pero solo una suscripción “vigente/activa/trial” por institución a la vez
- resolvelo de manera razonable desde lógica de negocio y/o constraint parcial si lo considerás viable en PostgreSQL

6.5 payments
Campos:
- id
- institution_id fk institutions.id
- subscription_id nullable fk subscriptions.id
- amount numeric(12,2)
- currency_code varchar(10) default 'USD'
- payment_date timestamp
- status (pending, approved, rejected, expired, canceled)
- payment_method nullable
- reference_code nullable
- notes nullable
- created_by_user_id nullable fk crm_users.id
- created_at
- updated_at

Índices:
- status
- payment_date
- institution_id
- subscription_id

==================================================
7. SEEDS OBLIGATORIOS
==================================================

Agregar seeds coherentes para desarrollo:

- 4 planes demo:
  - basic
  - professional
  - elite
  - enterprise

- al menos 8 módulos demo:
  - attendance
  - grades
  - campus
  - report_cards
  - families
  - doe
  - inventory
  - analytics

- relaciones plan_modules demo coherentes
  Ejemplo:
  - basic: attendance, grades, report_cards
  - professional: + campus, families
  - elite: + doe, analytics
  - enterprise: todos

- suscripciones demo asociadas a algunas instituciones ya existentes
- pagos demo asociados

IMPORTANTE:
No romper seeds previos de Fase 1.
Integrar todo de forma limpia.

==================================================
8. API ENDPOINTS OBLIGATORIOS
==================================================

Implementar estos endpoints nuevos:

PLANES
- GET    /api/v1/plans
- GET    /api/v1/plans/:id
- POST   /api/v1/plans
- PUT    /api/v1/plans/:id
- PATCH  /api/v1/plans/:id/status

MÓDULOS
- GET    /api/v1/modules-catalog
- GET    /api/v1/modules-catalog/:id
- POST   /api/v1/modules-catalog
- PUT    /api/v1/modules-catalog/:id
- PATCH  /api/v1/modules-catalog/:id/status

PLAN MODULES
- GET    /api/v1/plan-modules/matrix
- GET    /api/v1/plans/:id/modules
- PUT    /api/v1/plans/:id/modules

SUSCRIPCIONES
- GET    /api/v1/subscriptions
- GET    /api/v1/subscriptions/:id
- POST   /api/v1/subscriptions
- PUT    /api/v1/subscriptions/:id
- PATCH  /api/v1/subscriptions/:id/status

PAGOS
- GET    /api/v1/payments
- GET    /api/v1/payments/:id
- POST   /api/v1/payments
- PUT    /api/v1/payments/:id
- PATCH  /api/v1/payments/:id/status

REPORTES BÁSICOS PARA DASHBOARD O RESÚMENES
- GET /api/v1/plans/summary
- GET /api/v1/payments/summary
- GET /api/v1/subscriptions/summary

==================================================
9. REQUISITOS FUNCIONALES BACKEND
==================================================

PLANES
- listado con paginación y filtros
- filtro por status
- búsqueda por name/code
- evitar duplicados por code
- patch de estado

MÓDULOS
- listado con paginación y filtros
- filtro por status
- filtro por category
- búsqueda por name/code
- evitar duplicados por code
- patch de estado

PLANES VS MÓDULOS
- endpoint matrix que devuelva:
  - lista de planes
  - lista de módulos
  - relaciones
- endpoint de actualización por plan:
  - reemplaza set de módulos incluidos de un plan
- validación de IDs existentes
- auditoría de cambios en entitlements del plan

SUSCRIPCIONES
- listar con filtros por:
  - institution_id
  - plan_id
  - status
  - renewal_mode
- crear suscripción nueva
- validar que no exista otra vigente incompatible para la misma institución
- editar fechas, notas y plan si corresponde
- patch de estado
- auditoría completa

PAGOS
- listar con filtros por:
  - institution_id
  - subscription_id
  - status
  - rango de fechas
- crear pago manual
- editar pago
- patch de estado
- auditoría completa

==================================================
10. FRONTEND PHP A IMPLEMENTAR
==================================================

Agregar páginas SSR para estos módulos.

10.1 Plans
Archivos sugeridos:
- list.php
- create.php
- edit.php
- detail.php
- partials/form.php
- partials/table.php

UI:
- tabla dark elegante
- filtros superiores
- badges de estado
- acciones por fila
- form profesional
- vista detalle usable

10.2 Modules Catalog
Archivos sugeridos:
- list.php
- create.php
- edit.php
- detail.php
- partials/form.php
- partials/table.php

UI:
- muy similar a plans para coherencia
- category visible
- estado visible
- indicadores de módulos core

10.3 Plan Modules
Archivos sugeridos:
- matrix.php
- js/plan-modules.js

UI obligatoria:
- pantalla tipo matriz
- filas = módulos
- columnas = planes
- celda tipo toggle/checkbox
- botón “Guardar cambios”
- panel lateral o resumen con:
  - cantidad de módulos por plan
  - notas visuales
- diseño dark premium
- debe recordar visualmente a la matriz que ya se vio en el diseño, pero ajustada a datos reales

10.4 Subscriptions
Archivos sugeridos:
- list.php
- create.php
- edit.php
- detail.php
- partials/form.php
- partials/table.php

UI:
- listado con institución, plan, estado, fechas, renovación
- badges por estado
- formulario claro
- detalle usable
- desde detalle de institución debería poder verse el resumen actual de suscripción si es posible sin reestructurar demasiado

10.5 Payments
Archivos sugeridos:
- list.php
- create.php
- edit.php
- detail.php
- partials/form.php
- partials/table.php

UI:
- listado con institución, monto, moneda, fecha, estado, método
- filtros
- badges de estado
- formulario de alta/edición
- detalle del pago

==================================================
11. SIDEBAR Y NAVEGACIÓN
==================================================

Actualizar el sidebar existente.
Los ítems que antes estaban como “Soon” deben pasar a módulos reales donde corresponda.

El menú debe quedar coherente, por ejemplo:

- Dashboard
- Institutions
- Plans
- Modules
- Plan Matrix
- Subscriptions
- Payments
- Audit
- Settings (si ya existía como placeholder, no romper)
- Support (si aún no se implementa, dejarlo claramente como próximo módulo sin confundir)

IMPORTANTE:
- No inventar menús de pipeline, contacts, leads o sales CRM tradicional.
- Este CRM es para gestionar el SaaS Mi Tecnica, no para ventas genéricas.

==================================================
12. PERMISOS / RBAC
==================================================

Extender permisos de forma consistente.

Regla sugerida:
- superadmin: total
- commercial: plans read/write, subscriptions read/write, payments read
- finance: payments read/write, subscriptions read
- support: read de planes/módulos/suscripciones/pagos
- developer: read de planes/módulos/audit

Implementar authorizeRoles en API y reflejo coherente en frontend permissions.php.
No hace falta crear una UI nueva de permisos; solo extender la existente.

==================================================
13. AUDITORÍA OBLIGATORIA
==================================================

Registrar al menos:
- create/update/status change de plans
- create/update/status change de modules
- cambios en plan_modules
- create/update/status change de subscriptions
- create/update/status change de payments

Guardar:
- actor_user_id
- action
- entity
- entity_id
- before_data
- after_data
- ip
- user_agent
- created_at

==================================================
14. DASHBOARD: EXTENSIONES MÍNIMAS
==================================================

Sin romper el dashboard actual, agregar datos útiles del núcleo comercial:

- total de planes activos
- total de suscripciones activas/trial
- pagos recientes
- monto cobrado recientemente (simple)
- próximos vencimientos de suscripción

No rehacer completamente el dashboard si no hace falta.
Solo extenderlo razonablemente.

==================================================
15. VALIDACIONES Y UX
==================================================

BACKEND:
- express-validator en todos los endpoints nuevos
- mensajes claros
- evitar estados inválidos
- validación de foreign keys
- ordenamiento seguro
- paginación real

FRONTEND:
- forms con validación inline
- flash messages / toasts
- old values
- empty states
- loading states razonables si aplica
- botones deshabilitados donde corresponda
- no dejar acciones rotas

==================================================
16. DOCUMENTACIÓN A ACTUALIZAR
==================================================

Actualizar:
- README.md
- docs/ARCHITECTURE.md
- docs/API.md
- docs/DATABASE.md

Agregar:
- descripción de nuevas tablas
- endpoints nuevos
- flujo comercial del CRM
- relación entre institutions, plans, subscriptions y payments

==================================================
17. CRITERIOS DE ACEPTACIÓN
==================================================

La Fase 2A se considera correcta solo si:

- migraciones nuevas corren sin errores
- seeds nuevos cargan datos coherentes
- CRUD de planes funciona
- CRUD de módulos funciona
- matriz plan vs módulos funciona
- CRUD de suscripciones funciona
- CRUD de pagos funciona
- filtros y paginación funcionan
- auditoría registra cambios
- permisos no rompen flujos existentes
- sidebar navega correctamente
- frontend mantiene coherencia visual dark SaaS premium
- el proyecto sigue sintiéndose una evolución natural de Fase 1, no una reescritura

==================================================
18. ENTREGA ESPERADA
==================================================

Trabajá así:
1. Primero resumí el plan técnico en no más de 15 ítems.
2. Después implementá directamente.
3. No te quedes en teoría.
4. No propongas otros stacks.
5. No introduzcas features fuera del alcance.
6. Al final entregá:
   - árbol de archivos creados/modificados
   - resumen de decisiones clave
   - pasos para correr migraciones/seeds
   - checklist manual de prueba de Fase 2A

==================================================
19. AGENTES / SUBAGENTES / SKILLS
==================================================

Usá agentes/subagentes si están disponibles en el entorno.
Los agentes y skills del proyecto están definidos localmente en `.claude`.

Ubicaciones:
- Agentes: `.claude/agents/`
- Skills: `.claude/skills/`

Instrucciones:
- inspeccioná la carpeta `.claude` antes de empezar
- utilizá prioritariamente los agentes y skills locales si existen
- mantené una sola línea arquitectónica en toda la implementación
- no generes decisiones incompatibles entre backend, frontend y base de datos

Subagentes/roles recomendados:
1. Arquitectura / Coordinación
2. Backend Node + Express + Knex
3. PostgreSQL + Knex migrations/seeds
4. Frontend PHP + Tailwind
5. Auth / Seguridad / Permisos
6. Documentación técnica

Skills/capacidades necesarias:
- php-web-developer
- tailwind-ui-expert
- node-api-expert
- express-knex-backend
- postgresql-database-expert
- auth-security-specialist
- technical-writer

Si alguno no existe, continuá manualmente con el rol equivalente sin bloquear el avance.

==================================================
20. DECISIONES YA TOMADAS Y NO DISCUTIBLES
==================================================

- Repo separado: `mi-tecnica-crm`
- Frontend: PHP + Tailwind
- Backend: Node + Express + Knex
- DB: PostgreSQL
- Dark SaaS premium UI
- No DevOps visual
- No tenant app
- No reescritura de Fase 1
- Trabajo por fases
- Esta entrega es Fase 2A comercial

Empezá.