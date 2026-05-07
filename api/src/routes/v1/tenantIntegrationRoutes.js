'use strict';

/**
 * Rutas de integración server-to-server consumidas por la tenant app.
 * Auth: API key estática (Bearer) — NO JWT, porque el consumidor es un job
 * automático sin usuario humano.
 */

const express = require('express');
const apiKeyAuth = require('../../middlewares/apiKeyAuth');
const syncController = require('../../modules/tenantIntegration/tenantSyncController');

const router = express.Router();

router.use(apiKeyAuth);

// GET /api/v1/integration/institutions/sync-feed
// Query params: limit (1-200, default 50), cursor, since (ISO8601)
router.get('/institutions/sync-feed', syncController.listFeed);

// POST /api/v1/integration/institutions/activity
// Body: { subdomain: string, at?: ISO8601 }
// El tenant lo dispara fire-and-forget en cada login para mantener actualizado
// `institutions.last_activity_at` en el CRM.
router.post('/institutions/activity', syncController.recordActivity);

module.exports = router;
