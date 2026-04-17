'use strict';

const service = require('./dashboardService');
const apiResponse = require('../../utils/apiResponse');

async function summary(_req, res, next) {
  try {
    const data = await service.summary();
    return apiResponse.success(res, data);
  } catch (err) { next(err); }
}

module.exports = { summary };
