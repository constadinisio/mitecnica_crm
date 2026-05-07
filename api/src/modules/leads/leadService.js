'use strict';

const { db } = require('../../config/db');
const ApiError = require('../../utils/ApiError');
const { slugify } = require('../../utils/slug');
const repo = require('./leadRepository');
const institutionsRepo = require('../institutions/institutionRepository');
const plansRepo = require('../plans/planRepository');
const auditService = require('../audit/auditService');

const ALLOWED_STATUS = ['new', 'contacted', 'in_negotiation', 'converted', 'lost'];
const TERMINAL_STATUS = ['converted', 'lost'];

function parseArray(v) {
  if (!v) return null;
  if (Array.isArray(v)) return v;
  return String(v).split(',').map((s) => s.trim()).filter(Boolean);
}

async function listPaginated({ query, page, limit, offset }) {
  const filters = {
    status: parseArray(query.status),
    assignedTo: query.assigned_to || null,
    search: query.search || null,
  };
  const sort = query.sort || 'created_at';
  const order = query.order || 'desc';
  const { rows, total } = await repo.list({ filters, sort, order, limit, offset });
  return { rows, total, page, limit };
}

async function getById(id) {
  const row = await repo.findById(id);
  if (!row) throw ApiError.notFound('Lead not found');
  return row;
}

/**
 * Public submission — called from the public endpoint. No actor.
 * Creates just the contact_requests row; institution/subscription are not touched.
 */
async function submitPublic(payload, { ip, userAgent }) {
  const institutionName = String(payload.institution_name || '').trim();
  const contactName = String(payload.contact_name || '').trim();
  const contactLastName = String(payload.contact_last_name || '').trim();
  const contactEmail = String(payload.contact_email || '').trim().toLowerCase();

  if (!institutionName) throw ApiError.badRequest('institution_name is required');
  if (!contactName) throw ApiError.badRequest('contact_name is required');
  if (!contactEmail) throw ApiError.badRequest('contact_email is required');

  let planCode = payload.plan_code ? String(payload.plan_code).trim().toLowerCase() : null;
  if (planCode) {
    const plan = await plansRepo.findByCode(planCode);
    if (!plan || plan.status !== 'active') planCode = null; // Ignore silently — we just want the lead
  }

  const row = await repo.create({
    institution_name: institutionName,
    contact_name: contactName,
    contact_last_name: contactLastName || null,
    contact_email: contactEmail,
    contact_phone: payload.contact_phone || null,
    address: payload.address || null,
    plan_code_requested: planCode,
    notes: payload.notes || null,
    source: 'public_form',
    ip,
    user_agent: userAgent,
    status: 'new',
  });

  await auditService.record({
    actorUserId: null,
    action: 'lead.created',
    entity: 'contact_requests',
    entityId: row.id,
    description: `Solicitud pública de ${contactName} <${contactEmail}> para "${institutionName}"` + (planCode ? ` (plan ${planCode})` : ''),
    afterData: row,
    ip,
    userAgent,
  });

  return { id: row.id, status: row.status, institution_name: row.institution_name };
}

function ensureNotTerminal(lead) {
  if (TERMINAL_STATUS.includes(lead.status)) {
    throw ApiError.conflict(`Lead already in terminal state (${lead.status})`);
  }
}

async function changeStatus(id, newStatus, { actor, ip, userAgent, reason = null }) {
  if (!ALLOWED_STATUS.includes(newStatus)) throw ApiError.badRequest(`Invalid status: ${newStatus}`);
  const existing = await repo.findById(id);
  if (!existing) throw ApiError.notFound('Lead not found');
  if (existing.status === newStatus) return existing;

  // Conversion must go through /convert endpoint so institution/subscription get created atomically.
  if (newStatus === 'converted') {
    throw ApiError.badRequest('Use the /convert endpoint to mark a lead as converted');
  }
  if (existing.status === 'converted') {
    throw ApiError.conflict('Cannot change status of a converted lead');
  }

  const row = await repo.update(id, { status: newStatus });
  await auditService.record({
    actorUserId: actor?.userId || null,
    action: 'lead.status_changed',
    entity: 'contact_requests',
    entityId: id,
    description: `Lead #${id} status: ${existing.status} → ${newStatus}${reason ? ` (${reason})` : ''}`,
    beforeData: { status: existing.status },
    afterData: { status: newStatus, reason },
    ip,
    userAgent,
  });
  return row;
}

