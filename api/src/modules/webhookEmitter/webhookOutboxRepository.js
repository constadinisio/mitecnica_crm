'use strict';

const { db } = require('../../config/db');

const TABLE = 'crm_webhook_outbox';

/**
 * Inserta un webhook pendiente. Usa `trx` si viene desde una transacción de
 * service layer (garantiza atomicidad: si falla el cambio de negocio, no queda
 * un webhook huérfano encolado).
 *
 * @param {object} row
 * @param {string} row.webhook_id    UUID único
 * @param {string} row.event_type
 * @param {object} row.payload       objeto JS (se serializa a jsonb)
 * @param {string} row.target_url
 * @param {number} [row.max_attempts]
 * @param {import('knex').Knex.Transaction} [trx]
 * @returns {Promise<object>} row insertado
 */
async function create(row, trx = null) {
  const conn = trx || db;
  const [inserted] = await conn(TABLE)
    .insert({
      webhook_id: row.webhook_id,
      event_type: row.event_type,
      payload: row.payload,
      target_url: row.target_url,
      status: 'pending',
      attempts: 0,
      max_attempts: row.max_attempts || 8,
    })
    .returning('*');
  return inserted;
}

/**
 * Claim hasta `limit` webhooks pending con `next_attempt_at <= now()`.
 * Usa `FOR UPDATE SKIP LOCKED` para que múltiples workers (si hubiera)
 * no se pisen.
 *
 * @param {number} limit
 * @returns {Promise<object[]>}
 */
async function claimBatch(limit = 20) {
  const rows = await db.raw(
    `SELECT * FROM ${TABLE}
     WHERE status = 'pending' AND next_attempt_at <= NOW()
     ORDER BY next_attempt_at ASC
     LIMIT ?
     FOR UPDATE SKIP LOCKED`,
    [limit]
  );
  // pg driver devuelve { rows, ... } vs knex estándar.
  return rows.rows || rows;
}

async function markSent(id, httpStatus) {
  await db(TABLE).where({ id }).update({
    status: 'sent',
    last_http_status: httpStatus,
    last_attempt_at: db.fn.now(),
    delivered_at: db.fn.now(),
    updated_at: db.fn.now(),
    last_error: null,
  });
}

async function markRetry(id, { nextAttemptAt, lastHttpStatus = null, lastError = null }) {
  await db(TABLE).where({ id }).update({
    status: 'pending',
    last_http_status: lastHttpStatus,
    last_attempt_at: db.fn.now(),
    next_attempt_at: nextAttemptAt,
    last_error: (lastError || '').slice(0, 2000),
    updated_at: db.fn.now(),
  }).increment('attempts', 1);
}

async function markDead(id, { lastHttpStatus = null, lastError = null }) {
  await db(TABLE).where({ id }).update({
    status: 'dead',
    last_http_status: lastHttpStatus,
    last_attempt_at: db.fn.now(),
    last_error: (lastError || '').slice(0, 2000),
    updated_at: db.fn.now(),
  }).increment('attempts', 1);
}

async function findById(id) {
  return db(TABLE).where({ id }).first();
}

async function countByStatus() {
  const rows = await db(TABLE)
    .select('status')
    .count({ count: '*' })
    .groupBy('status');
  return Object.fromEntries(rows.map((r) => [r.status, Number(r.count)]));
}

module.exports = {
  create,
  claimBatch,
  markSent,
  markRetry,
  markDead,
  findById,
  countByStatus,
};
