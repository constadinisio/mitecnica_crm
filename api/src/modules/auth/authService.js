'use strict';

const ApiError = require('../../utils/ApiError');
const env = require('../../config/env');
const logger = require('../../config/logger');
const { signAccessToken, signRefreshToken, verifyRefreshToken } = require('../../config/jwt');
const google = require('../../config/google');
const { verifyPassword, hashToken, randomToken } = require('../../utils/encrypt');
const repo = require('./authRepository');
const auditService = require('../audit/auditService');

function publicUser(user) {
  if (!user) return null;
  const { password_hash, ...safe } = user;
  return {
    id: safe.id,
    name: safe.name,
    email: safe.email,
    status: safe.status,
    avatar_url: safe.avatar_url,
    last_login_at: safe.last_login_at,
    role: {
      id: safe.role_id,
      key: safe.role_key || null,
      name: safe.role_name || null,
    },
  };
}

function parseTtlToMs(ttl) {
  if (typeof ttl === 'number') return ttl * 1000;
  const m = String(ttl).match(/^(\d+)([smhdw])$/);
  if (!m) return 15 * 60 * 1000;
  const n = Number(m[1]);
  const unit = m[2];
  const mult = { s: 1000, m: 60000, h: 3600000, d: 86400000, w: 604800000 }[unit];
  return n * mult;
}

async function issueTokens(user, { userAgent = null, ip = null } = {}) {
  const payload = { sub: user.id, role: user.role_key || null, email: user.email, name: user.name };
  const accessToken = signAccessToken(payload);
  const refreshPlain = signRefreshToken({ sub: user.id, jti: randomToken(8) });
  const tokenHash = hashToken(refreshPlain);
  const expiresAt = new Date(Date.now() + parseTtlToMs(env.jwt.refreshTtl));
  await repo.createRefreshToken({ userId: user.id, tokenHash, expiresAt, userAgent, ip });
  return { accessToken, refreshToken: refreshPlain, accessTtl: env.jwt.accessTtl, refreshTtl: env.jwt.refreshTtl };
}

async function login({ email, password }, { userAgent, ip, req }) {
  const user = await repo.findUserByEmail(email);
  if (!user || user.status !== 'active') {
    await auditService.record({
      action: 'auth.login.failed',
      entity: 'crm_users',
      entityId: user?.id || null,
      description: `Failed login for ${email}`,
      ip,
      userAgent,
    });
    throw ApiError.unauthorized('Invalid credentials');
  }
  if (!user.password_hash) {
    throw ApiError.unauthorized('Password login is not enabled for this account');
  }
  const ok = await verifyPassword(password, user.password_hash);
  if (!ok) {
    await auditService.record({
      action: 'auth.login.failed',
      entity: 'crm_users',
      entityId: user.id,
      description: `Failed login for ${email}`,
      ip,
      userAgent,
    });
    throw ApiError.unauthorized('Invalid credentials');
  }

  await repo.touchLastLogin(user.id);
  const tokens = await issueTokens(user, { userAgent, ip });

  await auditService.record({
    actorUserId: user.id,
    action: 'auth.login',
    entity: 'crm_users',
    entityId: user.id,
    description: `Login OK for ${user.email}`,
    ip,
    userAgent,
  });

  return { user: publicUser(user), ...tokens };
}

async function logout({ refreshToken, actorUserId }, { userAgent, ip }) {
  if (refreshToken) {
    const hash = hashToken(refreshToken);
    await repo.revokeRefreshToken(hash);
  }
  await auditService.record({
    actorUserId: actorUserId || null,
    action: 'auth.logout',
    entity: 'crm_users',
    entityId: actorUserId || null,
    description: 'Logout',
    ip,
    userAgent,
  });
  return { ok: true };
}

async function refresh({ refreshToken }, { userAgent, ip }) {
  if (!refreshToken) throw ApiError.unauthorized('Missing refresh token');
  let decoded;
  try {
    decoded = verifyRefreshToken(refreshToken);
  } catch (e) {
    throw ApiError.unauthorized('Invalid refresh token');
  }
  const hash = hashToken(refreshToken);
  const stored = await repo.findRefreshToken(hash);
  if (!stored) throw ApiError.unauthorized('Refresh token not recognized');
  if (stored.revoked_at) throw ApiError.unauthorized('Refresh token revoked');
  if (new Date(stored.expires_at) < new Date()) throw ApiError.unauthorized('Refresh token expired');

  const user = await repo.findUserById(decoded.sub);
  if (!user || user.status !== 'active') throw ApiError.unauthorized('User not active');

  // rotate
  await repo.revokeRefreshToken(hash);
  const tokens = await issueTokens(user, { userAgent, ip });
  return { user: publicUser(user), ...tokens };
}

async function me({ userId }) {
  const user = await repo.findUserById(userId);
  if (!user) throw ApiError.notFound('User not found');
  return publicUser(user);
}

async function forgotPassword({ email }, { userAgent, ip }) {
  const user = await repo.findUserByEmail(email);
  // Intentionally always return OK (don't leak existence).
  await auditService.record({
    actorUserId: user?.id || null,
    action: 'auth.forgot_password',
    entity: 'crm_users',
    entityId: user?.id || null,
    description: `Forgot password request for ${email}`,
    ip,
    userAgent,
  });
  // NOTE: Email dispatch is intentionally out of scope in phase 1.
  // The reset token would be emailed here.
  if (user) logger.info('[auth] forgot-password requested for %s', user.email);
  return { ok: true };
}

async function resetPassword({ token, newPassword }, { userAgent, ip }) {
  // Phase 1 stub: we don't persist reset tokens yet.
  // The endpoint is structurally ready; returning 501 keeps the flow explicit.
  await auditService.record({
    action: 'auth.reset_password.attempt',
    entity: 'crm_users',
    description: 'Reset password attempted (not implemented in phase 1)',
    ip,
    userAgent,
  });
  throw new ApiError(501, 'Password reset flow is not wired to email in phase 1', { code: 'NOT_IMPLEMENTED' });
}

async function googleAuthUrl() {
  if (!google.isEnabled()) return null;
  return google.getAuthUrl();
}

async function googleCallback({ code }, { userAgent, ip }) {
  if (!google.isEnabled()) throw new ApiError(503, 'Google OAuth not configured', { code: 'GOOGLE_DISABLED' });
  const profile = await google.getUserInfoFromCode(code);
  if (!profile.email || !profile.emailVerified) throw ApiError.unauthorized('Google email not verified');

  let user = await repo.findUserByGoogleId(profile.googleId);
  if (!user) {
    user = await repo.findUserByEmail(profile.email);
    if (user) {
      await repo.linkGoogleId(user.id, profile.googleId);
    } else {
      // Do not auto-provision CRM users from Google in phase 1.
      throw ApiError.forbidden('No CRM account linked to this Google identity');
    }
  }
  if (user.status !== 'active') throw ApiError.unauthorized('User not active');

  await repo.touchLastLogin(user.id);
  const tokens = await issueTokens(user, { userAgent, ip });
  await auditService.record({
    actorUserId: user.id,
    action: 'auth.login.google',
    entity: 'crm_users',
    entityId: user.id,
    description: `Google login for ${user.email}`,
    ip,
    userAgent,
  });
  return { user: publicUser(user), ...tokens };
}

module.exports = {
  login,
  logout,
  refresh,
  me,
  forgotPassword,
  resetPassword,
  googleAuthUrl,
  googleCallback,
  publicUser,
};
