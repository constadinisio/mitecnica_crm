'use strict';

const ApiError = require('../utils/ApiError');

function notFoundHandler(req, _res, next) {
  next(ApiError.notFound(`Route ${req.method} ${req.originalUrl} not found`));
}

module.exports = notFoundHandler;
