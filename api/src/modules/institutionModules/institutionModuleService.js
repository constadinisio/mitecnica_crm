'use strict';

const ApiError = require('../../utils/ApiError');
const repo = require('./institutionModuleRepository');
const institutionsRepo = require('../institutions/institutionRepository');
const modulesRepo = require('../modulesCatalog/moduleCatalogRepository');
const planModulesRepo = require('../planModules/planModuleRepository');
const plansRepo = require('../plans/planRepository');
const subscriptionRepo = require('../subscriptions/subscriptionRepository');
const paymentRepo = require('../payments/paymentRepository');
const auditService = require('../audit/auditService');

const ALLOWED_OVERRIDE_MODES = ['force_enabled', 'force_disabled'];

/**
 * Resolve which plan applies to an institution.
 * Preference order:
 *   1. Live subscription (status in trial|active) — lee plans via subscription.
 *   2. No plan → null.
 */
async function resolveActivePlan(institutionId) {
  const live = await subscriptionRepo.findLiveForInstitution(institutionId);
  if (!live) return { subscription: null, plan: null };
  const plan = await plansRepo.findById(live.plan_id);
  return { subscription: live, plan: plan || null };
}

/**
 * Build the effective-modules matrix for an institution.
 * Each row = { module, plan_included, override_mode|null, effective_enabled, source }.
 * source ∈ { 'plan', 'override', 'plan+override' }
 */
async function getEffectiveModules(institutionId) {
  const institution = await institutionsRepo.findById(institutionId);
  if (!institution) throw ApiError.notFound('Institution not found');

  const { subscription, plan } = await resolveActivePlan(institutionId);

  const [modules, planRelations, overrides] = await Promise.all([
    modulesRepo.listActive(),
    plan ? planModulesRepo.listByPlan(plan.id) : Promise.resolve([]),
    repo.listByInstitution(institutionId),
  ]);

  const planSet = new Set(planRelations.map((r) => r.module_id));
  const overrideMap = new Map(overrides.map((o) => [o.module_id, o]));

  const rows = modules.map((m) => {
    const planIncluded = planSet.has(m.id);
    const ov = overrideMap.get(m.id) || null;
    const overrideMode = ov ? ov.override_mode : null;

    let effective;
    let source;
    if (overrideMode === 'force_enabled') {
      effective = true;
      source = planIncluded ? 'plan+override' : 'override';
    } else if (overrideMode === 'force_disabled') {
      effective = false;
      source = 'override';
    } else {
      effective = planIncluded;
      source = 'plan';
    }

    return {
      module: {
        id: m.id,
        code: m.code,
        name: m.name,
        category: m.category,
        is_core: m.is_core,
        status: m.status,
      },
      plan_included: planIncluded,
      override_mode: overrideMode,
      override_notes: ov ? ov.notes : null,
      effective_enabled: effective,
      source,
    };
  });

  const summary = {
    total: rows.length,
    plan_included: rows.filter((r) => r.plan_included).length,
    override_count: rows.filter((r) => r.override_mode).length,
    effective_enabled: rows.filter((r) => r.effective_enabled).length,
  };

  return {
    institution: {
      id: institution.id,
      name: institution.name,
      public_code: institution.public_code,
    },
    subscription: subscription
      ? {
          id: subscription.id,
          status: subscription.status,
          start_date: subscription.start_date,
          end_date: subscription.end_date,
          trial_ends_at: subscription.trial_ends_at,
          renewal_mode: subscription.renewal_mode,
        }
      : null,
    plan: plan
      ? {
          id: plan.id,
          code: plan.code,
          name: plan.name,
          billing_frequency: plan.billing_frequency,
          price_amount: Number(plan.price_amount),
          currency_code: plan.currency_code,
        }
      : null,
    modules: rows,
    summary,
  };
}

/**
 * Replace the full set of overrides for an institution.
 * overrides = [{ module_id, override_mode, notes? }]. Anything not sent is removed.
 */
async function replaceOverrides(institutionId, overrides, { actor, ip, userAgent }) {
  const institution = await institutionsRepo.findById(institutionId);
  if (!institution) throw ApiError.notFound('Institution not found');

  const normalized = Array.from(
    new Map(
      (overrides || [])
        .filter((o) => o && o.module_id && ALLOWED_OVERRIDE_MODES.includes(o.override_mode))
        .map((o) => [Number(o.module_id), {
          module_id: Number(o.module_id),
          override_mode: o.override_mode,
          notes: typeof o.notes === 'string' ? o.notes.slice(0, 500) : null,
        }]),
    ).values(),
  );

  if (normalized.length > 0) {
    const moduleIds = normalized.map((o) => o.module_id);
    const checks = await Promise.all(moduleIds.map((id) => modulesRepo.findById(id)));
    checks.forEach((m, i) => {
      if (!m) throw ApiError.badRequest(`Module id ${moduleIds[i]} not found`);
    });
  }

  const { before, after } = await repo.replaceOverrides(institutionId, normalized);

  const beforeMap = Object.fromEntries(before.map((r) => [r.module_id, r.override_mode]));
  const afterMap = Object.fromEntries(after.map((r) => [r.module_id, r.override_mode]));

  const changed = JSON.stringify(beforeMap) !== JSON.stringify(afterMap);
  if (changed) {
    await auditService.record({
      actorUserId: actor?.userId || null,
      action: 'institution.modules_overrides_updated',
      entity: 'institutions',
      entityId: institutionId,
      description: `Overrides de módulos actualizados para "${institution.name}" (${institution.public_code})`,
      beforeData: { overrides: beforeMap },
      afterData: { overrides: afterMap },
      ip,
      userAgent,
    });
  }

  return getEffectiveModules(institutionId);
}

/**
 * Condensed license summary: plan, subscription lifecycle, payment status, effective module count.
 */
async function getLicenseSummary(institutionId) {
  const institution = await institutionsRepo.findById(institutionId);
  if (!institution) throw ApiError.notFound('Institution not found');

  const [{ subscription, plan }, effective, recentPayments] = await Promise.all([
    resolveActivePlan(institutionId),
    getEffectiveModules(institutionId),
    paymentRepo.listForInstitution(institutionId, 5),
  ]);

  const endDate = subscription?.end_date || institution.expiration_date || null;
  let daysRemaining = null;
  if (endDate) {
    const now = new Date();
    const end = new Date(endDate);
    daysRemaining = Math.ceil((end.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
  }

  return {
    institution: {
      id: institution.id,
      name: institution.name,
      public_code: institution.public_code,
      status: institution.status,
      technical_status: institution.technical_status,
    },
    subscription,
    plan: plan
      ? {
          id: plan.id,
          code: plan.code,
          name: plan.name,
          billing_frequency: plan.billing_frequency,
          price_amount: Number(plan.price_amount),
          currency_code: plan.currency_code,
        }
      : null,
    expiration: {
      end_date: endDate,
      days_remaining: daysRemaining,
      trial_ends_at: subscription?.trial_ends_at || null,
    },
    effective_modules_count: effective.summary.effective_enabled,
    total_modules_count: effective.summary.total,
    has_overrides: effective.summary.override_count > 0,
    recent_payments: recentPayments,
  };
}

module.exports = {
  getEffectiveModules,
  replaceOverrides,
  getLicenseSummary,
  ALLOWED_OVERRIDE_MODES,
};
