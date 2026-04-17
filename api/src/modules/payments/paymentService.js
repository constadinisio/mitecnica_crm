'use strict';

const ApiError = require('../../utils/ApiError');
const repo = require('./paymentRepository');
const subsRepo = require('../subscriptions/subscriptionRepository');
const auditService = require('../audit/auditService');

const ALLOWED_STATUS = ['pending', 'approved', 'rejected', 'expired', 'canceled'];

function parseArray(v) {
  if (!v) return null;
  if (Array.isArray(v)) return v;
  return String(v).split(',').map((s) => s.trim()).filter(Boolean);
}

function parseDate(value) {
  if (!value) return null;
  const d = new Date(value);
  return Number.isNaN(d.getTime()) ? null : d;
}

async function listPaginated({ query, page, limit, offset }) {
  const filters = {
    institutionId: query.institution_id ? Number(query.institution_id) : null,
    subscriptionId: query.subscription_id ? Number(query.subscription_id) : null,
    status: parseArray(query.status),
    paymentMethod: query.payment_method || null,
    from: parseDate(query.from),
    to: parseDate(query.to),
    search: query.search || null,
  };
  const sort = query.sort || 'payment_date';
  const order = query.order || 'desc';
  const { rows, total } = await repo.list({ filters, sort, order, limit, offset });
  return { rows, total, page, limit };
}

async function getById(id) {
  const row = await repo.findById(id);
  if (!row) throw ApiError.notFound('Payment not found');
  return row;
}

async function create(data, { actor, ip, userAgent }) {
  if (!data.institution_id) throw ApiError.badRequest('institution_id is required');
  if (data.amount === undefined || data.amount === null) throw ApiError.badRequest('amount is required');

  if (data.subscription_id) {
    const sub = await subsRepo.findById(data.subscription_id);
    if (!sub) throw ApiError.badRequest('Subscription not found');
    if (sub.institution_id !== Number(data.institution_id)) {
      throw ApiError.badRequest('Subscription does not belong to this institution');
    }
  }

  const payload = {
    institution_id: data.institution_id,
    subscription_id: data.subscription_id || null,
    amount: Number(data.amount),
    currency_code: (data.currency_code || 'ARS').toUpperCase(),
    payment_date: data.payment_date || new Date().toISOString(),
    status: ALLOWED_STATUS.includes(data.status) ? data.status : 'pending',
    payment_method: data.payment_method || null,
    reference_code: data.reference_code || null,
    notes: data.notes || null,
    created_by_user_id: actor?.userId || null,
  };

  const row = await repo.create(payload);
  await auditService.record({
    actorUserId: actor?.userId || null,
    action: 'payment.created', entity: 'payments', entityId: row.id,
    description: `Pago #${row.id} registrado (${row.currency_code} ${row.amount}) — institución ${row.institution_id}`,
    afterData: row, ip, userAgent,
  });
  return row;
}

async function update(id, data, { actor, ip, userAgent }) {
  const existing = await repo.findById(id);
  if (!existing) throw ApiError.notFound('Payment not found');

  const patch = {};
  if (data.amount !== undefined) patch.amount = Number(data.amount);
  if (data.currency_code !== undefined) patch.currency_code = String(data.currency_code).toUpperCase();
  if (data.payment_date !== undefined) patch.payment_date = data.payment_date;
  if (data.status && ALLOWED_STATUS.includes(data.status)) patch.status = data.status;
  ['payment_method', 'reference_code', 'notes'].forEach((k) => {
    if (Object.prototype.hasOwnProperty.call(data, k)) patch[k] = data[k] ?? null;
  });
  if (data.subscription_id !== undefined) {
    if (data.subscription_id === null) {
      patch.subscription_id = null;
    } else {
      const sub = await subsRepo.findById(data.subscription_id);
      if (!sub) throw ApiError.badRequest('Subscription not found');
      if (sub.institution_id !== existing.institution_id) throw ApiError.badRequest('Subscription does not belong to this institution');
      patch.subscription_id = data.subscription_id;
    }
  }

  if (Object.keys(patch).length === 0) return existing;

  const row = await repo.update(id, patch);
  await auditService.record({
    actorUserId: actor?.userId || null,
    action: 'payment.updated', entity: 'payments', entityId: id,
    description: `Pago #${id} actualizado`,
    beforeData: existing, afterData: row, ip, userAgent,
  });
  return row;
}

async function changeStatus(id, newStatus, { actor, ip, userAgent, reason = null }) {
  if (!ALLOWED_STATUS.includes(newStatus)) throw ApiError.badRequest(`Invalid status: ${newStatus}`);
  const existing = await repo.findById(id);
  if (!existing) throw ApiError.notFound('Payment not found');
  if (existing.status === newStatus) return existing;
  const row = await repo.updateStatus(id, newStatus);
  await auditService.record({
    actorUserId: actor?.userId || null,
    action: 'payment.status_changed', entity: 'payments', entityId: id,
    description: `Pago #${id} status: ${existing.status} → ${newStatus}${reason ? ` (${reason})` : ''}`,
    beforeData: { status: existing.status }, afterData: { status: newStatus, reason },
    ip, userAgent,
  });
  return row;
}

async function summary() {
  const counts = await repo.countByStatus();
  // Últimos 30 días para el KPI de cobro
  const from30d = new Date();
  from30d.setDate(from30d.getDate() - 30);

  const totals30d = await repo.totalsByStatus({ from: from30d });
  const totalsAll = await repo.totalsByStatus();
  const recent = await repo.listRecent(6);

  return {
    counts: {
      total: Object.values(counts).reduce((a, b) => a + b, 0),
      by_status: {
        pending: counts.pending || 0,
        approved: counts.approved || 0,
        rejected: counts.rejected || 0,
        expired: counts.expired || 0,
        canceled: counts.canceled || 0,
      },
    },
    totals_all: totalsAll,
    totals_last_30d: totals30d,
    recent,
  };
}

module.exports = { listPaginated, getById, create, update, changeStatus, summary, ALLOWED_STATUS };
