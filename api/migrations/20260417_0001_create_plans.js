'use strict';

/** @param {import('knex').Knex} knex */
exports.up = async function up(knex) {
  await knex.schema.createTable('plans', (t) => {
    t.increments('id').primary();
    t.string('code', 32).notNullable().unique();
    t.string('name', 120).notNullable();
    t.text('description').nullable();
    t.enu('billing_frequency', ['monthly', 'quarterly', 'yearly', 'custom'], {
      useNative: true, enumName: 'plan_billing_frequency',
    }).notNullable().defaultTo('monthly');
    t.decimal('price_amount', 12, 2).notNullable().defaultTo(0);
    t.string('currency_code', 10).notNullable().defaultTo('ARS');
    t.enu('status', ['active', 'inactive', 'archived'], {
      useNative: true, enumName: 'plan_status',
    }).notNullable().defaultTo('active');
    t.boolean('is_custom').notNullable().defaultTo(false);
    t.timestamp('created_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());
    t.timestamp('updated_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());

    t.index('status', 'plans_status_idx');
    t.index('billing_frequency', 'plans_billing_idx');
  });
};

/** @param {import('knex').Knex} knex */
exports.down = async function down(knex) {
  await knex.schema.dropTableIfExists('plans');
  await knex.raw('DROP TYPE IF EXISTS plan_billing_frequency');
  await knex.raw('DROP TYPE IF EXISTS plan_status');
};
