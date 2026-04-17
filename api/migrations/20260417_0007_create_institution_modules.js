'use strict';

/** @param {import('knex').Knex} knex */
exports.up = async function up(knex) {
  await knex.schema.createTable('institution_modules', (t) => {
    t.increments('id').primary();
    t.integer('institution_id').notNullable().references('id').inTable('institutions').onDelete('CASCADE');
    t.integer('module_id').notNullable().references('id').inTable('modules_catalog').onDelete('CASCADE');
    t.enu('override_mode', ['force_enabled', 'force_disabled'], {
      useNative: true, enumName: 'institution_module_override_mode',
    }).notNullable();
    t.text('notes').nullable();
    t.timestamp('created_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());
    t.timestamp('updated_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());

    t.unique(['institution_id', 'module_id'], { indexName: 'institution_modules_institution_module_unique' });
    t.index('institution_id', 'institution_modules_institution_idx');
    t.index('module_id', 'institution_modules_module_idx');
    t.index('override_mode', 'institution_modules_mode_idx');
  });
};

/** @param {import('knex').Knex} knex */
exports.down = async function down(knex) {
  await knex.schema.dropTableIfExists('institution_modules');
  await knex.raw('DROP TYPE IF EXISTS institution_module_override_mode');
};
