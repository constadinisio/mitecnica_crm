'use strict';

/**
 * Tests de webhookEmitter (signer + service). Usa el test runner built-in de
 * Node 18+ (`node --test`). Zero deps.
 *
 * Corré con: `npm test`
 */

const test = require('node:test');
const assert = require('node:assert/strict');
const crypto = require('node:crypto');

const { computeSignature } = require('../src/modules/webhookEmitter/webhookSigner');

// -----------------------------------------------------------------------------
// Signer — tests puros
// -----------------------------------------------------------------------------

test('computeSignature → formato sha256=<hex64>', () => {
  const sig = computeSignature('{"foo":"bar"}', 'supersecret');
  assert.match(sig, /^sha256=[a-f0-9]{64}$/);
});

test('computeSignature → determinístico', () => {
  const body = '{"event":"tenant.created"}';
  const sig1 = computeSignature(body, 'secret1');
  const sig2 = computeSignature(body, 'secret1');
  assert.equal(sig1, sig2);
});

test('computeSignature → distinto secret genera distinta firma', () => {
  const body = '{"x":1}';
  const a = computeSignature(body, 'secretA');
  const b = computeSignature(body, 'secretB');
  assert.notEqual(a, b);
});

test('computeSignature → coincide con verificador mitecnica (regex + HMAC)', () => {
  // Replicamos la verificación exacta del middleware del tenant:
  // 1. Regex /^sha256=([a-f0-9]{64})$/i
  // 2. HMAC-SHA256(rawBody, secret) hex
  const secret = 'compartido-entre-crm-y-tenant';
  const raw = JSON.stringify({ event: 'tenant.created', payload: { codigo: 'et20' } });

  const crmSignature = computeSignature(raw, secret);
  const match = /^sha256=([a-f0-9]{64})$/i.exec(crmSignature);
  assert.ok(match, 'formato debe ser reconocido por el verificador');

  const provided = Buffer.from(match[1], 'hex');
  const expected = Buffer.from(
    crypto.createHmac('sha256', secret).update(raw).digest('hex'),
    'hex'
  );
  assert.ok(crypto.timingSafeEqual(provided, expected));
});

test('computeSignature → secret vacío lanza error', () => {
  assert.throws(() => computeSignature('body', ''), /secret vacío/);
});

// -----------------------------------------------------------------------------
// Service — deliverOne con fetchImpl inyectado (evita toquetear global)
// -----------------------------------------------------------------------------

function loadEmitter({ secret = 'test-secret', url = 'https://tenant.example.com/webhook' } = {}) {
  process.env.CRM_WEBHOOK_SECRET = secret;
  process.env.TENANT_WEBHOOK_URL = url;
  process.env.WEBHOOK_HTTP_TIMEOUT_MS = '500';
  process.env.WEBHOOK_MAX_ATTEMPTS = '3';
  delete require.cache[require.resolve('../src/config/env')];
  delete require.cache[require.resolve('../src/modules/webhookEmitter/webhookEmitterService')];
  return require('../src/modules/webhookEmitter/webhookEmitterService');
}

function buildRow(overrides = {}) {
  return {
    id: 1,
    webhook_id: '11111111-1111-1111-1111-111111111111',
    event_type: 'tenant.created',
    payload: { codigo: 'et20' },
    target_url: 'https://tenant.example.com/webhook',
    created_at: new Date().toISOString(),
    attempts: 0,
    max_attempts: 3,
    ...overrides,
  };
}

test('deliverOne → 2xx marca ok y devuelve status', async () => {
  const emitter = loadEmitter();
  let capturedHeaders = null;
  let capturedBody = null;
  const fetchImpl = async (url, opts) => {
    capturedHeaders = opts.headers;
    capturedBody = opts.body;
    return new Response('ok', { status: 200 });
  };

  const res = await emitter.deliverOne(buildRow(), { fetchImpl });
  assert.equal(res.ok, true);
  assert.equal(res.status, 200);
  assert.ok(capturedHeaders['X-CRM-Signature'].startsWith('sha256='));
  assert.equal(capturedHeaders['X-CRM-Webhook-Id'], '11111111-1111-1111-1111-111111111111');
  assert.equal(capturedHeaders['X-CRM-Event'], 'tenant.created');
  const parsed = JSON.parse(capturedBody);
  assert.equal(parsed.event, 'tenant.created');
  assert.deepEqual(parsed.payload, { codigo: 'et20' });
});

test('deliverOne → 500 marca retriable=true', async () => {
  const emitter = loadEmitter();
  const fetchImpl = async () => new Response('boom', { status: 500 });
  const res = await emitter.deliverOne(buildRow({ id: 2, event_type: 'tenant.suspended' }), { fetchImpl });
  assert.equal(res.ok, false);
  assert.equal(res.status, 500);
  assert.equal(res.retriable, true);
});

test('deliverOne → 400 marca retriable=false (no reintenta)', async () => {
  const emitter = loadEmitter();
  const fetchImpl = async () => new Response('bad', { status: 400 });
  const res = await emitter.deliverOne(buildRow({ id: 3, event_type: 'tenant.archived' }), { fetchImpl });
  assert.equal(res.ok, false);
  assert.equal(res.retriable, false);
});

test('deliverOne → 429 marca retriable=true', async () => {
  const emitter = loadEmitter();
  const fetchImpl = async () => new Response('rate limited', { status: 429 });
  const res = await emitter.deliverOne(buildRow({ id: 4 }), { fetchImpl });
  assert.equal(res.retriable, true);
});

test('deliverOne → network error es retriable', async () => {
  const emitter = loadEmitter();
  const fetchImpl = async () => { throw new Error('ECONNREFUSED'); };
  const res = await emitter.deliverOne(buildRow({ id: 5, event_type: 'tenant.plan_changed' }), { fetchImpl });
  assert.equal(res.ok, false);
  assert.equal(res.retriable, true);
  assert.match(res.error, /ECONNREFUSED/);
});

test('deliverOne → AbortError por timeout es retriable', async () => {
  const emitter = loadEmitter();
  const fetchImpl = async (_url, opts) => {
    await new Promise((resolve, reject) => {
      opts.signal?.addEventListener('abort', () => {
        const err = new Error('aborted');
        err.name = 'AbortError';
        reject(err);
      });
    });
  };
  const res = await emitter.deliverOne(buildRow({ id: 6 }), { fetchImpl });
  assert.equal(res.ok, false);
  assert.equal(res.retriable, true);
  assert.match(res.error, /timeout/);
});

test('enqueue → evento no soportado lanza', async () => {
  const emitter = loadEmitter();
  await assert.rejects(
    emitter.enqueue({ event: 'tenant.exploded', payload: {} }),
    /no soportado/
  );
});

test('enqueue → sin config (secret/url vacíos) → skipped=true', async () => {
  const emitter = loadEmitter({ secret: '', url: '' });
  const result = await emitter.enqueue({ event: 'tenant.created', payload: { codigo: 'x' } });
  assert.equal(result.skipped, true);
  assert.equal(result.webhookId, null);
});

test('BACKOFF_SECONDS → progresión monotónica creciente', () => {
  const emitter = loadEmitter();
  const b = emitter.BACKOFF_SECONDS;
  for (let i = 1; i < b.length; i += 1) {
    assert.ok(b[i] >= b[i - 1], `backoff[${i}]=${b[i]} debería ser >= ${b[i - 1]}`);
  }
});
