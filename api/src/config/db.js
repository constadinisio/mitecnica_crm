'use strict';

const knex = require('knex');
const knexConfig = require('../../knexfile');
const env = require('./env');
const logger = require('./logger');

const config = knexConfig[env.nodeEnv] || knexConfig.development;
const db = knex(config);

async function verifyConnection() {
  try {
    await db.raw('select 1+1 as result');
    logger.info('[db] PostgreSQL connection OK');
  } catch (err) {
    logger.error('[db] PostgreSQL connection FAILED: %s', err.message);
    throw err;
  }
}

async function closeConnection() {
  await db.destroy();
  logger.info('[db] PostgreSQL connection closed');
}

module.exports = { db, verifyConnection, closeConnection };
