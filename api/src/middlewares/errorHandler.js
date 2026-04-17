'use strict';

const logger = require('../config/logger');
const env = require('../config/env');
const ApiError = require('../utils/ApiError');
const apiResponse = require('../utils/apiResponse');

// eslint-disable-next-line no-unused-vars
function errorHandler(err, req, res, _next) {
  const isApi = err instanceof ApiError;
  const status = isApi ? err.statusCode : err.status || 500;
  const code = isApi ? err.code : 'INTERNAL';
  const message = isApi ? err.message : (status >= 500 ? 'Internal server error' : err.message || 'Error');
  const details = isApi ? err.details : null;

  if (status >= 500) {
    logger.error('[error] %s %s — %s\n%s', req.method, req.originalUrl, err.message, err.stack);
  } else {
    logger.warn('[warn] %s %s — %s', req.method, req.originalUrl, err.message);
  }

  const payload = { status, message, code, details };
  if (env.isDev && status >= 500) payload.meta = { stack: err.stack?.split('\n').slice(0, 5) };

  return apiResponse.error(res, payload);
}

module.exports = errorHandler;
