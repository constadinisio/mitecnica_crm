'use strict';

const express = require('express');
const controller = require('../../modules/subscriptions/subscriptionController');
const rules = require('../../modules/subscriptions/subscriptionValidator');
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
router.patch('/:id/status', authorizeRoles('commercial', 'support'), rules.statusRules, validateRequest, controller.changeStatus);

module.exports = router;
