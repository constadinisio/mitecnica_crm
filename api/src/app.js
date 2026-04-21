'use strict';

const express = require('express');
const helmet = require('helmet');
const cors = require('./config/cors');
const env = require('./config/env');
const { db } = require('./config/db');
const requestLogger = require('./middlewares/requestLogger');
const { globalLimiter } = require('./middlewares/rateLimiter');
const errorHandler = require('./middlewares/errorHandler');
const notFoundHandler = require('./middlewares/notFoundHandler');
const apiResponse = require('./utils/apiResponse');
const routes = require('./routes');

const app = express();
const BOOT_AT = Date.now();

app.set('trust proxy', 1);
app.use(helmet());
app.use(cors);
app.use(express.json({ limit: '1mb' }));
app.use(express.urlencoded({ extended: true, limit: '1mb' }));
app.use(requestLogger);
app.use(globalLimiter);

/**
 * Lightweight liveness probe.
 * Kept here (outside /api/v1) so load balancers can hit it without versioning.
 */
app.get('/health', (_req, res) => apiResponse.success(res, {
  status: 'ok',
  name: 'mi-tecnica-crm-api',
  env: env.nodeEnv,
  time: new Date().toISOString(),
  uptime_seconds: Math.round((Date.now() - BOOT_AT) / 1000),
}));

/**
 * Readiness probe: verifies DB round-trip, reports uptime, env, timestamp.
 * Also exposed under /api/v1/health for uniformity.
 */
async function readiness(_req, res) {
  const started = Date.now();
  let dbStatus = 'ok';
  let dbError = null;
  try {
    await db.raw('select 1 as ok');
  } catch (err) {
    dbStatus = 'fail';
    dbError = err.message;
  }
  const payload = {
    status: dbStatus === 'ok' ? 'ok' : 'degraded',
    name: 'mi-tecnica-crm-api',
    env: env.nodeEnv,
    time: new Date().toISOString(),
    uptime_seconds: Math.round((Date.now() - BOOT_AT) / 1000),
    db: { status: dbStatus, error: dbError, check_ms: Date.now() - started },
  };
  if (dbStatus !== 'ok') {
    res.status(503);
    return res.json({ status: 'error', data: payload, errors: [{ code: 'DB_UNAVAILABLE', message: dbError }], meta: {} });
  }
  return apiResponse.success(res, payload);
}

app.get('/ready', readiness);
app.locals.readiness = readiness;

app.use('/api', routes);

app.use(notFoundHandler);
app.use(errorHandler);

module.exports = app;
