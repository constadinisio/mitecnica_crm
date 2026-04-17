'use strict';

/** @param {import('knex').Knex} knex */
exports.up = async function up(knex) {
  await knex.schema.createTable('audit_logs', (t) => {
    t.bigIncrements('id').primary();
    t.integer('actor_user_id').nullable().references('id').inTable('crm_users').onDelete('SET NULL');
    t.string('action', 80).notNullable();
    t.string('entity', 80).notNullable();
    t.string('entity_id', 64).nullable();
    t.string('description', 500).nullable();
    t.jsonb('before_data').nullable();
    t.jsonb('after_data').nullable();
    t.string('ip', 64).nullable();
    t.string('user_agent', 255).nullable();
    t.timestamp('created_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());

    t.index('actor_user_id', 'audit_logs_actor_idx');
    t.index('action', 'audit_logs_action_idx');
    t.index('entity', 'audit_logs_entity_idx');
    t.index('created_at', 'audit_logs_created_at_idx');
    t.index(['entity', 'entity_id'], 'audit_logs_entity_pair_idx');
  });
};

/** @param {import('knex').Knex} knex */
exports.down = async function down(knex) {
  await knex.schema.dropTableIfExists('audit_logs');
};
