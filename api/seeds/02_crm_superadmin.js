'use strict';

const bcrypt = require('bcryptjs');

exports.seed = async function seed(knex) {
  const role = await knex('crm_roles').where({ key: 'superadmin' }).first();
  if (!role) throw new Error('Seed error: role superadmin not found. Run 01_crm_roles first.');

  const email = 'admin@mitecnica.local';
  const existing = await knex('crm_users').where({ email }).first();
  const passwordHash = await bcrypt.hash('Admin123!', 10);

  const payload = {
    name: 'Mi Tecnica Admin',
    email,
    password_hash: passwordHash,
    role_id: role.id,
    status: 'active',
    avatar_url: null,
  };

  if (existing) {
    await knex('crm_users').where({ id: existing.id }).update({ ...payload, updated_at: knex.fn.now() });
  } else {
    await knex('crm_users').insert(payload);
  }
};
