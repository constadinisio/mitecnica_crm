'use strict';

class ApiError extends Error {
  constructor(statusCode, message, { code = null, details = null, cause = null } = {}) {
    super(message);
    this.name = 'ApiError';
    this.statusCode = statusCode;
    this.code = code;
    this.details = details;
    this.isOperational = true;
    if (cause) this.cause = cause;
    Error.captureStackTrace?.(this, this.constructor);
  }

  static badRequest(message = 'Bad request', details = null) {
    return new ApiError(400, message, { code: 'BAD_REQUEST', details });
  }

  static unauthorized(message = 'Unauthorized') {
    return new ApiError(401, message, { code: 'UNAUTHORIZED' });
  }

  static forbidden(message = 'Forbidden') {
    return new ApiError(403, message, { code: 'FORBIDDEN' });
  }

  static notFound(message = 'Not found') {
    return new ApiError(404, message, { code: 'NOT_FOUND' });
  }

  static conflict(message = 'Conflict', details = null) {
    return new ApiError(409, message, { code: 'CONFLICT', details });
  }

  static validation(details) {
    return new ApiError(422, 'Validation failed', { code: 'VALIDATION', details });
  }

  static internal(message = 'Internal server error', cause = null) {
    return new ApiError(500, message, { code: 'INTERNAL', cause });
  }
}

module.exports = ApiError;
