'use strict';

const express = require('express');
const authRoutes = require('./authRoutes');
const dashboardRoutes = require('./dashboardRoutes');
const institutionRoutes = require('./institutionRoutes');
const auditRoutes = require('./auditRoutes');

const router = express.Router();

router.get('/', (_req, res) => res.json({
  status: 'success',
  data: { version: 'v1', name: 'mi-tecnica-crm-api' },
  errors: null,
  meta: {},
}));

router.use('/auth', authRoutes);
router.use('/dashboard', dashboardRoutes);
router.use('/institutions', institutionRoutes);
router.use('/audit-logs', auditRoutes);

module.exports = router;
