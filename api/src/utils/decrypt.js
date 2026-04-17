'use strict';

// Thin wrapper for symmetric decryption helpers. Kept for API symmetry with encrypt.js;
// currently unused outside of token hash comparisons which live in encrypt.js.

const { hashToken } = require('./encrypt');

function tokenMatches(plainToken, storedHash) {
  if (!plainToken || !storedHash) return false;
  return hashToken(plainToken) === storedHash;
}

module.exports = { tokenMatches };
