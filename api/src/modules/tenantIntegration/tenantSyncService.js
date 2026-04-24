'use strict';

const { db } = require('../../config/db');
const institutionModuleService = require('../institutionModules/institutionModuleService');
const subscriptionRepo = require('../subscriptions/subscriptionRepository');
const plansRepo = require('../plans/planRepository');
const tenantMapper = require('../webhookEmitter/tenantEventMapper');

const MAX_LIMIT = 200;
const DEFAULT_LIMIT = 50;

/**
 * Lista paginada de instituciones con licencia efectiva resuelta, en formato
 * pensado para que el job de reconciliación del tenant haga upsert directo.
 *
 * Paginación: cursor forward-only por `updated_at ASC, id ASC` (estable ante
 * inserts). El cliente manda `cursor = "<updated_at_iso>|<id>"`; devolvemos
 * `next_cursor` si hay más páginas.
 *
 * Filtro opcional: `?since=<iso8601>` para traer sólo lo actualizado desde una
 * marca de tiempo (útil para incrementales rápidos).
 */
async function listFeed({ limit = DEFAULT_LIMIT, cursor = null, since = null } = {}) {
  const effectiveLimit = Math.min(Math.max(Number(limit) || DEFAULT_LIMIT, 1), MAX_LIMIT);

  let query = db('institutions')
    .select('id', 'public_code', 'name', 'subdomain', 'status', 'expiration_date', 'updated_at')
    .orderBy('updated_at', 'asc')
    .orderBy('id', 'asc')
    .limit(effectiveLimit + 1); // +1 para detectar si hay más páginas

  if (since) {
    const sinceDate = new Date(since);
    if (!Number.isNaN(sinceDate.getTime())) {
      query = query.where('updated_at', '>=', sinceDate.toISOString());
    }
  }

  if (cursor) {
    const parsed = decodeCursor(cursor);
    if (parsed) {
      query = query.where((qb) => {
        qb.where('updated_at', '>', parsed.updatedAt)
          .orWhere((qb2) => {
            qb2.where('updated_at', '=', parsed.updatedAt).andWhere('id', '>', parsed.id);
          });
      });
    }
  }

  const rows = await query;
  const hasMore = rows.length > effectiveLimit;
  const page = hasMore ? rows.slice(0, effectiveLimit) : rows;

  // Resolver plan + módulos efectivos por institución. Lo hacemos en paralelo
  // pero acotado — para 50 institutions son ~50 roundtrips cortos.
  const enriched = await Promise.all(page.map(async (inst) => {
    const [live, effective] = await Promise.all([
      subscriptionRepo.findLiveForInstitution(inst.id),
      institutionModuleService.getEffectiveModules(inst.id).catch(() => null),
    ]);
    const plan = live ? await plansRepo.findById(live.plan_id) : null;
    const moduleCodes = effective ? tenantMapper.extractActiveModuleCodes(effective) : [];

    return {
      crm_id: inst.id,
      public_code: inst.public_code,
      codigo: inst.subdomain,
      nombre: inst.name,
      subdomain: inst.subdomain,
      status_crm: inst.status,
      estado_tenant: tenantMapper.STATUS_BUCKETS[inst.status] || null,
      plan: plan ? plan.code : null,
      modulos_activos: moduleCodes,
      expiration_date: inst.expiration_date,
      updated_at: inst.updated_at,
    };
  }));

  let nextCursor = null;
  if (hasMore) {
    const last = page[page.length - 1];
    nextCursor = encodeCursor(last.updated_at, last.id);
  }

  return {
    items: enriched,
    next_cursor: nextCursor,
    count: enriched.length,
  };
}

function encodeCursor(updatedAt, id) {
  const iso = updatedAt instanceof Date ? updatedAt.toISOString() : new Date(updatedAt).toISOString();
  return Buffer.from(`${iso}|${id}`).toString('base64url');
}

function decodeCursor(cursor) {
  try {
    const raw = Buffer.from(cursor, 'base64url').toString('utf8');
    const [iso, idStr] = raw.split('|');
    const id = Number(idStr);
    const date = new Date(iso);
    if (!iso || Number.isNaN(date.getTime()) || Number.isNaN(id)) return null;
    return { updatedAt: date.toISOString(), id };
  } catch {
    return null;
  }
}

module.exports = {
  listFeed,
  MAX_LIMIT,
  DEFAULT_LIMIT,
  // expuestos para tests
  encodeCursor,
  decodeCursor,
};
