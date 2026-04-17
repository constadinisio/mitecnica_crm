'use strict';

const ApiError = require('../../utils/ApiError');
const repo = require('./planRepository');
const auditService = require('../audit/auditService');

const ALLOWED_STATUS = ['active', 'inactive', 'archived'];
const ALLOWED_FREQUENCY = ['monthly', 'quarterly', 'yearly', 'custom'];

function parseArray(v) {
  if (!v) return null;
  if (Array.isArray(v)) return v;
  return String(v).split(',').map((s) => s.trim()).filter(Boolean);
}

async function listPaginated({ query, page, limit, offset }) {
  const filters = {
    status: parseArray(query.status),
    billingFrequency: query.billing_frequency || null,
    isCustom: query.is_custom === undefined || query.is_custom === '' ? null : query.is_custom === 'true' || query.is_custom === '1',
    search: query.search || null,
  };
  const sort = query.sort || 'created_at';
  const order = query.order || 'desc';
  const { rows, total } = await repo.list({ filters, sort, order, limit, offset });
  return { rows, total, page, limit };
}

async function getById(id) {
  const row = await repo.findById(id);
  if (!row) throw ApiError.notFound('Plan not found');
  return row;
}

async function create(data, { actor, ip, userAgent }) {
  if (!data.code) throw ApiError.badRequest('code is required');
  if (!data.name) throw ApiError.badRequest('name is required');
  const code = String(data.code).trim().toLowerCase();
  const existing = await repo.findByCode(code);
  if (existing) throw ApiError.conflict('Plan code already exists', { field: 'code' });

  const payload = {
    code,
    name: data.name.trim(),
    description: data.description || null,
    billing_frequency: ALLOWED_FREQUENCY.includes(data.billing_frequency) ? data.billing_frequency : 'monthly',
    price_amount: data.price_amount ?? 0,
    currency_code: (data.currency_code || 'ARS').toUpperCase(),
    status: ALLOWED_STATUS.includes(data.status) ? data.status : 'active',
    is_custom: Boolean(data.is_custom),
  };

  const row = await repo.create(payload);
  await auditService.record({
    actorUserId: actor?.userId || null,
    action: 'plan.created', entity: 'plans', entityId: row.id,
    description: `Plan "${row.name}" (${row.code}) creado`,
    afterData: row, ip, userAgent,
  });
  return row;
}

async function update(id, data, { actor, ip, userAgent }) {
  const existing = await repo.findById(id);
  if (!existing) throw ApiError.notFound('Plan not found');

  const patch = {};
  ['name', 'description', 'currency_code'].forEach((k) => {
    if (Object.prototype.hasOwnProperty.call(data, k)) patch[k] = data[k] ?? null;
  });
  if (data.billing_frequency && ALLOWED_FREQUENCY.includes(data.billing_frequency)) patch.billing_frequency = data.billing_frequency;
  if (data.price_amount !== undefined) patch.price_amount = Number(data.price_amount);
  if (data.status && ALLOWED_STATUS.includes(data.status)) patch.status = data.status;
  if (data.is_custom !== undefined) patch.is_custom = Boolean(data.is_custom);
  if (data.code && data.code !== existing.code) {
    const code = String(data.code).trim().toLowerCase();
    const dup = await repo.findByCode(code);
    if (dup) throw ApiError.conflict('Plan code already exists', { field: 'code' });
    patch.code = code;
  }

  if (Object.keys(patch).length === 0) return existing;

  const row = await repo.update(id, patch);
  await auditService.record({
    actorUserId: actor?.userId || null,
    action: 'plan.updated', entity: 'plans', entityId: id,
    description: `Plan "${row.name}" (${row.code}) actualizado`,
    beforeData: existing, afterData: row, ip, userAgent,
  });
  return row;
}

async function changeStatus(id, newStatus, { actor, ip, userAgent }) {
  if (!ALLOWED_STATUS.includes(newStatus)) throw ApiError.badRequest(`Invalid status: ${newStatus}`);
  const existing = await repo.findById(id);
  if (!existing) throw ApiError.notFound('Plan not found');
  if (existing.status === newStatus) return existing;
  const row = await repo.updateStatus(id, newStatus);
  await auditService.record({
    actorUserId: actor?.userId || null,
    action: 'plan.status_changed', entity: 'plans', entityId: id,
    description: `Plan "${row.name}" status: ${existing.status} → ${newStatus}`,
    beforeData: { status: existing.status }, afterData: { status: newStatus },
    ip, userAgent,
  });
  return row;
}

async function summary() {
  const counts = await repo.countByStatus();
  return {
    by_status: {
      active: counts.active || 0,
      inactive: counts.inactive || 0,
      archived: counts.archived || 0,
    },
    total: (counts.active || 0) + (counts.inactive || 0) + (counts.archived || 0),
  };
}

module.exports = { listPaginated, getById, create, update, changeStatus, summary, ALLOWED_STATUS, ALLOWED_FREQUENCY };
