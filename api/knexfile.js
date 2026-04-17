'use strict';

require('dotenv').config();
const path = require('path');

const common = {
  client: 'pg',
  connection: {
    host: process.env.CRM_DB_HOST || '127.0.0.1',
    port: Number(process.env.CRM_DB_PORT || 5432),
    database: process.env.CRM_DB_NAME || 'mitecnica_crm',
    user: process.env.CRM_DB_USER || 'mitecnica',
    password: process.env.CRM_DB_PASSWORD || 'mitecnica',
  },
  pool: { min: 2, max: 10 },
  migrations: {
    tableName: 'knex_migrations',
    directory: path.join(__dirname, 'migrations'),
    stub: path.join(__dirname, 'migrations', '.stub.js'),
  },
  seeds: {
    directory: path.join(__dirname, 'seeds'),
  },
};

module.exports = {
  development: { ...common, debug: false },
  test: {
    ...common,
    connection: {
      ...common.connection,
      database: process.env.CRM_DB_NAME_TEST || `${common.connection.database}_test`,
    },
  },
  production: {
    ...common,
    pool: { min: 2, max: 20 },
    ssl: process.env.CRM_DB_SSL === 'true' ? { rejectUnauthorized: false } : false,
  },
};
