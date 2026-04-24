'use strict';

const apiResponse = require('../../utils/apiResponse');
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

module.exports = { listFeed };
