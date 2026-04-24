'use strict';

const crypto = require('crypto');

/**
 * Calcula firma HMAC-SHA256 sobre el raw body (string exacto que se envía).
 * Formato del header: `sha256=<hex>`. Debe coincidir con el verificador del
 * consumer (mitecnica/webhookSignature.js).
 *
 * @param {string} rawBody  JSON stringificado tal cual va en el body.
 * @param {string} secret   Secreto compartido (CRM_WEBHOOK_SECRET).
 * @returns {string}        Firma en formato `sha256=<hex>`.
 */
function computeSignature(rawBody, secret) {
  if (!secret) throw new Error('webhookSigner: secret vacío');
  const hex = crypto.createHmac('sha256', secret).update(rawBody, 'utf8').digest('hex');
  return `sha256=${hex}`;
}

module.exports = { computeSignature };
