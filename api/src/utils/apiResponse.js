'use strict';

function success(res, data = null, { status = 200, meta = null } = {}) {
  return res.status(status).json({
    status: 'success',
    data,
    errors: null,
    meta: meta || {},
  });
}

function error(res, { status = 500, message = 'Internal error', code = 'INTERNAL', details = null, meta = null } = {}) {
  return res.status(status).json({
    status: 'error',
    data: null,
    errors: [{ code, message, details }],
    meta: meta || {},
  });
}

module.exports = { success, error };
