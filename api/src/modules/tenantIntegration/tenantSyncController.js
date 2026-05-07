'use strict';

const apiResponse = require('../../utils/apiResponse');
const ApiError = require('../../utils/ApiError');
const service = require('./tenantSyncService');

async function listFeed(req, res, next) {
  try {
    const { limit, cursor, since } = req.query;
    const result = await service.listFeed({ limit, cursor, since });
    return apiResponse.success(res, result.items, {
      meta: {
        count: result.count,
        next_cursor: result.next_cursor,
        has_more: result.next_cursor !== null,
      },
    });
  } catch (err) {
    return next(err);
  }
}

async function recordActivity(req, res, next) {
  try {
    const { subdomain, at } = req.body || {};
    const result = await service.recordActivity(subdomain, at);
    return apiResponse.success(res, result);
  } catch (err) {
    if (err.status === 400) return next(ApiError.badRequest(err.message));
    if (err.status === 404) return next(ApiError.notFound(err.message));
    return next(err);
  }
}

module.exports = { listFeed, recordActivity };
