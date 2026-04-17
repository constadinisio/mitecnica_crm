'use strict';

const { db } = require('../../config/db');

const TABLE = 'modules_catalog';
const ALLOWED_SORT = new Set(['id', 'code', 'name', 'category', 'status', 'is_core', 'created_at', 'updated_at']);

function applyFilters(query, f = {}) {
  if (f.status) query.whereIn('status', Array.isArray(f.status) ? f.status : [f.status]);
  if (f.category) query.where('category', f.category);
  if (f.isCore !== null && f.isCore !== undefined) query.where('is_core', Boolean(f.isCore));
  if (f.search) {
    const s = `%${String(f.search).toLowerCase()}%`;
    query.where((qb) => {
      qb.whereRaw('LOWER(name) LIKE ?', [s]).orWhereRaw('LOWER(code) LIKE ?', [s]);
    });
  }
  return query;
}

async function list({ filters = {}, sort = 'name', order = 'asc', limit = 50, offset = 0 } = {}) {
  const sortBy = ALLOWED_SORT.has(sort) ? sort : 'name';
  const sortDir = String(order).toLowerCase() === 'desc' ? 'desc' : 'asc';
  const [{ count }] = await applyFilters(db(TABLE), filters).count({ count: '*' });
  const rows = await applyFilters(db(TABLE), filters)
    .select('*')
    .orderBy(sortBy, sortDir)
    .limit(limit)
    .offset(offset);
  return { rows, total: Number(count) };
}

async function findById(id) { return db(TABLE).where({ id }).first(); }
async function findByCode(code) { return db(TABLE).where({ code }).first(); }
async function create(data) { const [row] = await db(TABLE).insert(data).returning('*'); return row; }
async function update(id, data) { const [row] = await db(TABLE).where({ id }).update({ ...data, updated_at: db.fn.now() }).returning('*'); return row; }
async function updateStatus(id, status) { const [row] = await db(TABLE).where({ id }).update({ status, updated_at: db.fn.now() }).returning('*'); return row; }
async function listActive() { return db(TABLE).where({ status: 'active' }).orderBy('name', 'asc'); }

module.exports = { list, findById, findByCode, create, update, updateStatus, listActive };
