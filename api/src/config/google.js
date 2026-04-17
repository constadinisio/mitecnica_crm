'use strict';

const env = require('./env');
const logger = require('./logger');

let client = null;

function getClient() {
  if (!env.google.enabled) return null;
  if (!env.google.clientId || !env.google.clientSecret) {
    logger.warn('[google] OAuth enabled but credentials missing');
    return null;
  }
  if (client) return client;

  try {
    const { OAuth2Client } = require('google-auth-library');
    client = new OAuth2Client({
      clientId: env.google.clientId,
      clientSecret: env.google.clientSecret,
      redirectUri: env.google.redirectUri,
    });
    return client;
  } catch (err) {
    logger.warn('[google] google-auth-library not available: %s', err.message);
    return null;
  }
}

function isEnabled() {
  return Boolean(getClient());
}

function getAuthUrl(state = '') {
  const c = getClient();
  if (!c) return null;
  return c.generateAuthUrl({
    access_type: 'offline',
    prompt: 'consent',
    scope: ['openid', 'email', 'profile'],
    state,
  });
}

async function getUserInfoFromCode(code) {
  const c = getClient();
  if (!c) throw new Error('Google OAuth not configured');
  const { tokens } = await c.getToken(code);
  c.setCredentials(tokens);
  const ticket = await c.verifyIdToken({
    idToken: tokens.id_token,
    audience: env.google.clientId,
  });
  const payload = ticket.getPayload();
  return {
    googleId: payload.sub,
    email: payload.email,
    name: payload.name,
    picture: payload.picture,
    emailVerified: payload.email_verified,
  };
}

module.exports = { getClient, isEnabled, getAuthUrl, getUserInfoFromCode };
