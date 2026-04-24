#!/usr/bin/env node
/* eslint-disable no-console */
/**
 * smokeTestIntegration.cjs — Smoke end-to-end del lado CRM.
 *
 * Qué valida:
 *   1. enqueue() desde service → outbox.
 *   2. webhookDispatcher procesa pending.
 *   3. Firma HMAC llega al receiver en formato esperado (sha256=<hex>).
 *   4. Body contiene event/payload/webhook_id.
 *   5. Endpoint /integration/institutions/sync-feed con API key funciona.
 *
 * No depende de mitecnica. Levanta un mock HTTP receiver acá mismo y apunta
 * TENANT_WEBHOOK_URL a él. Pensado para correr en CI o local como sanity check
 * antes del onboarding real (ese requiere el runbook).
 *
 * Uso:
 *   npm run migrate   # asegurate que crm_webhook_outbox exista
 *   node scripts/smokeTestIntegration.cjs
 *
 * Requiere:
 *   - DB del CRM accesible (las env vars CRM_DB_*).
 *   - Puerto 4999 libre (receiver mock).
 *
 * Exit codes:
 *   0 = todo OK
 *   1 = algún check falló
 */

require('dotenv').config({ path: require('path').resolve(__dirname, '../.env') });

const crypto = require('node:crypto');
const http = require('node:http');

// Forzamos env para que el emitter apunte al mock y el dispatcher corra rápido.
const MOCK_PORT = 4999;
const MOCK_SECRET = 'smoke-secret-' + crypto.randomBytes(8).toString('hex');
const MOCK_API_KEY = 'smoke-api-key-' + crypto.randomBytes(16).toString('hex');
process.env.TENANT_WEBHOOK_URL = `http://127.0.0.1:${MOCK_PORT}/webhook`;
process.env.CRM_WEBHOOK_SECRET = MOCK_SECRET;
process.env.MITECNICA_SYNC_API_KEY = MOCK_API_KEY;
process.env.WEBHOOK_DISPATCHER_INTERVAL_MS = '500';
process.env.WEBHOOK_DISPATCHER_ENABLED = 'true';
process.env.WEBHOOK_HTTP_TIMEOUT_MS = '3000';

// Requires después de setear env (env.js hace cache en su primer require).
const { db, verifyConnection, closeConnection } = require('../src/config/db');
const webhookEmitterService = require('../src/modules/webhookEmitter/webhookEmitterService');
const dispatcher = require('../src/jobs/webhookDispatcher');
const institutionService = require('../src/modules/institutions/institutionService');
const { computeSignature } = require('../src/modules/webhookEmitter/webhookSigner');

function log(tag, ...args) {
  console.log(`[smoke:${tag}]`, ...args);
}

function fail(msg) {
  console.error(`[smoke:FAIL] ${msg}`);
  process.exit(1);
}

// -----------------------------------------------------------------------------
// Mock HTTP receiver — simula mitecnica
// -----------------------------------------------------------------------------

function startMockReceiver() {
  const received = [];
  const server = http.createServer((req, res) => {
    if (req.method !== 'POST' || req.url !== '/webhook') {
      res.writeHead(404).end();
      return;
    }
    let rawBody = '';
    req.on('data', (chunk) => { rawBody += chunk; });
    req.on('end', () => {
      received.push({
        signature: req.headers['x-crm-signature'],
        webhookId: req.headers['x-crm-webhook-id'],
        event: req.headers['x-crm-event'],
        rawBody,
        body: JSON.parse(rawBody),
      });
      res.writeHead(200, { 'content-type': 'application/json' });
      res.end(JSON.stringify({ status: 'ok', received: true }));
    });
  });
  return new Promise((resolve) => {
    server.listen(MOCK_PORT, '127.0.0.1', () => {
      log('receiver', `mock escuchando en puerto ${MOCK_PORT}`);
      resolve({ server, received });
    });
  });
}

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

