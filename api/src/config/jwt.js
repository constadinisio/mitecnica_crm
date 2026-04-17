'use strict';

const jwt = require('jsonwebtoken');
const env = require('./env');

function signAccessToken(payload) {
  return jwt.sign(payload, env.jwt.accessSecret, {
    expiresIn: env.jwt.accessTtl,
    issuer: 'mitecnica-crm-api',
    audience: 'mitecnica-crm',
  });
}

function signRefreshToken(payload) {
  return jwt.sign(payload, env.jwt.refreshSecret, {
    expiresIn: env.jwt.refreshTtl,
    issuer: 'mitecnica-crm-api',
    audience: 'mitecnica-crm-refresh',
  });
}

function verifyAccessToken(token) {
  return jwt.verify(token, env.jwt.accessSecret, {
    issuer: 'mitecnica-crm-api',
    audience: 'mitecnica-crm',
  });
}

function verifyRefreshToken(token) {
  return jwt.verify(token, env.jwt.refreshSecret, {
    issuer: 'mitecnica-crm-api',
    audience: 'mitecnica-crm-refresh',
  });
}

module.exports = {
  signAccessToken,
  signRefreshToken,
  verifyAccessToken,
  verifyRefreshToken,
};
