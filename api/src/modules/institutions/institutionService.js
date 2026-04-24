'use strict';

const ApiError = require('../../utils/ApiError');
const { slugify } = require('../../utils/slug');
const repo = require('./institutionRepository');
const auditService = require('../audit/auditService');
const webhookEmitter = require('../webhookEmitter/webhookEmitterService');
const tenantMapper = require('../webhookEmitter/tenantEventMapper');
const logger = require('../../config/logger');

const ALLOWED_STATUS = ['trial', 'active', 'maintenance', 'suspended', 'expired', 'inactive'];
const ALLOWED_TECH_STATUS = ['pending', 'optimal', 'updating', 'offline'];

function parseArrayFilter(value) {
  if (!value) return null;
  if (Array.isArray(value)) return value.filter(Boolean);
  return String(value).split(',').map((s) => s.trim()).filter(Boolean);
}

async function listPaginated({ query, page, limit, offset }) {
  const filters = {
    search: query.search || null,
    status: parseArrayFilter(query.status),
    technicalStatus: parseArrayFilter(query.technical_status),
    plan: query.plan || null,
    expirationFrom: query.expiration_from || null,
    expirationTo: query.expiration_to || null,
  };
  const sort = query.sort || 'created_at';
  const order = query.order || 'desc';
  const { rows, total } = await repo.list({ filters, sort, order, limit, offset });
  return { rows, total, page, limit };
}

async function getById(id) {
  const row = await repo.findById(id);
  if (!row) throw ApiError.notFound('Institution not found');
  return row;
}

async function ensureUniqueSubdomain(subdomain, excludeId = null) {
  const existing = await repo.findBySubdomain(subdomain);
  if (existing && existing.id !== excludeId) {
    throw ApiError.conflict('Subdomain already in use', { field: 'subdomain' });
  }
}

async function ensureUniqueSlug(slug, excludeId = null) {
  const existing = await repo.findBySlug(slug);
  if (existing && existing.id !== excludeId) {
    throw ApiError.conflict('Slug already in use', { field: 'slug' });
  }
}

async function buildSlugCandidate(baseName) {
  const base = slugify(baseName);
  if (!base) return base;
  let candidate = base;
  let i = 2;
  while (await repo.findBySlug(candidate)) {
    candidate = `${base}-${i}`;
    i += 1;
    if (i > 100) break;
  }
  return candidate;
}

function normalizeSubdomain(input) {
  return slugify(input, { maxLength: 60 });
}

async function create(data, { actor, ip, userAgent }) {
  if (!data.name) throw ApiError.badRequest('name is required');
  if (!data.contact_email) throw ApiError.badRequest('contact_email is required');

  const slug = await buildSlugCandidate(data.slug || data.name);
  const subdomain = normalizeSubdomain(data.subdomain || slug);
  if (!subdomain) throw ApiError.badRequest('subdomain cannot be empty');

  await ensureUniqueSlug(slug);
  await ensureUniqueSubdomain(subdomain);

  const publicCode = data.public_code || await repo.nextPublicCode();

  const toInsert = {
    public_code: publicCode,
    name: data.name,
    slug,
    subdomain,
    status: data.status && ALLOWED_STATUS.includes(data.status) ? data.status : 'trial',
    technical_status: data.technical_status && ALLOWED_TECH_STATUS.includes(data.technical_status) ? data.technical_status : 'pending',
    contact_email: data.contact_email,
    contact_phone: data.contact_phone || null,
    address: data.address || null,
    responsible_name: data.responsible_name || null,
    responsible_email: data.responsible_email || null,
    notes_internal: data.notes_internal || null,
    current_plan_name: data.current_plan_name || null,
    expiration_date: data.expiration_date || null,
  };

  const row = await repo.create(toInsert);

  await auditService.record({
    actorUserId: actor?.userId || null,
    action: 'institution.created',
    entity: 'institutions',
    entityId: row.id,
    description: `Institution "${row.name}" (${row.public_code}) created`,
    afterData: row,
    ip,
    userAgent,
  });

  // Notificar a la tenant app. Fire-and-forget hacia el outbox: si el enqueue
  // falla no queremos bloquear la creación de la institución (el reconciliador
  // diario es el safety net). Si el enqueue persiste, el dispatcher hace retry.
  try {
    await webhookEmitter.enqueue({
      event: 'tenant.created',
      payload: tenantMapper.buildCreatedPayload(row),
    });
  } catch (err) {
    logger.error('[institutionService] enqueue tenant.created falló: %s', err.message);
  }

  return row;
}

