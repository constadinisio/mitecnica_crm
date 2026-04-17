'use strict';

/**
 * Public contact façade. Delegates to the leads module so the public form
 * only ever creates a `contact_requests` row — institutions/subscriptions are
 * created later by commercial when they convert the lead.
 */

const leadsService = require('../leads/leadService');
const plansRepo = require('../plans/planRepository');

async function submit(payload, meta) {
  return leadsService.submitPublic(payload, meta);
}

async function listActivePlans() {
  const plans = await plansRepo.listActive();
  return plans.map((p) => ({
    code: p.code,
    name: p.name,
    description: p.description,
    price_amount: Number(p.price_amount),
    currency_code: p.currency_code,
    billing_frequency: p.billing_frequency,
  }));
}

module.exports = { submit, listActivePlans };
