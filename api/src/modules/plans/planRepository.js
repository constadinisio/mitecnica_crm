'use strict';

const { db } = require('../../config/db');

const TABLE = 'plans';
const ALLOWED_SORT = new Set(['id', 'code', 'name', 'price_amount', 'billing_frequency', 'status', 'created_at', 'updated_at']);

function applyFilters(query, f = {}) {
  if (f.status) query.whereIn('status', Array.isArray(f.status) ? f.status : [f.status]);
  if (f.billingFrequency) query.where('billing_frequency', f.billingFrequency);
  if (f.isCustom !== null && f.isCustom !== undefined) query.where('is_custom', Boolean(f.isCustom));
  if (f.search) {
    const s = `%${String(f.search).toLowerCase()}%`;
    query.where((qb) => {
      qb.whereRaw('LOWER(name) LIKE ?', [s])
        .orWhereRaw('LOWER(code) LIKE ?', [s]);
    });
  }
  return query;
}

async function list({ filters = {}, sort = 'created_at', order = 'desc', limit = 20, offset = 0 } = {}) {
  const sortBy = ALLOWED_SORT.has(sort) ? sort : 'created_at';
  const sortDir = String(order).toLowerCase() === 'asc' ? 'asc' : 'desc';

  const [{ count }] = await applyFilters(db(TABLE), filters).count({ count: '*' });
  const rows = await applyFilters(db(TABLE), filters)
    .select('*')
    .orderBy(sortBy, sortDir)
    .limit(limit)
    .offset(offset);
  return { rows, total: Number(count) };
}

async function findById(id) {
  return db(TABLE).where({ id }).first();
}

async function findByCode(code) {
  return db(TABLE).where({ code }).first();
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

async function listActive() {
  return db(TABLE).where({ status: 'active' }).orderBy('price_amount', 'asc');
}

module.exports = { list, findById, findByCode, create, update, updateStatus, countByStatus, listActive };
