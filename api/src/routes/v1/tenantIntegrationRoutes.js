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

module.exports = router;
