'use strict';

/** @param {import('knex').Knex} knex */
exports.up = async function up(knex) {
  await knex.schema.createTable('institutions', (t) => {
    t.increments('id').primary();
    t.string('public_code', 24).notNullable().unique();
    t.string('name', 180).notNullable();
    t.string('slug', 180).notNullable().unique();
    t.string('subdomain', 120).notNullable().unique();

    t.enu('status', ['trial', 'active', 'maintenance', 'suspended', 'expired', 'inactive'], {
      useNative: true,
      enumName: 'institution_status',
    }).notNullable().defaultTo('trial');

    t.string('contact_email', 160).notNullable();
    t.string('contact_phone', 40).nullable();
    t.string('address', 255).nullable();

    t.string('responsible_name', 160).nullable();
    t.string('responsible_email', 160).nullable();

    t.text('notes_internal').nullable();

    t.string('current_plan_name', 120).nullable();
    t.date('expiration_date').nullable();

    t.enu('technical_status', ['pending', 'optimal', 'updating', 'offline'], {
      useNative: true,
      enumName: 'institution_technical_status',
    }).notNullable().defaultTo('pending');

    t.timestamp('last_activity_at', { useTz: true }).nullable();

    t.timestamp('created_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());
    t.timestamp('updated_at', { useTz: true }).notNullable().defaultTo(knex.fn.now());

    t.index('status', 'institutions_status_idx');
    t.index('technical_status', 'institutions_tech_status_idx');
    t.index('expiration_date', 'institutions_expiration_idx');
    t.index('created_at', 'institutions_created_at_idx');
  });

  await knex.raw(`
    CREATE INDEX institutions_name_trgm_idx ON institutions USING gin (LOWER(name) gin_trgm_ops);
  `).catch(() => { /* pg_trgm optional */ });
};

/** @param {import('knex').Knex} knex */
exports.down = async function down(knex) {
  await knex.schema.dropTableIfExists('institutions');
  await knex.raw('DROP TYPE IF EXISTS institution_status');
  await knex.raw('DROP TYPE IF EXISTS institution_technical_status');
};
