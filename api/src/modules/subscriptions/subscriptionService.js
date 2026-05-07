'use strict';

const ApiError = require('../../utils/ApiError');
const repo = require('./subscriptionRepository');
const plansRepo = require('../plans/planRepository');
const institutionsRepo = require('../institutions/institutionRepository');
const auditService = require('../audit/auditService');
const webhookEmitter = require('../webhookEmitter/webhookEmitterService');
const tenantMapper = require('../webhookEmitter/tenantEventMapper');
const logger = require('../../config/logger');

const ALLOWED_STATUS = ['trial', 'active', 'suspended', 'expired', 'canceled'];
const ALLOWED_RENEWAL = ['manual', 'automatic'];
const LIVE_STATUSES = repo.LIVE_STATUSES;

function parseArray(v) {
  if (!v) return null;
  if (Array.isArray(v)) return v;
  return String(v).split(',').map((s) => s.trim()).filter(Boolean);
}

async function listPaginated({ query, page, limit, offset }) {
  const filters = {
    institutionId: query.institution_id ? Number(query.institution_id) : null,
    planId: query.plan_id ? Number(query.plan_id) : null,
    status: parseArray(query.status),
    renewalMode: query.renewal_mode || null,
    search: query.search || null,
  };
  const sort = query.sort || 'created_at';
  const order = query.order || 'desc';
  const { rows, total } = await repo.list({ filters, sort, order, limit, offset });
  return { rows, total, page, limit };
}

async function getById(id) {
  const row = await repo.findById(id);
  if (!row) throw ApiError.notFound('Subscription not found');
  return row;
}

async function ensureNoLiveConflict({ institutionId, excludeId = null, desiredStatus }) {
  if (!LIVE_STATUSES.includes(desiredStatus)) return;
  const live = await repo.findLiveForInstitution(institutionId, excludeId);
  if (live) {
    throw ApiError.conflict(
      `Institution already has a live subscription (#${live.id}, status ${live.status})`,
      { field: 'institution_id', existing_subscription_id: live.id },
    );
  }
}

/**
 * Sincroniza los campos cacheados `institutions.current_plan_name` y
 * `institutions.expiration_date` con la suscripción LIVE más reciente.
 *
 * Estos campos los seguimos manteniendo para que el listado de instituciones
 * y el CSV puedan filtrar/mostrar el plan sin joinear contra subscriptions.
 * El form de Institución ya no los edita (se quitaron 2026-05-07): la única
 * fuente de verdad es la suscripción, y esto hace fan-out hacia el cache.
 */
async function syncInstitutionLicenseSummary(institutionId) {
  try {
    const live = await repo.findLiveForInstitution(institutionId);
    let planName = null;
    let expirationDate = null;
    if (live) {
      const plan = await plansRepo.findById(live.plan_id);
      planName = plan ? plan.name : null;
      expirationDate = live.end_date || null;
    }
    await institutionsRepo.update(institutionId, {
      current_plan_name: planName,
      expiration_date: expirationDate,
    });
  } catch (err) {
    logger.error(
      '[subscriptionService] syncInstitutionLicenseSummary falló para institution=%d: %s',
      institutionId, err.message
    );
  }
}

/**
 * Notifica al tenant que cambió el plan y/o los módulos efectivos. Lo usamos
 * tanto al crear/actualizar suscripción como al cambiar status. Encolamos dos
 * eventos separados porque del lado mitecnica son dos campos distintos en
 * `control.tenants` (plan + modulos_activos).
 *
 * Importamos institutionModuleService lazy para evitar ciclos de require
 * (el módulo ya pulla de subscriptionRepo).
 */
async function emitPlanAndModulesChanged(institutionId) {
  try {
    const institution = await institutionsRepo.findById(institutionId);
    if (!institution) return;

    // require lazy — safe porque este code path corre post-boot.
    const institutionModuleService = require('../institutionModules/institutionModuleService');
    const [live, effective] = await Promise.all([
      repo.findLiveForInstitution(institutionId),
      institutionModuleService.getEffectiveModules(institutionId).catch(() => null),
    ]);

    const plan = live ? await plansRepo.findById(live.plan_id) : null;
    const moduleCodes = effective ? tenantMapper.extractActiveModuleCodes(effective) : [];

    await Promise.all([
      webhookEmitter.enqueue({
        event: 'tenant.plan_changed',
        payload: tenantMapper.buildPlanChangedPayload(institution, plan),
      }),
      webhookEmitter.enqueue({
        event: 'tenant.modules_changed',
        payload: tenantMapper.buildModulesChangedPayload(institution, moduleCodes),
      }),
    ]);
  } catch (err) {
    logger.error(
      '[subscriptionService] emitPlanAndModulesChanged falló para institution=%d: %s',
      institutionId, err.message
    );
  }
}

