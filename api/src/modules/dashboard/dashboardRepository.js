'use strict';

const { db } = require('../../config/db');
const institutionsRepo = require('../institutions/institutionRepository');
const auditRepo = require('../audit/auditRepository');
const subscriptionRepo = require('../subscriptions/subscriptionRepository');
const paymentRepo = require('../payments/paymentRepository');

const LIVE_SUBSCRIPTION_STATUSES = ['trial', 'active'];

/**
 * Count live subscriptions grouped by plan (includes plan metadata).
 */
async function countLiveSubscriptionsByPlan() {
  return db('subscriptions')
    .leftJoin('plans', 'subscriptions.plan_id', 'plans.id')
    .whereIn('subscriptions.status', LIVE_SUBSCRIPTION_STATUSES)
    .groupBy('plans.id', 'plans.code', 'plans.name', 'plans.billing_frequency', 'plans.price_amount', 'plans.currency_code')
    .select(
      'plans.id as plan_id',
      'plans.code as plan_code',
      'plans.name as plan_name',
      'plans.billing_frequency',
      'plans.price_amount',
      'plans.currency_code',
    )
    .count({ institutions: 'subscriptions.id' })
    .orderBy('plans.price_amount', 'desc');
}

/**
 * Compute a very simple MRR estimate from live subscriptions.
 * Normalizes billing frequencies into a monthly equivalent for display only.
 */
function normalizeMonthlyAmount(amount, frequency) {
  const value = Number(amount) || 0;
  switch (frequency) {
    case 'monthly':   return value;
    case 'quarterly': return value / 3;
    case 'yearly':    return value / 12;
    default:          return value; // 'custom' treated as monthly
  }
}

async function estimateMRR() {
  const rows = await countLiveSubscriptionsByPlan();
  const byCurrency = {};
  rows.forEach((r) => {
    const monthly = normalizeMonthlyAmount(r.price_amount, r.billing_frequency) * Number(r.institutions);
    const currency = r.currency_code || 'ARS';
    byCurrency[currency] = (byCurrency[currency] || 0) + monthly;
  });
  return Object.entries(byCurrency).map(([currency_code, amount]) => ({
    currency_code,
    amount: Math.round(amount * 100) / 100,
  }));
}

/**
 * Aggregate summary (legacy shape kept intact; commercial bucket is additive).
 */
async function summary() {
  const [
    countByStatus, countByTech, total, upcoming, recentInstitutions, recentActivity,
    planBuckets, mrr, recentPayments, paymentTotals, subscriptionsByStatus, subscriptionExpirations,
  ] = await Promise.all([
    institutionsRepo.countByStatus(),
    institutionsRepo.countByTechnicalStatus(),
    institutionsRepo.countTotal(),
    institutionsRepo.upcomingExpirations({ days: 30, limit: 8 }),
    institutionsRepo.listRecent(6),
    auditRepo.listRecent(10),
    countLiveSubscriptionsByPlan(),
    estimateMRR(),
    paymentRepo.listRecent(6),
    paymentRepo.totalsByStatus(),
    subscriptionRepo.countByStatus(),
    subscriptionRepo.upcomingExpirations({ days: 30, limit: 8 }),
  ]);

  return {
    counts: {
      total,
      by_status: {
        trial: countByStatus.trial || 0,
        active: countByStatus.active || 0,
        maintenance: countByStatus.maintenance || 0,
        suspended: countByStatus.suspended || 0,
        expired: countByStatus.expired || 0,
        inactive: countByStatus.inactive || 0,
      },
      by_technical_status: {
        pending: countByTech.pending || 0,
        optimal: countByTech.optimal || 0,
        updating: countByTech.updating || 0,
        offline: countByTech.offline || 0,
      },
    },
    upcoming_expirations: upcoming,
    recent_institutions: recentInstitutions,
    recent_activity: recentActivity,
    commercial: {
      institutions_by_plan: planBuckets.map((b) => ({
        plan_id: b.plan_id,
        plan_code: b.plan_code,
        plan_name: b.plan_name,
        billing_frequency: b.billing_frequency,
        price_amount: Number(b.price_amount || 0),
        currency_code: b.currency_code || 'ARS',
        institutions: Number(b.institutions),
      })),
      mrr_estimate: mrr,
      recent_payments: recentPayments,
      payment_totals: paymentTotals,
      subscriptions: {
        by_status: {
          trial:    subscriptionsByStatus.trial    || 0,
          active:   subscriptionsByStatus.active   || 0,
          suspended:subscriptionsByStatus.suspended|| 0,
          expired:  subscriptionsByStatus.expired  || 0,
          canceled: subscriptionsByStatus.canceled || 0,
        },
        upcoming_expirations: subscriptionExpirations,
      },
    },
  };
}

/**
 * Lightweight operational snapshot. Small counters that describe
 * "how busy the CRM has been recently" — not DevOps/infra observability.
 */
async function operationalSummary({ windowHours = 24, recentDays = 7 } = {}) {
  const since = new Date(Date.now() - windowHours * 60 * 60 * 1000);
  const sinceDays = new Date(Date.now() - recentDays * 24 * 60 * 60 * 1000);

  const [
    auditLast,
    loginFailuresLast,
    authActivityLast,
    institutionsRecent,
    paymentsRecent,
    overridesActive,
    usersActive,
  ] = await Promise.all([
    db('audit_logs').where('created_at', '>=', since).count({ c: '*' }).first(),
    db('audit_logs')
      .where('created_at', '>=', since)
      .whereIn('action', ['auth.login_failed', 'auth.password_reset_failed'])
      .count({ c: '*' })
      .first(),
    db('audit_logs')
      .where('created_at', '>=', since)
      .where('entity', 'auth')
      .count({ c: '*' })
      .first(),
    db('institutions').where('created_at', '>=', sinceDays).count({ c: '*' }).first(),
    db('payments').where('created_at', '>=', sinceDays).count({ c: '*' }).first(),
    db('institution_modules').count({ c: '*' }).first().catch(() => ({ c: 0 })),
    db('crm_users').where({ is_active: true }).count({ c: '*' }).first().catch(() => ({ c: 0 })),
  ]);

  return {
    window: { hours: windowHours, recent_days: recentDays, since: since.toISOString(), since_days: sinceDays.toISOString() },
    audit_events_last_window: Number(auditLast?.c || 0),
    auth_events_last_window: Number(authActivityLast?.c || 0),
    login_failures_last_window: Number(loginFailuresLast?.c || 0),
    institutions_created_recent: Number(institutionsRecent?.c || 0),
    payments_created_recent: Number(paymentsRecent?.c || 0),
    active_overrides: Number(overridesActive?.c || 0),
    active_crm_users: Number(usersActive?.c || 0),
    generated_at: new Date().toISOString(),
  };
}

module.exports = { summary, operationalSummary };
