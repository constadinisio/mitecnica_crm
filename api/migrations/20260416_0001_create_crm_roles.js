'use strict';

/** @param {import('knex').Knex} knex */
exports.up = async function up(knex) {
  await knex.schema.createTable('crm_roles', (t) => {
    t.increments('id').primary();
    t.string('key', 32).notNullable().unique();
    t.string('name', 80).notNullable();
    t.string('description', 255).nullable();
    t.timestamp('created_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());
    t.timestamp('updated_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());
  });
};

/** @param {import('knex').Knex} knex */
exports.down = async function down(knex) {
  await knex.schema.dropTableIfExists('crm_roles');
};
