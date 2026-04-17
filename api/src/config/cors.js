'use strict';

const cors = require('cors');
const env = require('./env');

const origins = env.cors.origin.split(',').map((o) => o.trim()).filter(Boolean);

const options = {
  origin(origin, cb) {
    if (!origin) return cb(null, true);
    if (origins.includes('*')) return cb(null, true);
    if (origins.includes(origin)) return cb(null, true);
    return cb(new Error(`CORS: origin ${origin} not allowed`));
  },
  credentials: true,
  methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization', 'X-Request-Id'],
};

module.exports = cors(options);
