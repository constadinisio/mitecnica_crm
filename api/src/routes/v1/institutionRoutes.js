'use strict';

const express = require('express');
const controller = require('../../modules/institutions/institutionController');
const rules = require('../../modules/institutions/institutionValidator');
const validateRequest = require('../../middlewares/validateRequest');
const authMiddleware = require('../../middlewares/authMiddleware');
const authorizeRoles = require('../../middlewares/authorizeRoles');

const router = express.Router();

router.use(authMiddleware);

router.get(
  '/',
  authorizeRoles('support', 'commercial', 'finance', 'developer'),
  rules.listRules,
  validateRequest,
  controller.list,
);

router.get(
  '/:id',
  authorizeRoles('support', 'commercial', 'finance', 'developer'),
  rules.idRules,
  validateRequest,
  controller.getById,
);

router.post(
  '/',
  authorizeRoles('commercial'),
  rules.createRules,
  validateRequest,
  controller.create,
);

router.put(
  '/:id',
  authorizeRoles('support', 'commercial'),
  rules.updateRules,
  validateRequest,
  controller.update,
);

router.patch(
  '/:id/status',
  authorizeRoles('support', 'commercial'),
  rules.statusRules,
  validateRequest,
  controller.changeStatus,
);

module.exports = router;
