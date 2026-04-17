'use strict';

const repo = require('./auditRepository');
const logger = require('../../config/logger');

async function record({
  actorUserId = null,
  action,
  entity,
  entityId = null,
  description = null,
  beforeData = null,
  afterData = null,
  ip = null,
  userAgent = null,
} = {}) {
  if (!action || !entity) throw new Error('audit: action and entity are required');
  try {
    return await repo.create({
      actor_user_id: actorUserId,
      action,
      entity,
      entity_id: entityId ? String(entityId) : null,
      description,
      before_data: beforeData,
      after_data: afterData,
      ip,
      user_agent: userAgent,
    });
  } catch (err) {
    logger.error('[audit] failed to record %s %s: %s', action, entity, err.message);
    return null;
  }
}

function parseDate(value) {
  if (!value) return null;
  const d = new Date(value);
  return Number.isNaN(d.getTime()) ? null : d;
}

async function listPaginated({ query = {}, page, limit, offset }) {
  const filters = {
    actorUserId: query.actor_user_id ? Number(query.actor_user_id) : null,
    action: query.action || null,
    entity: query.entity || null,
    entityId: query.entity_id || null,
    from: parseDate(query.from),
    to: parseDate(query.to),
    search: query.search || null,
  };
  const sort = query.sort || 'created_at';
  const order = query.order || 'desc';
  const { rows, total } = await repo.list({ filters, sort, order, limit, offset });
  return { rows, total, page, limit };
}

async function getById(id) {
  return repo.findById(id);
}

async function listRecent(limit = 8) {
  return repo.listRecent(limit);
}

module.exports = { record, listPaginated, getById, listRecent };
