'use strict';

/** @param {import('knex').Knex} knex */
exports.up = async function up(knex) {
  await knex.schema.createTable('crm_users', (t) => {
    t.increments('id').primary();
    t.string('name', 120).notNullable();
    t.string('email', 160).notNullable().unique();
    t.string('password_hash', 255).nullable();
    t.integer('role_id').notNullable().references('id').inTable('crm_roles').onDelete('RESTRICT');
    t.enu('status', ['active', 'inactive'], {
      useNative: true,
      enumName: 'crm_user_status',
    }).notNullable().defaultTo('active');
    t.string('avatar_url', 500).nullable();
    t.string('google_id', 120).nullable().unique();
    t.timestamp('last_login_at', { useTz: true }).nullable();
    t.timestamp('created_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());
    t.timestamp('updated_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());

    t.index('role_id', 'crm_users_role_id_idx');
    t.index('status', 'crm_users_status_idx');
  });
};

/** @param {import('knex').Knex} knex */
exports.down = async function down(knex) {
  await knex.schema.dropTableIfExists('crm_users');
  await knex.raw('DROP TYPE IF EXISTS crm_user_status');
};
