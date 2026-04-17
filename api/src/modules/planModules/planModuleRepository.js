'use strict';

const { db } = require('../../config/db');

const TABLE = 'plan_modules';

async function listByPlan(planId) {
  return db(TABLE).where({ plan_id: planId, included: true }).select('*');
}

async function listAllIncluded() {
  return db(TABLE).where({ included: true }).select('plan_id', 'module_id');
}

async function replacePlanModules(planId, moduleIds) {
  return db.transaction(async (trx) => {
    const before = await trx(TABLE).where({ plan_id: planId }).select('*');
    await trx(TABLE).where({ plan_id: planId }).del();
    if (moduleIds.length > 0) {
      const rows = moduleIds.map((mid) => ({ plan_id: planId, module_id: mid, included: true }));
      await trx(TABLE).insert(rows);
    }
    const after = await trx(TABLE).where({ plan_id: planId }).select('*');
    return { before, after };
  });
}

module.exports = { listByPlan, listAllIncluded, replacePlanModules };
