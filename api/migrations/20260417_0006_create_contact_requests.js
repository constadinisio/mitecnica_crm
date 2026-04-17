'use strict';

/** @param {import('knex').Knex} knex */
exports.up = async function up(knex) {
  await knex.schema.createTable('contact_requests', (t) => {
    t.increments('id').primary();

    // Datos del formulario público
    t.string('institution_name', 180).notNullable();
    t.string('contact_name', 160).notNullable();
    t.string('contact_email', 160).notNullable();
    t.string('contact_phone', 40).nullable();
    t.string('address', 255).nullable();
    t.string('plan_code_requested', 32).nullable();
    t.text('notes').nullable();

    // Trazabilidad
    t.string('source', 32).notNullable().defaultTo('public_form');
    t.string('ip', 64).nullable();
    t.string('user_agent', 255).nullable();

    // Pipeline comercial
    t.enu('status', ['new', 'contacted', 'in_negotiation', 'converted', 'lost'], {
      useNative: true, enumName: 'contact_request_status',
    }).notNullable().defaultTo('new');
    t.integer('assigned_to_user_id').nullable().references('id').inTable('crm_users').onDelete('SET NULL');

    // Conversión (null hasta que alguien convierta el lead)
    t.integer('converted_institution_id').nullable().references('id').inTable('institutions').onDelete('SET NULL');
    t.integer('converted_subscription_id').nullable().references('id').inTable('subscriptions').onDelete('SET NULL');
    t.timestamp('converted_at', { useTz: true }).nullable();

    t.timestamp('created_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());
    t.timestamp('updated_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());

    t.index('status', 'contact_requests_status_idx');
    t.index('assigned_to_user_id', 'contact_requests_assigned_idx');
    t.index('created_at', 'contact_requests_created_at_idx');
    t.index('contact_email', 'contact_requests_email_idx');
  });
};

/** @param {import('knex').Knex} knex */
exports.down = async function down(knex) {
  await knex.schema.dropTableIfExists('contact_requests');
  await knex.raw('DROP TYPE IF EXISTS contact_request_status');
};
