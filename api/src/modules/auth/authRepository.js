'use strict';

const { db } = require('../../config/db');

async function findUserByEmail(email) {
  return db('crm_users')
    .leftJoin('crm_roles', 'crm_users.role_id', 'crm_roles.id')
    .select(
      'crm_users.*',
      'crm_roles.key as role_key',
      'crm_roles.name as role_name',
    )
    .where({ 'crm_users.email': String(email).toLowerCase() })
    .first();
}

async function findUserById(id) {
  return db('crm_users')
    .leftJoin('crm_roles', 'crm_users.role_id', 'crm_roles.id')
    .select(
      'crm_users.*',
      'crm_roles.key as role_key',
      'crm_roles.name as role_name',
    )
    .where({ 'crm_users.id': id })
    .first();
}

async function findUserByGoogleId(googleId) {
  return db('crm_users')
    .leftJoin('crm_roles', 'crm_users.role_id', 'crm_roles.id')
    .select(
      'crm_users.*',
      'crm_roles.key as role_key',
      'crm_roles.name as role_name',
    )
    .where({ 'crm_users.google_id': googleId })
    .first();
}

async function linkGoogleId(userId, googleId) {
  await db('crm_users').where({ id: userId }).update({ google_id: googleId, updated_at: db.fn.now() });
}

async function touchLastLogin(userId) {
  await db('crm_users').where({ id: userId }).update({ last_login_at: db.fn.now(), updated_at: db.fn.now() });
}

async function createRefreshToken({ userId, tokenHash, expiresAt, userAgent, ip }) {
  const [row] = await db('crm_refresh_tokens').insert({
    user_id: userId,
    token_hash: tokenHash,
    expires_at: expiresAt,
    user_agent: userAgent,
    ip,
  }).returning('*');
  return row;
}

async function findRefreshToken(tokenHash) {
  return db('crm_refresh_tokens').where({ token_hash: tokenHash }).first();
}

async function revokeRefreshToken(tokenHash) {
  await db('crm_refresh_tokens').where({ token_hash: tokenHash }).update({ revoked_at: db.fn.now() });
}

async function revokeAllUserTokens(userId) {
  await db('crm_refresh_tokens').where({ user_id: userId }).whereNull('revoked_at').update({ revoked_at: db.fn.now() });
}

async function findRoleByKey(key) {
  return db('crm_roles').where({ key }).first();
}

module.exports = {
  findUserByEmail,
  findUserById,
  findUserByGoogleId,
  linkGoogleId,
  touchLastLogin,
  createRefreshToken,
  findRefreshToken,
  revokeRefreshToken,
  revokeAllUserTokens,
  findRoleByKey,
};
