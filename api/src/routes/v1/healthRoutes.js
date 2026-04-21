'use strict';

const express = require('express');
const { db } = require('../../config/db');
const apiResponse = require('../../utils/apiResponse');
const env = require('../../config/env');

const router = express.Router();
const BOOT_AT = Date.now();

/**
 * GET /api/v1/health
 * Lightweight readiness probe with DB round-trip. No auth.
 */
router.get('/', async (_req, res) => {
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
    return res.json({
      status: 'error',
      data: payload,
      errors: [{ code: 'DB_UNAVAILABLE', message: dbError }],
      meta: {},
    });
  }
  return apiResponse.success(res, payload);
});

module.exports = router;
