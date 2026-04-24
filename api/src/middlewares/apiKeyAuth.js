'use strict';

const env = require('../config/env');
const logger = require('../config/logger');
const ApiError = require('../utils/ApiError');

/**
 * Middleware de auth server-to-server via API key estática.
 *
 * Uso: rutas consumidas por otro servicio (ej. el job de reconciliación del
 * tenant), donde no hay usuario humano para un JWT. La clave se comparte
 * fuera de banda y se guarda como env var.
 *
 * Formato del header: `Authorization: Bearer <key>`.
 *
 * Validaciones:
 *   1. Debe existir `MITECNICA_SYNC_API_KEY` en env. Si falta, 500.
 *   2. Header presente y con prefijo Bearer. Si no, 401.
 *   3. Comparación timing-safe. Si no matchea, 401.
 */
function apiKeyAuth(req, _res, next) {
  try {
    const expected = env.integration.mitecnicaSyncApiKey;
    if (!expected) {
      logger.error('[apiKeyAuth] MITECNICA_SYNC_API_KEY no configurado');
      return next(ApiError.internal('api_key_not_configured'));
    }

    const header = req.headers.authorization || '';
    if (!header.startsWith('Bearer ')) {
      return next(ApiError.unauthorized('Missing API key'));
    }
    const provided = header.slice(7).trim();

    // Comparación timing-safe: evita side-channel por longitud.
    if (!constantTimeEqual(provided, expected)) {
      return next(ApiError.unauthorized('Invalid API key'));
    }

    req.auth = { source: 'api_key', actor: 'mitecnica-sync' };
    return next();
  } catch (err) {
    return next(err);
  }
}

function constantTimeEqual(a, b) {
  if (typeof a !== 'string' || typeof b !== 'string') return false;
  if (a.length !== b.length) return false;
  let diff = 0;
  for (let i = 0; i < a.length; i += 1) {
    diff |= a.charCodeAt(i) ^ b.charCodeAt(i);
  }
  return diff === 0;
}

module.exports = apiKeyAuth;
