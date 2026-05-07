'use strict';

/**
 * Mapeo de conceptos CRM → eventos de tenant que espera mitecnica.
 *
 * El CRM tiene 6 estados de institución; mitecnica tiene 3 comportamientos
 * prácticos (activo / suspendido / archivado). Acá colapsamos.
 *
 * Sobre el `codigo` que viaja al tenant:
 *   - El CRM usa `public_code` ('INS-2026-0001') para display humano.
 *   - mitecnica usa `codigo` como nombre lógico del tenant y lo compone en
 *     `db_name = t_${codigo}`. Necesitamos algo slug-safe y corto.
 *   - Usamos `institution.subdomain` como `codigo` (ambos derivan de slugify
 *     del nombre → queda consistente y Postgres-safe).
 */

// Traduce status CRM → "bucket" con significado para mitecnica.
const STATUS_BUCKETS = {
  trial: 'active',
  active: 'active',
  maintenance: 'active',
  suspended: 'suspended',
  expired: 'suspended',
  inactive: 'archived',
};

/**
 * Dado un cambio de status en el CRM, devuelve el evento que corresponde
 * emitir al tenant — o null si el cambio no altera el bucket efectivo
 * (ej. trial→active, suspended→expired).
 *
 * @param {string} oldStatus
 * @param {string} newStatus
 * @returns {string|null}  'tenant.suspended' | 'tenant.reactivated' | 'tenant.archived' | null
 */
function mapInstitutionStatusChange(oldStatus, newStatus) {
  const oldBucket = STATUS_BUCKETS[oldStatus] || null;
  const newBucket = STATUS_BUCKETS[newStatus] || null;
  if (!newBucket) return null;
  if (oldBucket === newBucket) return null;
  if (newBucket === 'suspended') return 'tenant.suspended';
  if (newBucket === 'archived') return 'tenant.archived';
  // newBucket === 'active' y el anterior era suspended o archived
  if (oldBucket === 'suspended' || oldBucket === 'archived') return 'tenant.reactivated';
  return null;
}

/**
 * Si la institución tiene responsible_email (o contact_email como fallback),
 * arma el bloque `admin` que el tenant usa para crear el primer usuario admin
 * + welcome email.
 *
 * Prefiere los campos separados `responsible_name` / `responsible_last_name`
 * (introducidos 2026-05-07). Si solo hay un string single-field legacy, hace
 * un split best-effort por el primer espacio — esto falla con nombres
 * compuestos pero solo aplica a registros pre-migración.
 *
 * Devuelve null si no hay email aprovechable; en ese caso el tenant queda
 * provisionado sin admin y el operador puede crearlo manualmente desde el
 * panel de tenant_admin.
 */
function buildAdminBlock(institution) {
  const email = institution.responsible_email || institution.contact_email || null;
  if (!email) return null;

  let nombre = (institution.responsible_name || '').trim();
  let apellido = (institution.responsible_last_name || '').trim();

  // Fallback legacy: si no hay last_name pero el name trae espacio, splitteamos.
  if (!apellido && nombre) {
    const firstSpace = nombre.indexOf(' ');
    if (firstSpace !== -1) {
      apellido = nombre.slice(firstSpace + 1).trim();
      nombre = nombre.slice(0, firstSpace);
    }
  }

  return {
    nombre: nombre || 'Administrador',
    apellido,
    email,
  };
}

/**
 * Payload base para tenant.created. Al crearse la institución en el CRM
 * aún no hay subscription/módulos — eso viaja en eventos posteriores
 * (`tenant.plan_changed` / `tenant.modules_changed`).
 *
 * Incluye `admin` si la institución tiene responsible_email o contact_email,
 * para que el tenant cree el primer admin y dispare welcome email
 * automáticamente (Fase 7 — onboarding automático).
 *
 * @param {object} institution  row de `institutions`
 * @returns {object}
 */
function buildCreatedPayload(institution) {
  const payload = {
    crm_id: institution.id,
    codigo: institution.subdomain,
    nombre: institution.name,
    subdomain: institution.subdomain,
    plan: null,
    modulos_activos: [],
  };
  const admin = buildAdminBlock(institution);
  if (admin) payload.admin = admin;
  return payload;
}

function buildSuspendedPayload(institution, reason = null) {
  return {
    codigo: institution.subdomain,
    motivo: reason,
  };
}

function buildReactivatedPayload(institution) {
  return { codigo: institution.subdomain };
}

function buildArchivedPayload(institution) {
  return { codigo: institution.subdomain };
}

function buildPlanChangedPayload(institution, plan) {
  return {
    codigo: institution.subdomain,
    plan: plan ? plan.code : null,
  };
}

function buildModulesChangedPayload(institution, moduleCodes) {
  return {
    codigo: institution.subdomain,
    modulos_activos: Array.isArray(moduleCodes) ? moduleCodes : [],
  };
}

/**
 * Extrae los códigos de módulos activos (effective_enabled=true) de la
 * estructura que devuelve institutionModuleService.getEffectiveModules().
 */
function extractActiveModuleCodes(effectiveModulesResult) {
  if (!effectiveModulesResult?.modules) return [];
  return effectiveModulesResult.modules
    .filter((m) => m.effective_enabled)
    .map((m) => m.module.code);
}

module.exports = {
  STATUS_BUCKETS,
  mapInstitutionStatusChange,
  buildCreatedPayload,
  buildSuspendedPayload,
  buildReactivatedPayload,
  buildArchivedPayload,
  buildPlanChangedPayload,
  buildModulesChangedPayload,
  extractActiveModuleCodes,
};