async function assign(id, userId, { actor, ip, userAgent }) {
  const existing = await repo.findById(id);
  if (!existing) throw ApiError.notFound('Lead not found');
  ensureNotTerminal(existing);

  if (userId) {
    const user = await db('crm_users').where({ id: userId }).first();
    if (!user) throw ApiError.badRequest('User not found');
  }

  const row = await repo.update(id, { assigned_to_user_id: userId || null });
  await auditService.record({
    actorUserId: actor?.userId || null,
    action: 'lead.assigned',
    entity: 'contact_requests',
    entityId: id,
    description: userId ? `Lead #${id} asignado a usuario ${userId}` : `Lead #${id} desasignado`,
    beforeData: { assigned_to_user_id: existing.assigned_to_user_id },
    afterData: { assigned_to_user_id: userId || null },
    ip,
    userAgent,
  });
  return row;
}

async function buildUniqueSubdomain(baseName, trx) {
  const base = slugify(baseName, { maxLength: 50 }) || 'institucion';
  let candidate = base;
  let i = 2;
  while (await trx('institutions').where({ subdomain: candidate }).first()) {
    candidate = `${base}-${i}`;
    i += 1;
    if (i > 50) break;
  }
  return candidate;
}

async function buildUniqueSlug(baseName, trx) {
  const base = slugify(baseName) || 'institucion';
  let candidate = base;
  let i = 2;
  while (await trx('institutions').where({ slug: candidate }).first()) {
    candidate = `${base}-${i}`;
    i += 1;
    if (i > 50) break;
  }
  return candidate;
}

/**
 * Convert a lead into a real institution (+ optional subscription).
 * The commercial user tweaks the subdomain/slug/plan before calling this.
 */
