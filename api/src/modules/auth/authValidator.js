'use strict';

const { body, query } = require('express-validator');

const loginRules = [
  body('email').isEmail().normalizeEmail().withMessage('Email is invalid'),
  body('password').isString().isLength({ min: 8, max: 128 }).withMessage('Password must have 8-128 chars'),
];

const refreshRules = [
  body('refresh_token').isString().notEmpty().withMessage('refresh_token is required'),
];

const forgotRules = [
  body('email').isEmail().normalizeEmail().withMessage('Email is invalid'),
];

const resetRules = [
  body('token').isString().notEmpty(),
  body('new_password').isString().isLength({ min: 8, max: 128 }),
];

const googleCallbackRules = [
  query('code').isString().notEmpty(),
];

module.exports = { loginRules, refreshRules, forgotRules, resetRules, googleCallbackRules };
