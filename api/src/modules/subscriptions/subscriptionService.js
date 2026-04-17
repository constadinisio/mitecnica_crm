'use strict';

const ApiError = require('../../utils/ApiError');
const repo = require('./subscriptionRepository');
const plansRepo = require('../plans/planRepository');
const auditService = require('../audit/auditService');

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