async function convert(id, payload, { actor, ip, userAgent }) {
  const existing = await repo.findById(id);
  if (!existing) throw ApiError.notFound('Lead not found');
  if (existing.status === 'converted') throw ApiError.conflict('Lead is already converted');
  if (existing.status === 'lost') throw ApiError.conflict('Cannot convert a lost lead');

  const institutionName = String(payload.institution_name || existing.institution_name).trim();
  if (!institutionName) throw ApiError.badRequest('institution_name is required');

  const contactEmail = String(payload.contact_email || existing.contact_email).trim().toLowerCase();
  if (!contactEmail) throw ApiError.badRequest('contact_email is required');

  let plan = null;
  if (payload.plan_id) {
    plan = await plansRepo.findById(Number(payload.plan_id));
    if (!plan) throw ApiError.badRequest('Plan not found');
    if (plan.status !== 'active') throw ApiError.badRequest('Selected plan is not active');
  }

  const institutionStatus = payload.institution_status || 'trial';
  const subscriptionStatus = payload.subscription_status || 'trial';
  const createSub = payload.create_subscription !== false && plan !== null;

  const result = await db.transaction(async (trx) => {
    const subdomain = payload.subdomain
      ? slugify(String(payload.subdomain), { maxLength: 60 })
      : await buildUniqueSubdomain(institutionName, trx);
    if (!subdomain) throw ApiError.badRequest('subdomain is required');

    // Verify subdomain uniqueness if user-provided
    if (payload.subdomain) {
      const dup = await trx('institutions').where({ subdomain }).first();
      if (dup) throw ApiError.conflict('Subdomain already in use', { field: 'subdomain' });
    }

    const slug = payload.slug
      ? slugify(String(payload.slug))
      : await buildUniqueSlug(institutionName, trx);
    if (payload.slug) {
      const dup = await trx('institutions').where({ slug }).first();
      if (dup) throw ApiError.conflict('Slug already in use', { field: 'slug' });
    }

    // public_code
    const year = new Date().getFullYear();
    const prefix = `INS-${year}-`;
    const last = await trx('institutions')
      .where('public_code', 'like', `${prefix}%`)
      .orderBy('public_code', 'desc')
      .first();
    const publicCode = !last
      ? `${prefix}0001`
      : `${prefix}${String(Number(last.public_code.slice(prefix.length)) + 1).padStart(4, '0')}`;

    const [institution] = await trx('institutions').insert({
      public_code: publicCode,
      name: institutionName,
      slug,
      subdomain,
      status: institutionStatus,
      contact_email: contactEmail,
      contact_phone: payload.contact_phone ?? existing.contact_phone ?? null,
      address: payload.address ?? existing.address ?? null,
      responsible_name: payload.responsible_name ?? existing.contact_name ?? null,
      responsible_last_name: payload.responsible_last_name ?? existing.contact_last_name ?? null,
      responsible_email: payload.responsible_email ?? existing.contact_email ?? null,
      notes_internal: payload.notes_internal ?? (existing.notes ? `(Desde lead #${id}) ${existing.notes}` : `Lead #${id} convertido.`),
      current_plan_name: plan ? plan.name : null,
      technical_status: 'pending',
    }).returning('*');

    let subscription = null;
    if (createSub) {
      const startDate = payload.start_date || new Date().toISOString().slice(0, 10);
      let endDate = payload.end_date || null;
      let trialEndsAt = payload.trial_ends_at || null;
      if (subscriptionStatus === 'trial' && !trialEndsAt) {
        const d = new Date();
        d.setDate(d.getDate() + 14);
        trialEndsAt = d.toISOString();
      }

      [subscription] = await trx('subscriptions').insert({
        institution_id: institution.id,
        plan_id: plan.id,
        status: subscriptionStatus,
        start_date: startDate,
        end_date: endDate,
        trial_ends_at: trialEndsAt,
        renewal_mode: payload.renewal_mode || 'manual',
        billing_notes: payload.billing_notes || `Convertido desde lead #${id}.`,
      }).returning('*');
    }

    // Update the lead
    const [updatedLead] = await trx('contact_requests').where({ id }).update({
      status: 'converted',
      converted_institution_id: institution.id,
      converted_subscription_id: subscription ? subscription.id : null,
      converted_at: trx.fn.now(),
      updated_at: trx.fn.now(),
    }).returning('*');

    return { institution, subscription, lead: updatedLead };
  });

  await auditService.record({
    actorUserId: actor?.userId || null,
    action: 'lead.converted',
    entity: 'contact_requests',
    entityId: id,
    description: `Lead #${id} convertido a institución ${result.institution.public_code} ("${result.institution.name}")` + (result.subscription ? ` + suscripción #${result.subscription.id}` : ''),
    beforeData: { status: existing.status },
    afterData: {
      status: 'converted',
      institution_id: result.institution.id,
      subscription_id: result.subscription?.id || null,
    },
    ip,
    userAgent,
  });

  return result;
}

async function summary() {
  const counts = await repo.countByStatus();
  const recent = await repo.listRecent(6);
  const total = Object.values(counts).reduce((a, b) => a + b, 0);
  return {
    counts: {
      total,
      by_status: {
        new: counts.new || 0,
        contacted: counts.contacted || 0,
        in_negotiation: counts.in_negotiation || 0,
        converted: counts.converted || 0,
        lost: counts.lost || 0,
      },
      open: (counts.new || 0) + (counts.contacted || 0) + (counts.in_negotiation || 0),
    },
    recent,
  };
}

module.exports = {
  submitPublic, listPaginated, getById, changeStatus, assign, convert, summary,
  ALLOWED_STATUS,
};
