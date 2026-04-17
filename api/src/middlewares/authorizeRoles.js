'use strict';

const ApiError = require('../utils/ApiError');

function authorizeRoles(...allowed) {
  const allowList = allowed.flat().map((r) => String(r).toLowerCase());
  return function (req, _res, next) {
    if (!req.auth || !req.auth.roleKey) return next(ApiError.unauthorized('Authentication required'));
    const roleKey = String(req.auth.roleKey).toLowerCase();
    if (roleKey === 'superadmin') return next();
    if (allowList.length === 0) return next();
    if (!allowList.includes(roleKey)) return next(ApiError.forbidden('Insufficient permissions'));
    return next();
  };
}

module.exports = authorizeRoles;