async function update(id, data, { actor, ip, userAgent }) {
  const existing = await repo.findById(id);
  if (!existing) throw ApiError.notFound('Institution not found');

  const patch = {};
  const editableText = ['name', 'contact_email', 'contact_phone', 'address', 'responsible_name', 'responsible_email', 'notes_internal', 'current_plan_name'];
  editableText.forEach((k) => {
    if (Object.prototype.hasOwnProperty.call(data, k)) patch[k] = data[k] ?? null;
  });
  if (Object.prototype.hasOwnProperty.call(data, 'expiration_date')) {
    patch.expiration_date = data.expiration_date || null;
  }
  if (Object.prototype.hasOwnProperty.call(data, 'subdomain') && data.subdomain) {
    const sub = normalizeSubdomain(data.subdomain);
    await ensureUniqueSubdomain(sub, id);
    patch.subdomain = sub;
  }
  if (Object.prototype.hasOwnProperty.call(data, 'slug') && data.slug) {
    const slug = slugify(data.slug);
    await ensureUniqueSlug(slug, id);
    patch.slug = slug;
  }
  if (Object.prototype.hasOwnProperty.call(data, 'status') && ALLOWED_STATUS.includes(data.status)) {
    patch.status = data.status;
  }
  if (Object.prototype.hasOwnProperty.call(data, 'technical_status') && ALLOWED_TECH_STATUS.includes(data.technical_status)) {
    patch.technical_status = data.technical_status;
  }

  if (Object.keys(patch).length === 0) return existing;

  const row = await repo.update(id, patch);
  await auditService.record({
    actorUserId: actor?.userId || null,
    action: 'institution.updated',
    entity: 'institutions',
    entityId: id,
    description: `Institution "${row.name}" (${row.public_code}) updated`,
    beforeData: existing,
    afterData: row,
    ip,
    userAgent,
  });
  return row;
}

async function changeStatus(id, newStatus, { actor, ip, userAgent, reason = null }) {
  if (!ALLOWED_STATUS.includes(newStatus)) throw ApiError.badRequest(`Invalid status: ${newStatus}`);
  const existing = await repo.findById(id);
  if (!existing) throw ApiError.notFound('Institution not found');
  if (existing.status === newStatus) return existing;

  const row = await repo.updateStatus(id, newStatus);
  await auditService.record({
    actorUserId: actor?.userId || null,
    action: 'institution.status_changed',
    entity: 'institutions',
    entityId: id,
    description: `Institution "${row.name}" status: ${existing.status} → ${newStatus}${reason ? ` (${reason})` : ''}`,
    beforeData: { status: existing.status },
    afterData: { status: newStatus, reason },
    ip,
    userAgent,
  });

  // Mapear el cambio de status CRM al evento tenant correspondiente.
  // Ej: trial→active no emite nada (ambos son "activo" para mitecnica).
  const eventName = tenantMapper.mapInstitutionStatusChange(existing.status, newStatus);
  if (eventName) {
    const payloadBuilder = {
      'tenant.suspended': () => tenantMapper.buildSuspendedPayload(row, reason),
      'tenant.reactivated': () => tenantMapper.buildReactivatedPayload(row),
      'tenant.archived': () => tenantMapper.buildArchivedPayload(row),
    }[eventName];
    try {
      await webhookEmitter.enqueue({ event: eventName, payload: payloadBuilder() });
    } catch (err) {
      logger.error('[institutionService] enqueue %s falló: %s', eventName, err.message);
    }
  }

  return row;
}

module.exports = {
  listPaginated,
  getById,
  create,
  update,
  changeStatus,
  ALLOWED_STATUS,
  ALLOWED_TECH_STATUS,
};
