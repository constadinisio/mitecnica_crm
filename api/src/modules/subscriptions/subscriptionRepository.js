'use strict';

const { db } = require('../../config/db');

const TABLE = 'subscriptions';
const ALLOWED_SORT = new Set(['id', 'status', 'start_date', 'end_date', 'trial_ends_at', 'renewal_mode', 'created_at', 'updated_at']);
const LIVE_STATUSES = ['trial', 'active'];

function baseJoin(query) {
  return query
    .leftJoin('institutions', 'subscriptions.institution_id', 'institutions.id')
    .leftJoin('plans', 'subscriptions.plan_id', 'plans.id')
    .select(
      'subscriptions.*',
      'institutions.name as institution_name',
      'institutions.public_code as institution_code',
      'plans.name as plan_name',
      'plans.code as plan_code',
      'plans.price_amount as plan_price_amount',
      'plans.currency_code as plan_currency_code',
      'plans.billing_frequency as plan_billing_frequency',
    );
}

function applyFilters(query, f = {}) {
  if (f.institutionId) query.where('subscriptions.institution_id', f.institutionId);
  if (f.planId) query.where('subscriptions.plan_id', f.planId);
  if (f.status) query.whereIn('subscriptions.status', Array.isArray(f.status) ? f.status : [f.status]);
  if (f.renewalMode) query.where('subscriptions.renewal_mode', f.renewalMode);
  if (f.search) {
    const s = `%${String(f.search).toLowerCase()}%`;
    query.where((qb) => {
      qb.whereRaw('LOWER(institutions.name) LIKE ?', [s])
        .orWhereRaw('LOWER(institutions.public_code) LIKE ?', [s])
        .orWhereRaw('LOWER(plans.name) LIKE ?', [s])
        .orWhereRaw('LOWER(plans.code) LIKE ?', [s]);
    });
  }
  return query;
}

async function list({ filters = {}, sort = 'created_at', order = 'desc', limit = 20, offset = 0 } = {}) {
  const sortBy = ALLOWED_SORT.has(sort) ? sort : 'created_at';
  const sortDir = String(order).toLowerCase() === 'asc' ? 'asc' : 'desc';

  const countQ = applyFilters(
    db(TABLE).leftJoin('institutions', 'subscriptions.institution_id', 'institutions.id')
             .leftJoin('plans', 'subscriptions.plan_id', 'plans.id'),
    filters,
  );
  const [{ count }] = await countQ.count({ count: 'subscriptions.id' });

  const rows = await applyFilters(baseJoin(db(TABLE)), filters)
    .orderBy(`subscriptions.${sortBy}`, sortDir)
    .limit(limit)
    .offset(offset);

  return { rows, total: Number(count) };
}

async function findById(id) {
  return baseJoin(db(TABLE)).where('subscriptions.id', id).first();
}

async function findLiveForInstitution(institutionId, excludeId = null) {
  const q = db(TABLE)
    .where('institution_id', institutionId)
    .whereIn('status', LIVE_STATUSES);
  if (excludeId) q.whereNot('id', excludeId);
  return q.first();
}

async function create(data) {
  const [row] = await db(TABLE).insert(data).returning('*');
  return row;
}

async function update(id, data) {
  const [row] = await db(TABLE).where({ id }).update({ ...data, updated_at: db.fn.now() }).returning('*');
  return row;
}

async function updateStatus(id, status) {
  const [row] = await db(TABLE).where({ id }).update({ status, updated_at: db.fn.now() }).returning('*');
  return row;
}

async function countByStatus() {
  const rows = await db(TABLE).select('status').count({ count: '*' }).groupBy('status');
  const out = {};
  rows.forEach((r) => { out[r.status] = Number(r.count); });
  return out;
}

async function listActiveForInstitution(institutionId) {
  return baseJoin(db(TABLE))
    .where('subscriptions.institution_id', institutionId)
    .orderBy('subscriptions.created_at', 'desc')
    .limit(10);
}

async function upcomingExpirations({ days = 30, limit = 8 } = {}) {
  const from = new Date();
  const to = new Date();
  to.setDate(to.getDate() + days);
  return baseJoin(db(TABLE))
    .whereIn('subscriptions.status', LIVE_STATUSES)
    .whereNotNull('subscriptions.end_date')
    .whereBetween('subscriptions.end_date', [from.toISOString().slice(0, 10), to.toISOString().slice(0, 10)])
    .orderBy('subscriptions.end_date', 'asc')
    .limit(limit);
}

module.exports = {
  list, findById, findLiveForInstitution, create, update, updateStatus,
  countByStatus, listActiveForInstitution, upcomingExpirations, LIVE_STATUSES,
};
