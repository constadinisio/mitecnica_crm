'use strict';

const express = require('express');
const controller = require('../../modules/auth/authController');
const rules = require('../../modules/auth/authValidator');
const validateRequest = require('../../middlewares/validateRequest');
const authMiddleware = require('../../middlewares/authMiddleware');
const { loginLimiter } = require('../../middlewares/rateLimiter');

const router = express.Router();

router.post('/login', loginLimiter, rules.loginRules, validateRequest, controller.login);
router.post('/logout', controller.logout);
router.post('/refresh', rules.refreshRules, validateRequest, controller.refresh);
router.get('/me', authMiddleware, controller.me);

router.post('/forgot-password', rules.forgotRules, validateRequest, controller.forgotPassword);
router.post('/reset-password', rules.resetRules, validateRequest, controller.resetPassword);

router.get('/google', controller.googleStart);
router.get('/google/callback', rules.googleCallbackRules, validateRequest, controller.googleCallback);

module.exports = router;
