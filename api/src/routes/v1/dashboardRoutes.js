'use strict';

const express = require('express');
const controller = require('../../modules/dashboard/dashboardController');
const authMiddleware = require('../../middlewares/authMiddleware');
const authorizeRoles = require('../../middlewares/authorizeRoles');

const router = express.Router();

router.get(
  '/summary',
  authMiddleware,
  authorizeRoles('support', 'commercial', 'finance', 'developer'),
  controller.summary,
);

router.get(
  '/operational-summary',
  authMiddleware,
  authorizeRoles('support', 'commercial', 'finance', 'developer'),
  controller.operationalSummary,
);

module.exports = router;
