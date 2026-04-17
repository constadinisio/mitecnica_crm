'use strict';

const morgan = require('morgan');
const env = require('../config/env');
const logger = require('../config/logger');

const format = env.isDev ? 'dev' : 'combined';

const stream = {
  write(line) {
    logger.info(line.trim());
  },
};

module.exports = morgan(format, { stream });