async function main() {
  log('init', 'verificando conexión a DB...');
  await verifyConnection();

  const { server, received } = await startMockReceiver();

  let exitCode = 0;

  try {
    // 1) Encolar manualmente un tenant.created (no requiere crear institución real)
    log('step', '1/4 — enqueue tenant.created sintético');
    const testPayload = {
      crm_id: 99999,
      codigo: 'smoke-' + crypto.randomBytes(4).toString('hex'),
      nombre: 'Smoke Test Institution',
      subdomain: 'smoke-test',
      plan: 'basic',
      modulos_activos: ['core'],
    };
    const enqueueResult = await webhookEmitterService.enqueue({
      event: 'tenant.created',
      payload: testPayload,
    });
    if (enqueueResult.skipped) fail('enqueue skipped — revisá env vars');
    log('step', `  → outbox id=${enqueueResult.id} webhook_id=${enqueueResult.webhookId}`);

    // 2) Correr el dispatcher una vez manual (en vez de esperar el setInterval)
    log('step', '2/4 — dispatcher.tick() para enviar pending');
    await dispatcher.tick();

    // Darle un pequeño margen por si el receiver tarda un ms en completar.
    await new Promise((r) => setTimeout(r, 200));

    // 3) Validar que el mock receiver recibió el webhook firmado
    log('step', '3/4 — validando recepción en el mock');
    if (received.length === 0) {
      fail('el mock receiver NO recibió ningún webhook');
    }
    const last = received[received.length - 1];
    log('step', `  → received ${received.length} webhook(s), último event=${last.event}`);

    // 3a) Firma matchea
    const expectedSig = computeSignature(last.rawBody, MOCK_SECRET);
    if (last.signature !== expectedSig) {
      fail(`firma no coincide. esperado=${expectedSig.slice(0, 20)}... recibido=${last.signature?.slice(0, 20)}...`);
    }
    log('step', '  → firma HMAC OK');

    // 3b) Body correcto
    if (last.event !== 'tenant.created') fail(`event header=${last.event}`);
    if (last.body.event !== 'tenant.created') fail('body.event incorrecto');
    if (last.body.webhook_id !== enqueueResult.webhookId) fail('webhook_id no matchea');
    if (last.body.payload.codigo !== testPayload.codigo) fail('payload.codigo no matchea');
    log('step', '  → body OK');

    // 3c) Estado en outbox = sent
    const outboxRow = await db('crm_webhook_outbox').where({ id: enqueueResult.id }).first();
    if (!outboxRow) fail('row no encontrada en outbox');
    if (outboxRow.status !== 'sent') fail(`outbox.status=${outboxRow.status} (esperado 'sent')`);
    if (outboxRow.last_http_status !== 200) fail(`outbox.last_http_status=${outboxRow.last_http_status}`);
    log('step', `  → outbox row status=sent last_http_status=200`);

    // 4) Endpoint sync-feed funciona con API key
    log('step', '4/4 — GET /integration/institutions/sync-feed');
    // Arrancamos app en un puerto separado para no colisionar.
    const app = require('../src/app');
    const apiServer = await new Promise((resolve) => {
      const s = app.listen(0, '127.0.0.1', () => resolve(s));
    });
    const { port } = apiServer.address();

    const syncUrl = `http://127.0.0.1:${port}/api/v1/integration/institutions/sync-feed?limit=5`;
    const okRes = await fetch(syncUrl, {
      headers: { authorization: `Bearer ${MOCK_API_KEY}` },
    });
    if (okRes.status !== 200) fail(`sync-feed con key correcta: status=${okRes.status}`);
    const okBody = await okRes.json();
    if (okBody.status !== 'success') fail(`sync-feed body.status=${okBody.status}`);
    log('step', `  → sync-feed OK, ${okBody.data.length} items, next_cursor=${okBody.meta.next_cursor ? 'sí' : 'no'}`);

    const badRes = await fetch(syncUrl, {
      headers: { authorization: 'Bearer wrong-key' },
    });
    if (badRes.status !== 401) fail(`sync-feed con key incorrecta: status=${badRes.status} (esperado 401)`);
    log('step', '  → sync-feed rechaza key incorrecta (401)');

    apiServer.close();

    log('done', '✓ todos los checks pasaron');
  } catch (err) {
    console.error('[smoke:ERROR]', err.stack || err.message);
    exitCode = 1;
  } finally {
    dispatcher.stop();
    server.close();
    await closeConnection().catch(() => {});
  }

  process.exit(exitCode);
}

main();
