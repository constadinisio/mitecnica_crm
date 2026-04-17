'use strict';

/** @param {import('knex').Knex} knex */
exports.up = async function up(knex) {
  await knex.schema.createTable('payments', (t) => {
    t.increments('id').primary();
    t.integer('institution_id').notNullable().references('id').inTable('institutions').onDelete('CASCADE');
    t.integer('subscription_id').nullable().references('id').inTable('subscriptions').onDelete('SET NULL');
    t.decimal('amount', 12, 2).notNullable();
    t.string('currency_code', 10).notNullable().defaultTo('ARS');
    t.timestamp('payment_date', { useTz: true }).notNullable().defaultTo(knex.fn.now());
    t.enu('status', ['pending', 'approved', 'rejected', 'expired', 'canceled'], {
      useNative: true, enumName: 'payment_status',
    }).notNullable().defaultTo('pending');
    t.string('payment_method', 80).nullable();
    t.string('reference_code', 120).nullable();
    t.text('notes').nullable();
    t.integer('created_by_user_id').nullable().references('id').inTable('crm_users').onDelete('SET NULL');
    t.timestamp('created_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());
    t.timestamp('updated_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());

    t.index('status', 'payments_status_idx');
    t.index('payment_date', 'payments_payment_date_idx');
    t.index('institution_id', 'payments_institution_idx');
    t.index('subscription_id', 'payments_subscription_idx');
  });
};

/** @param {import('knex').Knex} knex */
exports.down = async function down(knex) {
  await knex.schema.dropTableIfExists('payments');
  await knex.raw('DROP TYPE IF EXISTS payment_status');
};
