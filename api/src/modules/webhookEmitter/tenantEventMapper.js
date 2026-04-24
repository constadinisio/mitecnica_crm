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
 * Payload base para tenant.created. Al crearse la institución en el CRM
 * aún no hay subscription/módulos — eso viaja en eventos posteriores
 * (`tenant.plan_changed` / `tenant.modules_changed`).
 *
 * @param {object} institution  row de `institutions`
 * @returns {object}
 */
function buildCreatedPayload(institution) {
  return {
    crm_id: institution.id,
    codigo: institution.subdomain,
    nombre: institution.name,
    subdomain: institution.subdomain,
    plan: null,
    modulos_activos: [],
  };
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
