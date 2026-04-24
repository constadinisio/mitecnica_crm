'use strict';

const emitter = require('../modules/webhookEmitter/webhookEmitterService');
const env = require('../config/env');
const logger = require('../config/logger');

let interval = null;
let running = false;

async function tick() {
  if (running) return; // overlap guard: si el tick anterior aún está corriendo, skip
  running = true;
  try {
    const stats = await emitter.deliverPending(20);
    if (stats.processed > 0) {
      logger.info(
        '[webhookDispatcher] tick processed=%d sent=%d retried=%d dead=%d',
        stats.processed,
        stats.sent,
        stats.retried,
        stats.dead
      );
    }
  } catch (err) {
    logger.error('[webhookDispatcher] tick failed: %s', err.message);
  } finally {
    running = false;
  }
}

function start() {
  if (!env.integration.webhookDispatcherEnabled) {
    logger.info('[webhookDispatcher] disabled via WEBHOOK_DISPATCHER_ENABLED=false');
    return;
  }
  if (interval) return;
  const ms = env.integration.webhookDispatcherIntervalMs;
  interval = setInterval(tick, ms);
  interval.unref?.();
  logger.info('[webhookDispatcher] started (every %dms)', ms);
  // Primera ejecución inmediata — para no esperar el primer tick tras boot.
  setImmediate(tick);
}

function stop() {
  if (interval) {
    clearInterval(interval);
    interval = null;
    logger.info('[webhookDispatcher] stopped');
  }
}

module.exports = { start, stop, tick };
