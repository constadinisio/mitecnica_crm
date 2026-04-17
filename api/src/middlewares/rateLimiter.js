'use strict';

const rateLimit = require('express-rate-limit');
const env = require('../config/env');
const apiResponse = require('../utils/apiResponse');

const globalLimiter = rateLimit({
  windowMs: env.security.rateLimitWindowMs,
  max: env.security.rateLimitMax,
  standardHeaders: true,
  legacyHeaders: false,
  handler: (_req, res) => apiResponse.error(res, {
    status: 429,
    code: 'RATE_LIMIT',
    message: 'Too many requests, please try again later',
  }),
});

const loginLimiter = rateLimit({
  windowMs: env.security.rateLimitWindowMs,
  max: env.security.rateLimitLoginMax,
  skipSuccessfulRequests: true,
  standardHeaders: true,
  legacyHeaders: false,
  handler: (_req, res) => apiResponse.error(res, {
    status: 429,
    code: 'RATE_LIMIT_LOGIN',
    message: 'Too many login attempts, please wait a few minutes',
  }),
});

module.exports = { globalLimiter, loginLimiter };
