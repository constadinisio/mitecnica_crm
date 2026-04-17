'use strict';

const express = require('express');
const controller = require('../../modules/plans/planController');
const planModuleController = require('../../modules/planModules/planModuleController');
const rules = require('../../modules/plans/planValidator');
const validateRequest = require('../../middlewares/validateRequest');
const authMiddleware = require('../../middlewares/authMiddleware');
const authorizeRoles = require('../../middlewares/authorizeRoles');

const router = express.Router();
router.use(authMiddleware);

router.get('/summary', authorizeRoles('support', 'commercial', 'finance', 'developer'), controller.summary);

router.get('/', authorizeRoles('support', 'commercial', 'finance', 'developer'), rules.listRules, validateRequest, controller.list);
router.get('/:id', authorizeRoles('support', 'commercial', 'finance', 'developer'), rules.idRules, validateRequest, controller.getById);
router.post('/', authorizeRoles('commercial'), rules.createRules, validateRequest, controller.create);
router.put('/:id', authorizeRoles('commercial'), rules.updateRules, validateRequest, controller.update);
router.patch('/:id/status', authorizeRoles('commercial'), rules.statusRules, validateRequest, controller.changeStatus);

// Nested plan-modules resource
router.get('/:id/modules', authorizeRoles('support', 'commercial', 'finance', 'developer'), planModuleController.idRules, validateRequest, planModuleController.listForPlan);
router.put('/:id/modules', authorizeRoles('commercial'), planModuleController.setRules, validateRequest, planModuleController.setForPlan);

module.exports = router;
