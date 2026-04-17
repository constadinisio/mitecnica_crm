'use strict';

function daysFromNow(days) {
  const d = new Date();
  d.setDate(d.getDate() + days);
  return d.toISOString().slice(0, 10);
}

function isoDaysFromNow(days) {
  const d = new Date();
  d.setDate(d.getDate() + days);
  return d.toISOString();
}

/**
 * One live subscription per institution. Non-live statuses (canceled/expired) are allowed
 * as historic records. This aligns with the partial-unique index in migration 0004.
 */
const SUBSCRIPTIONS = [
  { institution_code: 'INS-2026-0001', plan_code: 'enterprise',  status: 'active', start_offset: -300, end_offset:  240, renewal_mode: 'automatic', billing_notes: 'Cliente insignia, renovación programada.' },
  { institution_code: 'INS-2026-0002', plan_code: 'basic',       status: 'trial',  start_offset: -16,  end_offset:   14, trial_ends_offset: 14, renewal_mode: 'manual',    billing_notes: 'Trial inicial, seguimiento comercial.' },
  { institution_code: 'INS-2026-0003', plan_code: 'professional',status: 'suspended', start_offset: -200, end_offset: -30, renewal_mode: 'manual',   billing_notes: 'Suspendida por mora.' },
  { institution_code: 'INS-2026-0004', plan_code: 'professional',status: 'expired',   start_offset: -380, end_offset:  -7, renewal_mode: 'manual',   billing_notes: 'Plan vencido pendiente de renovación.' },
  { institution_code: 'INS-2026-0005', plan_code: 'professional',status: 'active',    start_offset: -120, end_offset:  90, renewal_mode: 'automatic', billing_notes: 'Renovación automática Q3.' },
];

exports.seed = async function seed(knex) {
  const institutions = await knex('institutions').select('id', 'public_code');
  const plans = await knex('plans').select('id', 'code');
  const instByCode = Object.fromEntries(institutions.map((i) => [i.public_code, i.id]));
  const planByCode = Object.fromEntries(plans.map((p) => [p.code, p.id]));

  for (const s of SUBSCRIPTIONS) {
    const institutionId = instByCode[s.institution_code];
    const planId = planByCode[s.plan_code];
    if (!institutionId || !planId) continue;

    const startDate = daysFromNow(s.start_offset);
    const endDate = s.end_offset != null ? daysFromNow(s.end_offset) : null;
    const trialEndsAt = s.trial_ends_offset != null ? isoDaysFromNow(s.trial_ends_offset) : null;

    const existing = await knex('subscriptions')
      .where({ institution_id: institutionId, plan_id: planId, status: s.status })
      .first();

    const payload = {
      institution_id: institutionId,
      plan_id: planId,
      status: s.status,
      start_date: startDate,
      end_date: endDate,
      trial_ends_at: trialEndsAt,
      renewal_mode: s.renewal_mode,
      billing_notes: s.billing_notes,
    };

    if (existing) {
      await knex('subscriptions').where({ id: existing.id }).update({ ...payload, updated_at: knex.fn.now() });
    } else {
      await knex('subscriptions').insert(payload);
    }
  }
};
