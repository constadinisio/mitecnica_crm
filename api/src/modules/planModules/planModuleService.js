'use strict';

const ApiError = require('../../utils/ApiError');
const repo = require('./planModuleRepository');
const plansRepo = require('../plans/planRepository');
const modulesRepo = require('../modulesCatalog/moduleCatalogRepository');
const auditService = require('../audit/auditService');

async function getMatrix() {
  const [plans, modules, relations] = await Promise.all([
    plansRepo.listActive(),
    modulesRepo.listActive(),
    repo.listAllIncluded(),
  ]);
  // Build a plan_id -> Set<module_id> lookup for quick cell resolution
  const map = {};
  relations.forEach((r) => {
    map[r.plan_id] = map[r.plan_id] || {};
    map[r.plan_id][r.module_id] = true;
  });
  return {
    plans: plans.map((p) => ({
      id: p.id, code: p.code, name: p.name, status: p.status,
      price_amount: Number(p.price_amount), currency_code: p.currency_code,
      billing_frequency: p.billing_frequency,
    })),
    modules: modules.map((m) => ({
      id: m.id, code: m.code, name: m.name, category: m.category, is_core: m.is_core, status: m.status,
    })),
    relations: map,
  };
}

async function listForPlan(planId) {
  const plan = await plansRepo.findById(planId);
  if (!plan) throw ApiError.notFound('Plan not found');
  const rows = await repo.listByPlan(planId);
  return {
    plan: { id: plan.id, code: plan.code, name: plan.name },
    module_ids: rows.map((r) => r.module_id),
  };
}

async function setPlanModules(planId, moduleIds, { actor, ip, userAgent }) {
  const plan = await plansRepo.findById(planId);
  if (!plan) throw ApiError.notFound('Plan not found');

  const normalized = Array.from(new Set((moduleIds || []).map(Number).filter(Number.isFinite)));
  // Validate all module ids exist and are active
  if (normalized.length > 0) {
    const existing = await Promise.all(normalized.map((id) => modulesRepo.findById(id)));
    existing.forEach((m, i) => {
      if (!m) throw ApiError.badRequest(`Module id ${normalized[i]} not found`);
    });
  }

  const { before, after } = await repo.replacePlanModules(planId, normalized);

  const beforeIds = before.map((r) => r.module_id).sort((a, b) => a - b);
  const afterIds = after.map((r) => r.module_id).sort((a, b) => a - b);

  if (JSON.stringify(beforeIds) !== JSON.stringify(afterIds)) {
    await auditService.record({
      actorUserId: actor?.userId || null,
      action: 'plan.modules_updated', entity: 'plans', entityId: planId,
      description: `Entitlements del plan "${plan.name}" actualizados (${afterIds.length} módulos)`,
      beforeData: { module_ids: beforeIds },
      afterData: { module_ids: afterIds },
      ip, userAgent,
    });
  }

  return { plan_id: planId, module_ids: afterIds };
}

module.exports = { getMatrix, listForPlan, setPlanModules };
