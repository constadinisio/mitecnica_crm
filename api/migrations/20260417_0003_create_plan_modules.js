'use strict';

/** @param {import('knex').Knex} knex */
exports.up = async function up(knex) {
  await knex.schema.createTable('plan_modules', (t) => {
    t.increments('id').primary();
    t.integer('plan_id').notNullable().references('id').inTable('plans').onDelete('CASCADE');
    t.integer('module_id').notNullable().references('id').inTable('modules_catalog').onDelete('CASCADE');
    t.boolean('included').notNullable().defaultTo(true);
    t.timestamp('created_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());
    t.timestamp('updated_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());

    t.unique(['plan_id', 'module_id'], { indexName: 'plan_modules_plan_module_unique' });
    t.index('plan_id', 'plan_modules_plan_idx');
    t.index('module_id', 'plan_modules_module_idx');
  });
};

/** @param {import('knex').Knex} knex */
exports.down = async function down(knex) {
  await knex.schema.dropTableIfExists('plan_modules');
};
