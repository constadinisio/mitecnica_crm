'use strict';

const service = require('./dashboardService');
const apiResponse = require('../../utils/apiResponse');

async function summary(_req, res, next) {
  try {
    const data = await service.summary();
    return apiResponse.success(res, data);
  } catch (err) { next(err); }
}

async function operationalSummary(req, res, next) {
  try {
    const windowHours = Math.max(1, Math.min(168, Number(req.query.window_hours) || 24));
    const recentDays = Math.max(1, Math.min(60, Number(req.query.recent_days) || 7));
    const data = await service.operationalSummary({ windowHours, recentDays });
    return apiResponse.success(res, data);
  } catch (err) { next(err); }
}

module.exports = { summary, operationalSummary };