async function create(data, { actor, ip, userAgent }) {
  if (!data.institution_id) throw ApiError.badRequest('institution_id is required');
  if (!data.plan_id) throw ApiError.badRequest('plan_id is required');
  if (!data.start_date) throw ApiError.badRequest('start_date is required');

  const plan = await plansRepo.findById(data.plan_id);
  if (!plan) throw ApiError.badRequest('Plan not found');

  const status = ALLOWED_STATUS.includes(data.status) ? data.status : 'trial';
  await ensureNoLiveConflict({ institutionId: data.institution_id, desiredStatus: status });

  const payload = {
    institution_id: data.institution_id,
    plan_id: data.plan_id,
    status,
    start_date: data.start_date,
    end_date: data.end_date || null,
    trial_ends_at: data.trial_ends_at || null,
    renewal_mode: ALLOWED_RENEWAL.includes(data.renewal_mode) ? data.renewal_mode : 'manual',
    billing_notes: data.billing_notes || null,
  };

  const row = await repo.create(payload);
  await auditService.record({
    actorUserId: actor?.userId || null,
    action: 'subscription.created', entity: 'subscriptions', entityId: row.id,
    description: `Suscripción creada para institución ${row.institution_id} (plan "${plan.name}")`,
    afterData: row, ip, userAgent,
  });

  await syncInstitutionLicenseSummary(row.institution_id);

  // Si la suscripción nació "live" (trial|active), ya cambia lo que el tenant ve.
  if (LIVE_STATUSES.includes(status)) {
    await emitPlanAndModulesChanged(row.institution_id);
  }
  return row;
}

async function update(id, data, { actor, ip, userAgent }) {
  const existing = await repo.findById(id);
  if (!existing) throw ApiError.notFound('Subscription not found');

  const patch = {};
  if (data.plan_id && data.plan_id !== existing.plan_id) {
    const plan = await plansRepo.findById(data.plan_id);
    if (!plan) throw ApiError.badRequest('Plan not found');
    patch.plan_id = data.plan_id;
  }
  if (data.start_date) patch.start_date = data.start_date;
  if (data.end_date !== undefined) patch.end_date = data.end_date || null;
  if (data.trial_ends_at !== undefined) patch.trial_ends_at = data.trial_ends_at || null;
  if (data.renewal_mode && ALLOWED_RENEWAL.includes(data.renewal_mode)) patch.renewal_mode = data.renewal_mode;
  if (data.billing_notes !== undefined) patch.billing_notes = data.billing_notes || null;
  if (data.status && ALLOWED_STATUS.includes(data.status) && data.status !== existing.status) {
    await ensureNoLiveConflict({ institutionId: existing.institution_id, excludeId: id, desiredStatus: data.status });
    patch.status = data.status;
  }

  if (Object.keys(patch).length === 0) return existing;

  const row = await repo.update(id, patch);
  await auditService.record({
    actorUserId: actor?.userId || null,
    action: 'subscription.updated', entity: 'subscriptions', entityId: id,
    description: `Suscripción #${id} actualizada`,
    beforeData: existing, afterData: row, ip, userAgent,
  });

  // Sincronizar el cache de licencia en la institution si cambió algo que afecte
  // al plan vigente o la fecha de vencimiento.
  if (patch.plan_id || patch.status || patch.end_date !== undefined) {
    await syncInstitutionLicenseSummary(row.institution_id);
  }

  // Si cambió el plan o si el status cambió de/hacia "live", los módulos
  // efectivos pueden haber cambiado → notificar.
  const planChanged = patch.plan_id && patch.plan_id !== existing.plan_id;
  const liveChanged =
    patch.status && LIVE_STATUSES.includes(patch.status) !== LIVE_STATUSES.includes(existing.status);
  if (planChanged || liveChanged) {
    await emitPlanAndModulesChanged(row.institution_id);
  }
  return row;
}

async function changeStatus(id, newStatus, { actor, ip, userAgent, reason = null }) {
  if (!ALLOWED_STATUS.includes(newStatus)) throw ApiError.badRequest(`Invalid status: ${newStatus}`);
  const existing = await repo.findById(id);
  if (!existing) throw ApiError.notFound('Subscription not found');
  if (existing.status === newStatus) return existing;

  await ensureNoLiveConflict({ institutionId: existing.institution_id, excludeId: id, desiredStatus: newStatus });

  const row = await repo.updateStatus(id, newStatus);
  await auditService.record({
    actorUserId: actor?.userId || null,
    action: 'subscription.status_changed', entity: 'subscriptions', entityId: id,
    description: `Suscripción #${id} status: ${existing.status} → ${newStatus}${reason ? ` (${reason})` : ''}`,
    beforeData: { status: existing.status },
    afterData: { status: newStatus, reason },
    ip, userAgent,
  });

  // Sincronizar cache de plan/vencimiento en la institution.
  await syncInstitutionLicenseSummary(existing.institution_id);

  // Un cambio de status puede pasar la subscription de "live" a "no live" o viceversa,
  // lo que altera el plan/módulos efectivos del lado tenant.
  const wasLive = LIVE_STATUSES.includes(existing.status);
  const isLive = LIVE_STATUSES.includes(newStatus);
  if (wasLive !== isLive) {
    await emitPlanAndModulesChanged(existing.institution_id);
  }
  return row;
}

async function summary() {
  const counts = await repo.countByStatus();
  const upcoming = await repo.upcomingExpirations({ days: 30, limit: 8 });
  const total = Object.values(counts).reduce((a, b) => a + b, 0);
  return {
    counts: {
      total,
      by_status: {
        trial: counts.trial || 0,
        active: counts.active || 0,
        suspended: counts.suspended || 0,
        expired: counts.expired || 0,
        canceled: counts.canceled || 0,
      },
      live: (counts.trial || 0) + (counts.active || 0),
    },
    upcoming_renewals: upcoming,
  };
}

module.exports = { listPaginated, getById, create, update, changeStatus, summary, ALLOWED_STATUS, ALLOWED_RENEWAL };
