'use strict';

require('dotenv').config();

const toInt = (value, fallback) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
};

const bool = (value, fallback = false) => {
  if (value === undefined || value === null || value === '') return fallback;
  return ['1', 'true', 'yes', 'on'].includes(String(value).toLowerCase());
};

const env = {
  nodeEnv: process.env.NODE_ENV || 'development',
  port: toInt(process.env.PORT, 4000),

  db: {
    host: process.env.CRM_DB_HOST || '127.0.0.1',
    port: toInt(process.env.CRM_DB_PORT, 5432),
    name: process.env.CRM_DB_NAME || 'mitecnica_crm',
    user: process.env.CRM_DB_USER || 'mitecnica',
    password: process.env.CRM_DB_PASSWORD || 'mitecnica',
    ssl: bool(process.env.CRM_DB_SSL, false),
  },

  jwt: {
    accessSecret: process.env.JWT_SECRET || 'dev-access-secret-change-me',
    refreshSecret: process.env.JWT_REFRESH_SECRET || 'dev-refresh-secret-change-me',
    accessTtl: process.env.ACCESS_TOKEN_TTL || '15m',
    refreshTtl: process.env.REFRESH_TOKEN_TTL || '30d',
  },

  cors: {
    origin: process.env.CORS_ORIGIN || 'http://localhost:8080',
  },

  google: {
    enabled: bool(process.env.GOOGLE_OAUTH_ENABLED, Boolean(process.env.GOOGLE_CLIENT_ID && process.env.GOOGLE_CLIENT_SECRET)),
    clientId: process.env.GOOGLE_CLIENT_ID || '',
    clientSecret: process.env.GOOGLE_CLIENT_SECRET || '',
    redirectUri: process.env.GOOGLE_REDIRECT_URI || 'http://localhost:8080/auth/google/callback',
  },

  security: {
    bcryptRounds: toInt(process.env.BCRYPT_ROUNDS, 10),
    rateLimitWindowMs: toInt(process.env.RATE_LIMIT_WINDOW_MS, 15 * 60 * 1000),
    rateLimitMax: toInt(process.env.RATE_LIMIT_MAX, 300),
    rateLimitLoginMax: toInt(process.env.RATE_LIMIT_LOGIN_MAX, 20),
  },

  logging: {
    level: process.env.LOG_LEVEL || 'info',
  },

  integration: {
    tenantWebhookUrl: process.env.TENANT_WEBHOOK_URL || '',
    crmWebhookSecret: process.env.CRM_WEBHOOK_SECRET || '',
    // Preferimos `CRM_SYNC_API_KEY` (nombre alineado con el lado tenant para que
    // ambos `.env` usen idéntica variable). Mantenemos `MITECNICA_SYNC_API_KEY`
    // como fallback histórico hasta deprecarlo.
    mitecnicaSyncApiKey:
      process.env.CRM_SYNC_API_KEY || process.env.MITECNICA_SYNC_API_KEY || '',
    webhookDispatcherEnabled: bool(process.env.WEBHOOK_DISPATCHER_ENABLED, true),
    webhookDispatcherIntervalMs: toInt(process.env.WEBHOOK_DISPATCHER_INTERVAL_MS, 30000),
    webhookHttpTimeoutMs: toInt(process.env.WEBHOOK_HTTP_TIMEOUT_MS, 10000),
    webhookMaxAttempts: toInt(process.env.WEBHOOK_MAX_ATTEMPTS, 8),
  },
};

env.isProd = env.nodeEnv === 'production';
env.isDev = env.nodeEnv !== 'production';

module.exports = env;
