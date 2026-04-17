'use strict';

/**
 * Public routes — NO authentication required.
 * Every mutating route here MUST go through `publicContactLimiter` and express-validator.
 */

const express = require('express');
const controller = require('../../modules/publicContact/publicContactController');
const rules = require('../../modules/publicContact/publicContactValidator');
const validateRequest = require('../../middlewares/validateRequest');
const { publicContactLimiter } = require('../../middlewares/rateLimiter');

const router = express.Router();

router.get('/plans', controller.plans);
router.post('/contact-requests', publicContactLimiter, rules.submitRules, validateRequest, controller.submit);

module.exports = router;
