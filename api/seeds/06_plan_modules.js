'use strict';

const MATRIX = {
  basic:        ['attendance', 'grades', 'report_cards'],
  professional: ['attendance', 'grades', 'report_cards', 'campus', 'families'],
  elite:        ['attendance', 'grades', 'report_cards', 'campus', 'families', 'doe', 'analytics'],
  enterprise:   ['attendance', 'grades', 'report_cards', 'campus', 'families', 'doe', 'analytics', 'inventory'],
};

exports.seed = async function seed(knex) {
  const plans = await knex('plans').select('id', 'code');
  const modules = await knex('modules_catalog').select('id', 'code');
  const planByCode = Object.fromEntries(plans.map((p) => [p.code, p.id]));
  const moduleByCode = Object.fromEntries(modules.map((m) => [m.code, m.id]));

  for (const [planCode, moduleCodes] of Object.entries(MATRIX)) {
    const planId = planByCode[planCode];
    if (!planId) continue;
    for (const moduleCode of moduleCodes) {
      const moduleId = moduleByCode[moduleCode];
      if (!moduleId) continue;
      const existing = await knex('plan_modules').where({ plan_id: planId, module_id: moduleId }).first();
      if (existing) {
        await knex('plan_modules').where({ id: existing.id }).update({ included: true, updated_at: knex.fn.now() });
      } else {
        await knex('plan_modules').insert({ plan_id: planId, module_id: moduleId, included: true });
      }
    }
  }
};
