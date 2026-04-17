'use strict';

const { db } = require('../../config/db');

const TABLE = 'audit_logs';

const ALLOWED_SORT = new Set(['created_at', 'action', 'entity']);

async function create(row) {
  const [inserted] = await db(TABLE).insert(row).returning('*');
  return inserted;
}

async function findById(id) {
  return db(TABLE)
    .leftJoin('crm_users', 'audit_logs.actor_user_id', 'crm_users.id')
    .select(
      'audit_logs.*',
      'crm_users.name as actor_name',
      'crm_users.email as actor_email',
    )
    .where('audit_logs.id', id)
    .first();
}

function applyFilters(query, f = {}) {
  if (f.actorUserId) query.where('audit_logs.actor_user_id', f.actorUserId);
  if (f.action) query.where('audit_logs.action', f.action);
  if (f.entity) query.where('audit_logs.entity', f.entity);
  if (f.entityId) query.where('audit_logs.entity_id', String(f.entityId));
  if (f.from) query.where('audit_logs.created_at', '>=', f.from);
  if (f.to) query.where('audit_logs.created_at', '<=', f.to);
  if (f.search) {
    const s = `%${f.search.toLowerCase()}%`;
    query.where((qb) => {
      qb.whereRaw('LOWER(audit_logs.description) LIKE ?', [s])
        .orWhereRaw('LOWER(audit_logs.action) LIKE ?', [s])
        .orWhereRaw('LOWER(audit_logs.entity) LIKE ?', [s]);
    });
  }
  return query;
}

async function list({ filters = {}, sort = 'created_at', order = 'desc', limit = 20, offset = 0 } = {}) {
  const sortBy = ALLOWED_SORT.has(sort) ? sort : 'created_at';
  const sortDir = String(order).toLowerCase() === 'asc' ? 'asc' : 'desc';

  const countQ = applyFilters(db(TABLE), filters);
  const [{ count }] = await countQ.count({ count: '*' });

  const rowsQ = applyFilters(db(TABLE), filters)
    .leftJoin('crm_users', 'audit_logs.actor_user_id', 'crm_users.id')
    .select(
      'audit_logs.*',
      'crm_users.name as actor_name',
      'crm_users.email as actor_email',
    )
    .orderBy(`audit_logs.${sortBy}`, sortDir)
    .limit(limit)
    .offset(offset);

  const rows = await rowsQ;
  return { rows, total: Number(count) };
}

async function listRecent(limit = 10) {
  return db(TABLE)
    .leftJoin('crm_users', 'audit_logs.actor_user_id', 'crm_users.id')
    .select(
      'audit_logs.*',
      'crm_users.name as actor_name',
      'crm_users.email as actor_email',
    )
    .orderBy('audit_logs.created_at', 'desc')
    .limit(limit);
}

module.exports = { create, findById, list, listRecent };
