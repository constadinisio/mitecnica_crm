'use strict';

const { db } = require('../../config/db');

const TABLE = 'contact_requests';
const ALLOWED_SORT = new Set(['id', 'status', 'institution_name', 'contact_email', 'created_at', 'updated_at']);

function baseJoin(query) {
  return query
    .leftJoin('crm_users as assignee', 'contact_requests.assigned_to_user_id', 'assignee.id')
    .leftJoin('institutions', 'contact_requests.converted_institution_id', 'institutions.id')
    .select(
      'contact_requests.*',
      'assignee.name as assigned_to_name',
      'assignee.email as assigned_to_email',
      'institutions.public_code as converted_institution_code',
      'institutions.name as converted_institution_name',
    );
}

function applyFilters(query, f = {}) {
  if (f.status) query.whereIn('contact_requests.status', Array.isArray(f.status) ? f.status : [f.status]);
  if (f.assignedTo === 'unassigned') query.whereNull('contact_requests.assigned_to_user_id');
  else if (f.assignedTo) query.where('contact_requests.assigned_to_user_id', f.assignedTo);
  if (f.search) {
    const s = `%${String(f.search).toLowerCase()}%`;
    query.where((qb) => {
      qb.whereRaw('LOWER(contact_requests.institution_name) LIKE ?', [s])
        .orWhereRaw('LOWER(contact_requests.contact_name) LIKE ?', [s])
        .orWhereRaw('LOWER(COALESCE(contact_requests.contact_last_name, \'\')) LIKE ?', [s])
        .orWhereRaw('LOWER(contact_requests.contact_email) LIKE ?', [s]);
    });
  }
  return query;
}

async function list({ filters = {}, sort = 'created_at', order = 'desc', limit = 20, offset = 0 } = {}) {
  const sortBy = ALLOWED_SORT.has(sort) ? sort : 'created_at';
  const sortDir = String(order).toLowerCase() === 'asc' ? 'asc' : 'desc';

  const countQ = applyFilters(
    db(TABLE).leftJoin('crm_users as assignee', 'contact_requests.assigned_to_user_id', 'assignee.id'),
    filters,
  );
  const [{ count }] = await countQ.count({ count: 'contact_requests.id' });

  const rows = await applyFilters(baseJoin(db(TABLE)), filters)
    .orderBy(`contact_requests.${sortBy}`, sortDir)
    .limit(limit)
    .offset(offset);
  return { rows, total: Number(count) };
}

async function findById(id) {
  return baseJoin(db(TABLE)).where('contact_requests.id', id).first();
}

async function create(data) {
  const [row] = await db(TABLE).insert(data).returning('*');
  return row;
}

async function update(id, data) {
  const [row] = await db(TABLE).where({ id }).update({ ...data, updated_at: db.fn.now() }).returning('*');
  return row;
}

async function countByStatus() {
  const rows = await db(TABLE).select('status').count({ count: '*' }).groupBy('status');
  const out = {};
  rows.forEach((r) => { out[r.status] = Number(r.count); });
  return out;
}

async function listRecent(limit = 6) {
  return baseJoin(db(TABLE))
    .whereIn('contact_requests.status', ['new', 'contacted', 'in_negotiation'])
    .orderBy('contact_requests.created_at', 'desc')
    .limit(limit);
}

module.exports = { list, findById, create, update, countByStatus, listRecent };
