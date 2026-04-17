'use strict';

const service = require('./authService');
const apiResponse = require('../../utils/apiResponse');
const auditMeta = require('../../utils/auditMetadata');
const google = require('../../config/google');
const env = require('../../config/env');

async function login(req, res, next) {
  try {
    const meta = auditMeta.fromRequest(req);
    const result = await service.login(
      { email: req.body.email, password: req.body.password },
      { userAgent: meta.userAgent, ip: meta.ip, req },
    );
    return apiResponse.success(res, result);
  } catch (err) { next(err); }
}

async function logout(req, res, next) {
  try {
    const meta = auditMeta.fromRequest(req);
    const refreshToken = req.body?.refresh_token;
    await service.logout({ refreshToken, actorUserId: req.auth?.userId }, meta);
    return apiResponse.success(res, { ok: true });
  } catch (err) { next(err); }
}

async function refresh(req, res, next) {
  try {
    const meta = auditMeta.fromRequest(req);
    const result = await service.refresh({ refreshToken: req.body.refresh_token }, meta);
    return apiResponse.success(res, result);
  } catch (err) { next(err); }
}

async function me(req, res, next) {
  try {
    const user = await service.me({ userId: req.auth.userId });
    return apiResponse.success(res, {
      user,
      google_enabled: google.isEnabled(),
    });
  } catch (err) { next(err); }
}

async function forgotPassword(req, res, next) {
  try {
    const meta = auditMeta.fromRequest(req);
    const result = await service.forgotPassword({ email: req.body.email }, meta);
    return apiResponse.success(res, result);
  } catch (err) { next(err); }
}

async function resetPassword(req, res, next) {
  try {
    const meta = auditMeta.fromRequest(req);
    const result = await service.resetPassword(
      { token: req.body.token, newPassword: req.body.new_password },
      meta,
    );
    return apiResponse.success(res, result);
  } catch (err) { next(err); }
}

async function googleStart(req, res, next) {
  try {
    if (!google.isEnabled()) {
      return apiResponse.success(res, { enabled: false, url: null });
    }
    const url = await service.googleAuthUrl();
    return apiResponse.success(res, { enabled: true, url });
  } catch (err) { next(err); }
}

async function googleCallback(req, res, next) {
  try {
    const meta = auditMeta.fromRequest(req);
    const result = await service.googleCallback({ code: req.query.code }, meta);
    // Redirect clients: we return JSON; CRM frontend does the redirect itself.
    return apiResponse.success(res, result);
  } catch (err) { next(err); }
}

module.exports = {
  login,
  logout,
  refresh,
  me,
  forgotPassword,
  resetPassword,
  googleStart,
  googleCallback,
};
