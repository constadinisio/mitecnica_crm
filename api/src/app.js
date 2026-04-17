'use strict';

const express = require('express');
const helmet = require('helmet');
const cors = require('./config/cors');
const env = require('./config/env');
const requestLogger = require('./middlewares/requestLogger');
const { globalLimiter } = require('./middlewares/rateLimiter');
const errorHandler = require('./middlewares/errorHandler');
const notFoundHandler = require('./middlewares/notFoundHandler');
const apiResponse = require('./utils/apiResponse');
const routes = require('./routes');

const app = express();

app.set('trust proxy', 1);
app.use(helmet());
app.use(cors);
app.use(express.json({ limit: '1mb' }));
app.use(express.urlencoded({ extended: true, limit: '1mb' }));
app.use(requestLogger);
app.use(globalLimiter);

app.get('/health', (_req, res) => apiResponse.success(res, {
  status: 'ok',
  name: 'mi-tecnica-crm-api',
  env: env.nodeEnv,
  time: new Date().toISOString(),
}));

app.use('/api', routes);

app.use(notFoundHandler);
app.use(errorHandler);

module.exports = app;
