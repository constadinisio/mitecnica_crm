'use strict';

const express = require('express');
const controller = require('../../modules/leads/leadController');
const rules = require('../../modules/leads/leadValidator');
const validateRequest = require('../../middlewares/validateRequest');
const authMiddleware = require('../../middlewares/authMiddleware');
const authorizeRoles = require('../../middlewares/authorizeRoles');

const router = express.Router();
router.use(authMiddleware);

router.get('/summary', authorizeRoles('support', 'commercial', 'finance', 'developer'), controller.summary);

router.get('/', authorizeRoles('support', 'commercial', 'finance'), rules.listRules, validateRequest, controller.list);
router.get('/:id', authorizeRoles('support', 'commercial', 'finance'), rules.idRules, validateRequest, controller.getById);

router.patch('/:id/status', authorizeRoles('commercial', 'support'), rules.statusRules, validateRequest, controller.changeStatus);
router.patch('/:id/assign', authorizeRoles('commercial', 'support'), rules.assignRules, validateRequest, controller.assign);
router.post('/:id/convert', authorizeRoles('commercial'), rules.convertRules, validateRequest, controller.convert);

module.exports = router;
