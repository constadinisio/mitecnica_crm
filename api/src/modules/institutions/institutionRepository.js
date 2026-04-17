'use strict';

const { db } = require('../../config/db');

const TABLE = 'institutions';

const ALLOWED_SORT = new Set([
  'id', 'name', 'status', 'technical_status',
  'expiration_date', 'created_at', 'updated_at', 'last_activity_at',
]);

function applyFilters(query, f = {}) {
  if (f.status) query.whereIn('status', Array.isArray(f.status) ? f.status : [f.status]);
  if (f.technicalStatus) query.whereIn('technical_status', Array.isArray(f.technicalStatus) ? f.technicalStatus : [f.technicalStatus]);
  if (f.plan) query.where('current_plan_name', f.plan);
  if (f.search) {
    const s = `%${String(f.search).toLowerCase()}%`;
    query.where((qb) => {
      qb.whereRaw('LOWER(name) LIKE ?', [s])
        .orWhereRaw('LOWER(public_code) LIKE ?', [s])
        .orWhereRaw('LOWER(subdomain) LIKE ?', [s])
        .orWhereRaw('LOWER(contact_email) LIKE ?', [s]);
    });
  }
  if (f.expirationFrom) query.where('expiration_date', '>=', f.expirationFrom);
  if (f.expirationTo) query.where('expiration_date', '<=', f.expirationTo);
  return query;
}

async function list({ filters = {}, sort = 'created_at', order = 'desc', limit = 20, offset = 0 } = {}) {
  const sortBy = ALLOWED_SORT.has(sort) ? sort : 'created_at';
  const sortDir = String(order).toLowerCase() === 'asc' ? 'asc' : 'desc';

  const countQ = applyFilters(db(TABLE), filters);
  const [{ count }] = await countQ.count({ count: '*' });

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

async function findBySlug(slug) {
  return db(TABLE).where({ slug }).first();
}

async function findBySubdomain(subdomain) {
  return db(TABLE).where({ subdomain }).first();
}

async function findByPublicCode(publicCode) {
  return db(TABLE).where({ public_code: publicCode }).first();
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

async function countByTechnicalStatus() {
  const rows = await db(TABLE).select('technical_status').count({ count: '*' }).groupBy('technical_status');
  const out = {};
  rows.forEach((r) => { out[r.technical_status] = Number(r.count); });
  return out;
}

async function upcomingExpirations({ days = 30, limit = 8 } = {}) {
  const from = new Date();
  const to = new Date();
  to.setDate(to.getDate() + days);
  return db(TABLE)
    .whereNotNull('expiration_date')
    .whereBetween('expiration_date', [from.toISOString().slice(0, 10), to.toISOString().slice(0, 10)])
    .whereNotIn('status', ['inactive'])
    .orderBy('expiration_date', 'asc')
    .limit(limit);
}

async function listRecent(limit = 6) {
  return db(TABLE).orderBy('created_at', 'desc').limit(limit);
}

async function countTotal() {
  const [{ count }] = await db(TABLE).count({ count: '*' });
  return Number(count);
}

async function nextPublicCode() {
  const year = new Date().getFullYear();
  const prefix = `INS-${year}-`;
  const last = await db(TABLE)
    .where('public_code', 'like', `${prefix}%`)
    .orderBy('public_code', 'desc')
    .first();
  if (!last) return `${prefix}0001`;
  const tail = last.public_code.slice(prefix.length);
  const next = String(Number(tail) + 1).padStart(4, '0');
  return `${prefix}${next}`;
}

module.exports = {
  list,
  findById,
  findBySlug,
  findBySubdomain,
  findByPublicCode,
  create,
  update,
  updateStatus,
  countByStatus,
  countByTechnicalStatus,
  upcomingExpirations,
  listRecent,
  countTotal,
  nextPublicCode,
};
