'use strict';

const crypto = require('crypto');
const repo = require('./webhookOutboxRepository');
const { computeSignature } = require('./webhookSigner');
const env = require('../../config/env');
const logger = require('../../config/logger');

/**
 * Eventos soportados por el receiver (mitecnica). Si agregás uno nuevo acá,
 * tenés que agregarlo también en `api/src/modules/tenant-admin/webhookService.js`
 * del lado tenant.
 */
const SUPPORTED_EVENTS = new Set([
  'tenant.created',
  'tenant.suspended',
  'tenant.reactivated',
  'tenant.archived',
  'tenant.plan_changed',
  'tenant.modules_changed',
]);

/**
 * Backoff exponencial (en segundos) por attempt. Total: ~32h hasta dead.
 * attempts=0 → primer intento (delay 0). attempts=1 → retry en 5s. Etc.
 */
const BACKOFF_SECONDS = [0, 5, 30, 300, 1800, 7200, 21600, 86400];

function nextAttemptDelaySeconds(attempts) {
  const idx = Math.min(attempts, BACKOFF_SECONDS.length - 1);
  return BACKOFF_SECONDS[idx];
}

/**
 * Encola un evento para envío asíncrono. Si viene `trx`, se inserta dentro de
 * la transacción del service que llama (garantiza que el evento no se encole
 * si el cambio de negocio falla).
 *
 * @param {object} input
 * @param {string} input.event           Ej. 'tenant.created'
 * @param {object} input.payload         Payload del evento
 * @param {string} [input.targetUrl]     Override de target (tests); sino env
 * @param {import('knex').Knex.Transaction} [trx]
 * @returns {Promise<{webhookId: string, id: number, skipped?: boolean}>}
 */
async function enqueue({ event, payload, targetUrl = null }, trx = null) {
  if (!SUPPORTED_EVENTS.has(event)) {
    throw new Error(`webhookEmitter: evento no soportado: ${event}`);
  }
  const url = targetUrl || env.integration.tenantWebhookUrl;

  // Modo degradado: si no hay URL ni secret configurado, logueamos y salimos.
  // Esto permite correr el CRM sin integración activa (dev/test aislado).
  if (!url || !env.integration.crmWebhookSecret) {
    logger.warn(
      '[webhookEmitter] enqueue skipped (TENANT_WEBHOOK_URL o CRM_WEBHOOK_SECRET no configurados) event=%s',
      event
    );
    return { webhookId: null, id: null, skipped: true };
  }

  const webhookId = crypto.randomUUID();
  const row = await repo.create(
    {
      webhook_id: webhookId,
      event_type: event,
      payload,
      target_url: url,
      max_attempts: env.integration.webhookMaxAttempts,
    },
    trx
  );
  logger.info('[webhookEmitter] enqueued %s id=%d webhook_id=%s', event, row.id, webhookId);
  return { webhookId, id: row.id, skipped: false };
}

/**
 * Función pura de transporte: firma y hace POST. No toca DB. El caller decide
 * qué hacer con el resultado (markSent/markRetry/markDead). Diseño para que
 * sea trivialmente testeable inyectando un fetchImpl.
 *
 * @param {object} row
 * @param {object} [options]
 * @param {typeof fetch} [options.fetchImpl]  Default `globalThis.fetch`.
 * @returns {Promise<{ok: boolean, status: number|null, error?: string, retriable?: boolean}>}
 */
async function deliverOne(row, { fetchImpl = globalThis.fetch } = {}) {
  const bodyObj = {
    webhook_id: row.webhook_id,
    event: row.event_type,
    payload: row.payload,
    emitted_at: row.created_at,
  };
  const rawBody = JSON.stringify(bodyObj);
  const signature = computeSignature(rawBody, env.integration.crmWebhookSecret);

  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), env.integration.webhookHttpTimeoutMs);

  try {
    const response = await fetchImpl(row.target_url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CRM-Signature': signature,
        'X-CRM-Webhook-Id': row.webhook_id,
        'X-CRM-Event': row.event_type,
        'User-Agent': 'mitecnica-crm-webhook-emitter/1.0',
      },
      body: rawBody,
      signal: controller.signal,
    });
    const httpStatus = response.status;
    if (httpStatus >= 200 && httpStatus < 300) {
      return { ok: true, status: httpStatus };
    }
    const bodyText = await response.text().catch(() => '');
    const errorText = `HTTP ${httpStatus}: ${bodyText.slice(0, 500)}`;
    // 4xx distinto de 408/429 → sin retry (el request está mal formado, no lo arregla reintentar).
    const retriable = httpStatus >= 500 || httpStatus === 408 || httpStatus === 429;
    return { ok: false, status: httpStatus, error: errorText, retriable };
  } catch (err) {
    const errorText = err.name === 'AbortError'
      ? `timeout after ${env.integration.webhookHttpTimeoutMs}ms`
      : err.message;
    return { ok: false, status: null, error: errorText, retriable: true };
  } finally {
    clearTimeout(timer);
  }
}

/**
 * Procesa un batch de webhooks pending. Llamado por el dispatcher job.
 * No tira excepciones al caller — cada fallo de delivery se maneja dentro.
 *
 * @param {number} [limit]
 * @returns {Promise<{processed: number, sent: number, retried: number, dead: number}>}
 */
async function deliverPending(limit = 20) {
  const rows = await repo.claimBatch(limit);
  const stats = { processed: 0, sent: 0, retried: 0, dead: 0 };
  for (const row of rows) {
    stats.processed += 1;
    const attempt = row.attempts + 1;
    const result = await deliverOne(row);
    if (result.ok) {
      await repo.markSent(row.id, result.status);
      logger.info(
        '[webhookEmitter] delivered id=%d event=%s status=%d',
        row.id,
        row.event_type,
        result.status
      );
      stats.sent += 1;
      continue;
    }
    const hitCeiling = attempt >= row.max_attempts;
    const retriable = result.retriable !== false;
    if (!retriable || hitCeiling) {
      await repo.markDead(row.id, {
        lastHttpStatus: result.status,
        lastError: result.error,
      });
      stats.dead += 1;
      logger.warn(
        '[webhookEmitter] DEAD id=%d event=%s attempts=%d error=%s',
        row.id,
        row.event_type,
        attempt,
        result.error
      );
      continue;
    }
    const delaySec = nextAttemptDelaySeconds(attempt);
    const nextAttemptAt = new Date(Date.now() + delaySec * 1000);
    await repo.markRetry(row.id, {
      nextAttemptAt,
      lastHttpStatus: result.status,
      lastError: result.error,
    });
    stats.retried += 1;
    logger.warn(
      '[webhookEmitter] retry id=%d event=%s attempt=%d next=+%ds error=%s',
      row.id,
      row.event_type,
      attempt,
      delaySec,
      result.error
    );
  }
  return stats;
}

module.exports = {
  enqueue,
  deliverPending,
  deliverOne, // exportado para tests
  SUPPORTED_EVENTS,
  BACKOFF_SECONDS,
};
