'use strict';

const express = require('express');
const controller = require('../../modules/audit/auditController');
const rules = require('../../modules/audit/auditValidator');
const validateRequest = require('../../middlewares/validateRequest');
const authMiddleware = require('../../middlewares/authMiddleware');
const authorizeRoles = require('../../middlewares/authorizeRoles');

const router = express.Router();

router.use(authMiddleware);

router.get(
  '/',
  authorizeRoles('support', 'developer'),
  rules.listRules,
  validateRequest,
  controller.list,
);

router.get(
  '/export.csv',
  authorizeRoles('support', 'developer'),
  rules.listRules,
  validateRequest,
  controller.exportCsv,
);

router.get(
  '/:id',
  authorizeRoles('support', 'developer'),
  rules.byIdRules,
  validateRequest,
  controller.getById,
);

module.exports = router;
