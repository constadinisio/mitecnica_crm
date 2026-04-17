'use strict';

function isoDaysFromNow(days) {
  const d = new Date();
  d.setDate(d.getDate() + days);
  return d.toISOString();
}

const PAYMENTS = [
  // Active enterprise: serie de pagos mensuales
  { institution_code: 'INS-2026-0001', plan_code: 'enterprise',   amount:  150000.00, days_offset:  -90, status: 'approved',  payment_method: 'Transferencia bancaria', reference_code: 'TRF-0001-ENT', notes: 'Cuota mensual.' },
  { institution_code: 'INS-2026-0001', plan_code: 'enterprise',   amount:  150000.00, days_offset:  -60, status: 'approved',  payment_method: 'Transferencia bancaria', reference_code: 'TRF-0002-ENT', notes: 'Cuota mensual.' },
  { institution_code: 'INS-2026-0001', plan_code: 'enterprise',   amount:  150000.00, days_offset:  -30, status: 'approved',  payment_method: 'Transferencia bancaria', reference_code: 'TRF-0003-ENT', notes: 'Cuota mensual.' },
  { institution_code: 'INS-2026-0001', plan_code: 'enterprise',   amount:  150000.00, days_offset:   -2, status: 'pending',   payment_method: 'Transferencia bancaria', reference_code: 'TRF-0004-ENT', notes: 'En conciliación.' },

  // Trial basic: sin pagos aún
  // Suspended professional: historial con rechazo
  { institution_code: 'INS-2026-0003', plan_code: 'professional', amount:   65000.00, days_offset:  -45, status: 'rejected',  payment_method: 'Tarjeta de crédito',    reference_code: 'CC-0001-PRO',  notes: 'Rechazo bancario, mora.' },

  // Expired professional: último pago fallido
  { institution_code: 'INS-2026-0004', plan_code: 'professional', amount:   65000.00, days_offset: -100, status: 'approved',  payment_method: 'Mercado Pago',          reference_code: 'MP-0001-PRO',  notes: 'Último pago OK.' },
  { institution_code: 'INS-2026-0004', plan_code: 'professional', amount:   65000.00, days_offset:   -9, status: 'expired',   payment_method: 'Link de pago',          reference_code: 'LP-0001-PRO',  notes: 'Link expiró sin uso.' },

  // Active professional Bahía: al día
  { institution_code: 'INS-2026-0005', plan_code: 'professional', amount:   65000.00, days_offset:  -75, status: 'approved',  payment_method: 'Transferencia bancaria', reference_code: 'TRF-0010-PRO', notes: 'Cuota mensual.' },
  { institution_code: 'INS-2026-0005', plan_code: 'professional', amount:   65000.00, days_offset:  -15, status: 'approved',  payment_method: 'Transferencia bancaria', reference_code: 'TRF-0011-PRO', notes: 'Cuota mensual.' },
];

exports.seed = async function seed(knex) {
  const institutions = await knex('institutions').select('id', 'public_code');
  const plans = await knex('plans').select('id', 'code');
  const subscriptions = await knex('subscriptions').select('id', 'institution_id', 'plan_id');
  const instByCode = Object.fromEntries(institutions.map((i) => [i.public_code, i.id]));
  const planByCode = Object.fromEntries(plans.map((p) => [p.code, p.id]));

  for (const p of PAYMENTS) {
    const institutionId = instByCode[p.institution_code];
    const planId = planByCode[p.plan_code];
    if (!institutionId || !planId) continue;

    const sub = subscriptions.find((s) => s.institution_id === institutionId && s.plan_id === planId);

    const payload = {
      institution_id: institutionId,
      subscription_id: sub?.id ?? null,
      amount: p.amount,
      currency_code: 'ARS',
      payment_date: isoDaysFromNow(p.days_offset),
      status: p.status,
      payment_method: p.payment_method,
      reference_code: p.reference_code,
      notes: p.notes,
      created_by_user_id: null,
    };

    const existing = await knex('payments').where({ reference_code: p.reference_code }).first();
    if (existing) {
      await knex('payments').where({ id: existing.id }).update({ ...payload, updated_at: knex.fn.now() });
    } else {
      await knex('payments').insert(payload);
    }
  }
};
