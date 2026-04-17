'use strict';

/** @param {import('knex').Knex} knex */
exports.up = async function up(knex) {
  await knex.schema.createTable('modules_catalog', (t) => {
    t.increments('id').primary();
    t.string('code', 48).notNullable().unique();
    t.string('name', 120).notNullable();
    t.text('description').nullable();
    t.enu('category', ['academic', 'communication', 'administration', 'technical', 'analytics', 'other'], {
      useNative: true, enumName: 'module_category',
    }).nullable();
    t.enu('status', ['active', 'inactive'], {
      useNative: true, enumName: 'module_status',
    }).notNullable().defaultTo('active');
    t.boolean('is_core').notNullable().defaultTo(false);
    t.timestamp('created_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());
    t.timestamp('updated_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());

    t.index('status', 'modules_catalog_status_idx');
    t.index('category', 'modules_catalog_category_idx');
  });
};

/** @param {import('knex').Knex} knex */
exports.down = async function down(knex) {
  await knex.schema.dropTableIfExists('modules_catalog');
  await knex.raw('DROP TYPE IF EXISTS module_category');
  await knex.raw('DROP TYPE IF EXISTS module_status');
};
