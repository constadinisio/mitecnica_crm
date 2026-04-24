'use strict';

/**
 * crm_webhook_outbox — Cola durable para webhooks salientes del CRM hacia la
 * tenant app (mitecnica). Permite reintentos con backoff exponencial, auditoría
 * y recuperación ante caídas del consumer.
 *
 * Flujo:
 *   1. Service layer llama `webhookEmitterService.enqueue(event, payload)`.
 *   2. Se inserta con status='pending', webhook_id único (UUID), payload JSON.
 *   3. El dispatcher job (loop cada N seg) toma los pending cuyo
 *      next_attempt_at <= now(), firma HMAC, hace POST al tenant, y actualiza
 *      status/attempts/next_attempt_at según el resultado.
 *   4. Backoff: 1s, 5s, 30s, 5m, 30m, 2h, 6h, 24h → dead.
 *
 * Idempotencia: `webhook_id` viaja en el header x-crm-webhook-id. El receiver
 * (mitecnica) lo usa para evitar reprocesar duplicados ante reintentos.
 *
 * @param {import('knex').Knex} knex
 */
exports.up = async function up(knex) {
  await knex.schema.createTable('crm_webhook_outbox', (t) => {
    t.increments('id').primary();
    t.uuid('webhook_id').notNullable().unique();
    t.string('event_type', 60).notNullable();
    t.jsonb('payload').notNullable();
    t.text('target_url').notNullable();

    t.enu('status', ['pending', 'sent', 'failed', 'dead'], {
      useNative: true,
      enumName: 'crm_webhook_outbox_status',
    }).notNullable().defaultTo('pending');

    t.integer('attempts').notNullable().defaultTo(0);
    t.integer('max_attempts').notNullable().defaultTo(8);
    t.timestamp('next_attempt_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());
    t.timestamp('last_attempt_at', { useTz: true }).nullable();
    t.timestamp('delivered_at', { useTz: true }).nullable();
    t.integer('last_http_status').nullable();
    t.text('last_error').nullable();

    t.timestamp('created_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());
    t.timestamp('updated_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());

    t.index(['status', 'next_attempt_at'], 'crm_webhook_outbox_ready_idx');
    t.index('event_type', 'crm_webhook_outbox_event_idx');
    t.index('created_at', 'crm_webhook_outbox_created_idx');
  });
};

/** @param {import('knex').Knex} knex */
exports.down = async function down(knex) {
  await knex.schema.dropTableIfExists('crm_webhook_outbox');
  await knex.raw('DROP TYPE IF EXISTS crm_webhook_outbox_status');
};
