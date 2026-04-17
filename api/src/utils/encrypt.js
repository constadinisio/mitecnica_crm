'use strict';

const bcrypt = require('bcryptjs');
const crypto = require('crypto');
const env = require('../config/env');

async function hashPassword(plain) {
  if (!plain || typeof plain !== 'string') throw new Error('password required');
  return bcrypt.hash(plain, env.security.bcryptRounds);
}

async function verifyPassword(plain, hash) {
  if (!plain || !hash) return false;
  return bcrypt.compare(plain, hash);
}

function hashToken(token) {
  return crypto.createHash('sha256').update(String(token)).digest('hex');
}

function randomToken(bytes = 32) {
  return crypto.randomBytes(bytes).toString('hex');
}

module.exports = { hashPassword, verifyPassword, hashToken, randomToken };
