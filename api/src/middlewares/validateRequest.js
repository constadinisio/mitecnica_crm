'use strict';

const { validationResult } = require('express-validator');
const ApiError = require('../utils/ApiError');

function validateRequest(req, _res, next) {
  const result = validationResult(req);
  if (result.isEmpty()) return next();

  const details = result.array({ onlyFirstError: true }).map((e) => ({
    field: e.path || e.param,
    message: e.msg,
    location: e.location,
    value: e.value,
  }));
  return next(ApiError.validation(details));
}

module.exports = validateRequest;
