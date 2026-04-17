'use strict';

const { db } = require('../../config/db');

const TABLE = 'payments';
const ALLOWED_SORT = new Set(['id', 'amount', 'payment_date', 'status', 'created_at', 'updated_at']);

function baseJoin(query) {
  return query
    .leftJoin('institutions', 'payments.institution_id', 'institutions.id')
    .leftJoin('subscriptions', 'payments.subscription_id', 'subscriptions.id')
    .leftJoin('plans', 'subscriptions.plan_id', 'plans.id')
    .leftJoin('crm_users as creator', 'payments.created_by_user_id', 'creator.id')
    .select(
      'payments.*',
      'institutions.name as institution_name',
      'institutions.public_code as institution_code',
      'plans.name as plan_name',
      'plans.code as plan_code',
      'creator.name as created_by_name',
      'creator.email as created_by_email',
    );
}

function applyFilters(query, f = {}) {
  if (f.institutionId) query.where('payments.institution_id', f.institutionId);
  if (f.subscriptionId) query.where('payments.subscription_id', f.subscriptionId);
  if (f.status) query.whereIn('payments.status', Array.isArray(f.status) ? f.status : [f.status]);
  if (f.paymentMethod) query.where('payments.payment_method', f.paymentMethod);
  if (f.from) query.where('payments.payment_date', '>=', f.from);
  if (f.to) query.where('payments.payment_date', '<=', f.to);
  if (f.search) {
    const s = `%${String(f.search).toLowerCase()}%`;
    query.where((qb) => {
      qb.whereRaw('LOWER(institutions.name) LIKE ?', [s])
        .orWhereRaw('LOWER(institutions.public_code) LIKE ?', [s])
        .orWhereRaw('LOWER(payments.reference_code) LIKE ?', [s])
        .orWhereRaw('LOWER(payments.payment_method) LIKE ?', [s]);
    });
  }
  return query;
}

async function list({ filters = {}, sort = 'payment_date', order = 'desc', limit = 20, offset = 0 } = {}) {
  const sortBy = ALLOWED_SORT.has(sort) ? sort : 'payment_date';
  const sortDir = String(order).toLowerCase() === 'asc' ? 'asc' : 'desc';

  const countQ = applyFilters(
    db(TABLE).leftJoin('institutions', 'payments.institution_id', 'institutions.id'),
    filters,
  );
  const [{ count }] = await countQ.count({ count: 'payments.id' });

  const rows = await applyFilters(baseJoin(db(TABLE)), filters)
    .orderBy(`payments.${sortBy}`, sortDir)
    .limit(limit)
    .offset(offset);

  return { rows, total: Number(count) };
}

async function findById(id) {
  return baseJoin(db(TABLE)).where('payments.id', id).first();
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

async function totalsByStatus({ from = null, to = null } = {}) {
  const q = db(TABLE).select('status', 'currency_code').sum({ amount: 'amount' }).groupBy('status', 'currency_code');
  if (from) q.where('payment_date', '>=', from);
  if (to) q.where('payment_date', '<=', to);
  const rows = await q;
  return rows.map((r) => ({ status: r.status, currency_code: r.currency_code, amount: Number(r.amount) }));
}

async function listRecent(limit = 6) {
  return baseJoin(db(TABLE)).orderBy('payments.payment_date', 'desc').limit(limit);
}

async function listForInstitution(institutionId, limit = 10) {
  return baseJoin(db(TABLE))
    .where('payments.institution_id', institutionId)
    .orderBy('payments.payment_date', 'desc')
    .limit(limit);
}

module.exports = {
  list, findById, create, update, updateStatus,
  countByStatus, totalsByStatus, listRecent, listForInstitution,
};
