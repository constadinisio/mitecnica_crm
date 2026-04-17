'use strict';

const ROLES = [
  { key: 'superadmin', name: 'Superadmin', description: 'Acceso total al CRM' },
  { key: 'support', name: 'Soporte', description: 'Soporte técnico y monitoreo' },
  { key: 'commercial', name: 'Comercial', description: 'Gestión comercial e instituciones' },
  { key: 'finance', name: 'Finanzas', description: 'Reportes y control financiero' },
  { key: 'developer', name: 'Desarrollo', description: 'Equipo técnico interno' },
];

exports.seed = async function seed(knex) {
  for (const role of ROLES) {
    const existing = await knex('crm_roles').where({ key: role.key }).first();
    if (existing) {
      await knex('crm_roles').where({ id: existing.id }).update({ ...role, updated_at: knex.fn.now() });
    } else {
      await knex('crm_roles').insert(role);
    }
  }
};
