'use strict';

/**
 * Precios de referencia en pesos argentinos (ARS) para el mercado educativo local.
 * Ajustables desde el CRM una vez corridos los seeds.
 */
const PLANS = [
  { code: 'basic',        name: 'Basic',        description: 'Plan inicial para instituciones pequeñas.',    billing_frequency: 'monthly', price_amount:   25000.00, currency_code: 'ARS', status: 'active', is_custom: false },
  { code: 'professional', name: 'Professional', description: 'Plan profesional con módulos ampliados.',      billing_frequency: 'monthly', price_amount:   65000.00, currency_code: 'ARS', status: 'active', is_custom: false },
  { code: 'elite',        name: 'Elite',        description: 'Plan con analítica y reportes avanzados.',     billing_frequency: 'monthly', price_amount:  120000.00, currency_code: 'ARS', status: 'active', is_custom: false },
  { code: 'enterprise',   name: 'Enterprise',   description: 'Plan completo para redes educativas grandes.', billing_frequency: 'yearly',  price_amount: 1800000.00, currency_code: 'ARS', status: 'active', is_custom: false },
];

exports.seed = async function seed(knex) {
  for (const plan of PLANS) {
    const existing = await knex('plans').where({ code: plan.code }).first();
    if (existing) {
      await knex('plans').where({ id: existing.id }).update({ ...plan, updated_at: knex.fn.now() });
    } else {
      await knex('plans').insert(plan);
    }
  }
};
