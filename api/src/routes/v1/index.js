'use strict';

const express = require('express');
const authRoutes = require('./authRoutes');
const healthRoutes = require('./healthRoutes');
const dashboardRoutes = require('./dashboardRoutes');
const institutionRoutes = require('./institutionRoutes');
const auditRoutes = require('./auditRoutes');
const planRoutes = require('./planRoutes');
const moduleCatalogRoutes = require('./moduleCatalogRoutes');
const planModuleRoutes = require('./planModuleRoutes');
const subscriptionRoutes = require('./subscriptionRoutes');
const paymentRoutes = require('./paymentRoutes');
const publicRoutes = require('./publicRoutes');
const leadRoutes = require('./leadRoutes');
const tenantIntegrationRoutes = require('./tenantIntegrationRoutes');

const router = express.Router();

router.get('/', (_req, res) => res.json({
  status: 'success',
  data: { version: 'v1', name: 'mi-tecnica-crm-api' },
  errors: null,
  meta: {},
}));

router.use('/health', healthRoutes);
router.use('/auth', authRoutes);
router.use('/dashboard', dashboardRoutes);
router.use('/institutions', institutionRoutes);
router.use('/audit-logs', auditRoutes);
router.use('/plans', planRoutes);
router.use('/modules-catalog', moduleCatalogRoutes);
router.use('/plan-modules', planModuleRoutes);
router.use('/subscriptions', subscriptionRoutes);
router.use('/payments', paymentRoutes);
router.use('/public', publicRoutes);
router.use('/leads', leadRoutes);
router.use('/integration', tenantIntegrationRoutes);

module.exports = router;
