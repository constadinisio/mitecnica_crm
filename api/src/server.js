'use strict';

const app = require('./app');
const env = require('./config/env');
const logger = require('./config/logger');
const { verifyConnection, closeConnection } = require('./config/db');
const webhookDispatcher = require('./jobs/webhookDispatcher');

async function start() {
  await verifyConnection();
  const server = app.listen(env.port, () => {
    logger.info('[api] listening on http://localhost:%d (%s)', env.port, env.nodeEnv);
  });

  webhookDispatcher.start();

  const shutdown = async (signal) => {
    logger.info('[api] received %s, shutting down', signal);
    webhookDispatcher.stop();
    server.close(async () => {
      try {
        await closeConnection();
      } catch (err) {
        logger.error('[api] error while closing DB: %s', err.message);
      }
      process.exit(0);
    });
    setTimeout(() => {
      logger.warn('[api] forced shutdown after 10s timeout');
      process.exit(1);
    }, 10000).unref();
  };

  process.on('SIGTERM', () => shutdown('SIGTERM'));
  process.on('SIGINT', () => shutdown('SIGINT'));
  process.on('unhandledRejection', (reason) => {
    logger.error('[api] unhandled rejection: %s', reason?.message || reason);
  });
}

start().catch((err) => {
  logger.error('[api] fatal startup error: %s\n%s', err.message, err.stack);
  process.exit(1);
});
