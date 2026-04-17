'use strict';

const util = require('util');
const env = require('./env');

const LEVELS = { debug: 10, info: 20, warn: 30, error: 40 };
const currentLevel = LEVELS[env.logging.level] || LEVELS.info;

function stamp() {
  return new Date().toISOString();
}

function format(level, args) {
  const msg = args.length === 1 && typeof args[0] === 'string' ? args[0] : util.format(...args);
  return `[${stamp()}] [${level.toUpperCase()}] ${msg}`;
}

const logger = {
  debug: (...args) => { if (LEVELS.debug >= currentLevel) process.stdout.write(format('debug', args) + '\n'); },
  info:  (...args) => { if (LEVELS.info  >= currentLevel) process.stdout.write(format('info',  args) + '\n'); },
  warn:  (...args) => { if (LEVELS.warn  >= currentLevel) process.stderr.write(format('warn',  args) + '\n'); },
  error: (...args) => { if (LEVELS.error >= currentLevel) process.stderr.write(format('error', args) + '\n'); },
};

module.exports = logger;
