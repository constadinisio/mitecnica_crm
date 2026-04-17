'use strict';

const ApiError = require('../../utils/ApiError');
const repo = require('./moduleCatalogRepository');
const auditService = require('../audit/auditService');

const ALLOWED_STATUS = ['active', 'inactive'];
const ALLOWED_CATEGORIES = ['academic', 'communication', 'administration', 'technical', 'analytics', 'other'];

function parseArray(v) {
  if (!v) return null;
  if (Array.isArray(v)) return v;
  return String(v).split(',').map((s) => s.trim()).filter(Boolean);
}

async function listPaginated({ query, page, limit, offset }) {
  const filters = {
    status: parseArray(query.status),
    category: query.category || null,
    isCore: query.is_core === undefined || query.is_core === '' ? null : query.is_core === 'true' || query.is_core === '1',
    search: query.search || null,
  };
  const sort = query.sort || 'name';
  const order = query.order || 'asc';
  const { rows, total } = await repo.list({ filters, sort, order, limit, offset });
  return { rows, total, page, limit };
}

async function getById(id) {
  const row = await repo.findById(id);
  if (!row) throw ApiError.notFound('Module not found');
  return row;
}

async function create(data, { actor, ip, userAgent }) {
  if (!data.code) throw ApiError.badRequest('code is required');
  if (!data.name) throw ApiError.badRequest('name is required');
  const code = String(data.code).trim().toLowerCase();
  if (await repo.findByCode(code)) throw ApiError.conflict('Module code already exists', { field: 'code' });

  const payload = {
    code,
    name: data.name.trim(),
    description: data.description || null,
    category: ALLOWED_CATEGORIES.includes(data.category) ? data.category : null,
    status: ALLOWED_STATUS.includes(data.status) ? data.status : 'active',
    is_core: Boolean(data.is_core),
  };
  const row = await repo.create(payload);
  await auditService.record({
    actorUserId: actor?.userId || null,
    action: 'module.created', entity: 'modules_catalog', entityId: row.id,
    description: `Módulo "${row.name}" (${row.code}) creado`,
    afterData: row, ip, userAgent,
  });
  return row;
}

async function update(id, data, { actor, ip, userAgent }) {
  const existing = await repo.findById(id);
  if (!existing) throw ApiError.notFound('Module not found');

  const patch = {};
  ['name', 'description'].forEach((k) => {
    if (Object.prototype.hasOwnProperty.call(data, k)) patch[k] = data[k] ?? null;
  });
  if (data.category !== undefined) patch.category = ALLOWED_CATEGORIES.includes(data.category) ? data.category : null;
  if (data.status && ALLOWED_STATUS.includes(data.status)) patch.status = data.status;
  if (data.is_core !== undefined) patch.is_core = Boolean(data.is_core);
  if (data.code && data.code !== existing.code) {
    const code = String(data.code).trim().toLowerCase();
    if (await repo.findByCode(code)) throw ApiError.conflict('Module code already exists', { field: 'code' });
    patch.code = code;
  }
  if (Object.keys(patch).length === 0) return existing;

  const row = await repo.update(id, patch);
  await auditService.record({
    actorUserId: actor?.userId || null,
    action: 'module.updated', entity: 'modules_catalog', entityId: id,
    description: `Módulo "${row.name}" (${row.code}) actualizado`,
    beforeData: existing, afterData: row, ip, userAgent,
  });
  return row;
}

async function changeStatus(id, newStatus, { actor, ip, userAgent }) {
  if (!ALLOWED_STATUS.includes(newStatus)) throw ApiError.badRequest(`Invalid status: ${newStatus}`);
  const existing = await repo.findById(id);
  if (!existing) throw ApiError.notFound('Module not found');
  if (existing.status === newStatus) return existing;
  const row = await repo.updateStatus(id, newStatus);
  await auditService.record({
    actorUserId: actor?.userId || null,
    action: 'module.status_changed', entity: 'modules_catalog', entityId: id,
    description: `Módulo "${row.name}" status: ${existing.status} → ${newStatus}`,
    beforeData: { status: existing.status }, afterData: { status: newStatus },
    ip, userAgent,
  });
  return row;
}

module.exports = { listPaginated, getById, create, update, changeStatus, ALLOWED_STATUS, ALLOWED_CATEGORIES };
