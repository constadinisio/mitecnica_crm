'use strict';

function daysFromNow(days) {
  const d = new Date();
  d.setDate(d.getDate() + days);
  return d.toISOString().slice(0, 10);
}

const INSTITUTIONS = [
  {
    public_code: 'INS-2026-0001',
    name: 'Escuela Técnica N°20 D.E. 20 "Carlos Pellegrini"',
    slug: 'et20-pellegrini',
    subdomain: 'et20-pellegrini',
    status: 'active',
    contact_email: 'contacto@et20.edu.ar',
    contact_phone: '+54 11 4555-0001',
    address: 'Av. Larrazabal 1200, CABA',
    responsible_name: 'Nicolás Fernández',
    responsible_email: 'director@et20.edu.ar',
    notes_internal: 'Cliente insignia, adopción temprana.',
    current_plan_name: 'Enterprise Anual',
    expiration_date: daysFromNow(240),
    technical_status: 'optimal',
    last_activity_at: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
  },
  {
    public_code: 'INS-2026-0002',
    name: 'Instituto Técnico San Martín',
    slug: 'itsm',
    subdomain: 'itsm',
    status: 'trial',
    contact_email: 'secretaria@itsm.edu.ar',
    contact_phone: '+54 11 4555-0002',
    address: 'Belgrano 450, San Martín',
    responsible_name: 'Mariana López',
    responsible_email: 'mlopez@itsm.edu.ar',
    notes_internal: 'Trial inicial 30 días.',
    current_plan_name: 'Trial',
    expiration_date: daysFromNow(14),
    technical_status: 'updating',
    last_activity_at: new Date(Date.now() - 6 * 60 * 60 * 1000).toISOString(),
  },
  {
    public_code: 'INS-2026-0003',
    name: 'Escuela Técnica Industrial Mendoza',
    slug: 'eti-mendoza',
    subdomain: 'eti-mendoza',
    status: 'suspended',
    contact_email: 'admin@eti-mendoza.edu.ar',
    contact_phone: '+54 261 4555-0003',
    address: 'Mitre 900, Mendoza',
    responsible_name: 'Carlos Páez',
    responsible_email: 'cpaez@eti-mendoza.edu.ar',
    notes_internal: 'Suspendido por mora. Revisar con finanzas.',
    current_plan_name: 'Standard',
    expiration_date: daysFromNow(-30),
    technical_status: 'offline',
    last_activity_at: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    public_code: 'INS-2026-0004',
    name: 'Colegio Técnico Santa Fe',
    slug: 'cts-santafe',
    subdomain: 'cts-santafe',
    status: 'expired',
    contact_email: 'info@cts.edu.ar',
    contact_phone: '+54 342 4555-0004',
    address: 'San Martín 320, Santa Fe',
    responsible_name: 'Laura Ríos',
    responsible_email: 'lrios@cts.edu.ar',
    notes_internal: 'Plan vencido hace 7 días.',
    current_plan_name: 'Standard',
    expiration_date: daysFromNow(-7),
    technical_status: 'pending',
    last_activity_at: new Date(Date.now() - 14 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    public_code: 'INS-2026-0005',
    name: 'Instituto Tecnológico del Sur',
    slug: 'its-sur',
    subdomain: 'its-sur',
    status: 'active',
    contact_email: 'direccion@its-sur.edu.ar',
    contact_phone: '+54 291 4555-0005',
    address: 'Alem 2400, Bahía Blanca',
    responsible_name: 'Sergio Duarte',
    responsible_email: 'sduarte@its-sur.edu.ar',
    notes_internal: 'Renovación programada Q3.',
    current_plan_name: 'Professional',
    expiration_date: daysFromNow(90),
    technical_status: 'optimal',
    last_activity_at: new Date(Date.now() - 45 * 60 * 1000).toISOString(),
  },
];

exports.seed = async function seed(knex) {
  // Demo data solo si se pide explícitamente (dev local). En staging/prod
  // usamos la UI para crear instituciones reales y evitamos contaminar el feed.
  if (process.env.SEED_INCLUDE_DEMOS !== 'true') {
    return;
  }

  for (const inst of INSTITUTIONS) {
    const existing = await knex('institutions').where({ public_code: inst.public_code }).first();
    if (existing) {
      await knex('institutions').where({ id: existing.id }).update({ ...inst, updated_at: knex.fn.now() });
    } else {
      await knex('institutions').insert(inst);
    }
  }
};
