'use strict';

/** @param {import('knex').Knex} knex */
exports.up = async function up(knex) {
  await knex.schema.createTable('crm_refresh_tokens', (t) => {
    t.increments('id').primary();
    t.integer('user_id').notNullable().references('id').inTable('crm_users').onDelete('CASCADE');
    t.string('token_hash', 128).notNullable().unique();
    t.timestamp('expires_at', { useTz: true }).notNullable();
    t.timestamp('revoked_at', { useTz: true }).nullable();
    t.string('user_agent', 255).nullable();
    t.string('ip', 64).nullable();
    t.timestamp('created_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());

    t.index('user_id', 'crm_refresh_tokens_user_id_idx');
    t.index('expires_at', 'crm_refresh_tokens_expires_at_idx');
  });
};

/** @param {import('knex').Knex} knex */
exports.down = async function down(knex) {
  await knex.schema.dropTableIfExists('crm_refresh_tokens');
};
