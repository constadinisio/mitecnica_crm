'use strict';

/** @param {import('knex').Knex} knex */
exports.up = async function up(knex) {
  await knex.schema.createTable('subscriptions', (t) => {
    t.increments('id').primary();
    t.integer('institution_id').notNullable().references('id').inTable('institutions').onDelete('CASCADE');
    t.integer('plan_id').notNullable().references('id').inTable('plans').onDelete('RESTRICT');
    t.enu('status', ['trial', 'active', 'suspended', 'expired', 'canceled'], {
      useNative: true, enumName: 'subscription_status',
    }).notNullable().defaultTo('trial');
    t.date('start_date').notNullable();
    t.date('end_date').nullable();
    t.timestamp('trial_ends_at', { useTz: true }).nullable();
    t.enu('renewal_mode', ['manual', 'automatic'], {
      useNative: true, enumName: 'subscription_renewal_mode',
    }).notNullable().defaultTo('manual');
    t.text('billing_notes').nullable();
    t.timestamp('created_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());
    t.timestamp('updated_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());

    t.index('institution_id', 'subscriptions_institution_idx');
    t.index('plan_id', 'subscriptions_plan_idx');
    t.index('status', 'subscriptions_status_idx');
    t.index('start_date', 'subscriptions_start_date_idx');
  });

  // Partial unique constraint: only one LIVE subscription per institution
  // (live = trial or active). A canceled/expired/suspended sub should not block a new one.
  await knex.raw(`
    CREATE UNIQUE INDEX subscriptions_one_live_per_institution
      ON subscriptions (institution_id)
      WHERE status IN ('trial', 'active');
  `);
};

/** @param {import('knex').Knex} knex */
exports.down = async function down(knex) {
  await knex.raw('DROP INDEX IF EXISTS subscriptions_one_live_per_institution');
  await knex.schema.dropTableIfExists('subscriptions');
  await knex.raw('DROP TYPE IF EXISTS subscription_status');
  await knex.raw('DROP TYPE IF EXISTS subscription_renewal_mode');
};
