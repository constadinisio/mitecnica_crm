'use strict';

const { verifyAccessToken } = require('../config/jwt');
const ApiError = require('../utils/ApiError');

function extractToken(req) {
  const header = req.headers.authorization || '';
  if (header.startsWith('Bearer ')) return header.slice(7).trim();
  return null;
}

function authMiddleware(req, _res, next) {
  try {
    const token = extractToken(req);
    if (!token) throw ApiError.unauthorized('Missing access token');
    const payload = verifyAccessToken(token);
    req.auth = {
      userId: payload.sub,
      roleKey: payload.role,
      email: payload.email,
      name: payload.name,
    };
    next();
  } catch (err) {
    if (err.name === 'TokenExpiredError') return next(ApiError.unauthorized('Access token expired'));
    if (err.name === 'JsonWebTokenError') return next(ApiError.unauthorized('Invalid access token'));
    next(err);
  }
}

module.exports = authMiddleware;
