'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');

const { encodeCursor, decodeCursor } = require('../src/modules/tenantIntegration/tenantSyncService');

// -----------------------------------------------------------------------------
// Cursor codec — idempotente, resistente a input inválido
// -----------------------------------------------------------------------------

test('cursor: encode/decode roundtrip con Date', () => {
  const date = new Date('2026-04-20T15:30:00.000Z');
  const encoded = encodeCursor(date, 42);
  const decoded = decodeCursor(encoded);
  assert.equal(decoded.updatedAt, '2026-04-20T15:30:00.000Z');
  assert.equal(decoded.id, 42);
});

test('cursor: encode acepta string ISO también', () => {
  const encoded = encodeCursor('2026-01-01T00:00:00.000Z', 1);
  const decoded = decodeCursor(encoded);
  assert.equal(decoded.id, 1);
  assert.equal(decoded.updatedAt, '2026-01-01T00:00:00.000Z');
});

test('cursor: decode con string corrupto = null', () => {
  assert.equal(decodeCursor('!!!not-a-cursor!!!'), null);
});

test('cursor: decode con base64 válido pero formato interno malo = null', () => {
  const junk = Buffer.from('solo-esto-sin-separador').toString('base64url');
  assert.equal(decodeCursor(junk), null);
});

test('cursor: decode con fecha inválida = null', () => {
  const bad = Buffer.from('not-a-date|42').toString('base64url');
  assert.equal(decodeCursor(bad), null);
});

test('cursor: decode con id no numérico = null', () => {
  const bad = Buffer.from('2026-04-20T15:30:00.000Z|abc').toString('base64url');
  assert.equal(decodeCursor(bad), null);
});

test('cursor: url-safe (no +, /, =)', () => {
  // base64url evita caracteres problemáticos en query strings.
  const encoded = encodeCursor(new Date(), 1);
  assert.ok(!encoded.includes('+'));
  assert.ok(!encoded.includes('/'));
  assert.ok(!encoded.includes('='));
});

// -----------------------------------------------------------------------------
// apiKeyAuth middleware — sin DB, sin HTTP server
// -----------------------------------------------------------------------------

function loadApiKeyAuth(key) {
  process.env.MITECNICA_SYNC_API_KEY = key;
  delete require.cache[require.resolve('../src/config/env')];
  delete require.cache[require.resolve('../src/middlewares/apiKeyAuth')];
  return require('../src/middlewares/apiKeyAuth');
}

function invokeMiddleware(mw, headers) {
  return new Promise((resolve) => {
    const req = { headers };
    const res = {};
    mw(req, res, (err) => resolve({ err, req }));
  });
}

test('apiKeyAuth: sin key configurada → error 500', async () => {
  const mw = loadApiKeyAuth('');
  const { err } = await invokeMiddleware(mw, {});
  assert.ok(err);
  assert.equal(err.status || err.statusCode, 500);
});

test('apiKeyAuth: sin header → 401', async () => {
  const mw = loadApiKeyAuth('s3cret');
  const { err } = await invokeMiddleware(mw, {});
  assert.ok(err);
  assert.equal(err.status || err.statusCode, 401);
  assert.match(err.message, /Missing API key/i);
});

test('apiKeyAuth: header sin prefijo Bearer → 401', async () => {
  const mw = loadApiKeyAuth('s3cret');
  const { err } = await invokeMiddleware(mw, { authorization: 's3cret' });
  assert.ok(err);
  assert.equal(err.status || err.statusCode, 401);
});

test('apiKeyAuth: key incorrecta → 401', async () => {
  const mw = loadApiKeyAuth('s3cret');
  const { err } = await invokeMiddleware(mw, { authorization: 'Bearer wrong' });
  assert.ok(err);
  assert.equal(err.status || err.statusCode, 401);
});

test('apiKeyAuth: key incorrecta misma longitud → 401 (no leak via length)', async () => {
  const mw = loadApiKeyAuth('s3cret');
  const { err } = await invokeMiddleware(mw, { authorization: 'Bearer 6chars' });
  assert.ok(err);
  assert.equal(err.status || err.statusCode, 401);
});

test('apiKeyAuth: key correcta → next() sin error + setea req.auth', async () => {
  const mw = loadApiKeyAuth('correcto-de-64-chars-abcdef1234567890abcdef1234567890abcdef12');
  const { err, req } = await invokeMiddleware(mw, {
    authorization: 'Bearer correcto-de-64-chars-abcdef1234567890abcdef1234567890abcdef12',
  });
  assert.equal(err, undefined);
  assert.equal(req.auth.source, 'api_key');
  assert.equal(req.auth.actor, 'mitecnica-sync');
});
