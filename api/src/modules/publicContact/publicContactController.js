'use strict';

const service = require('./publicContactService');
const apiResponse = require('../../utils/apiResponse');
const auditMeta = require('../../utils/auditMetadata');

async function plans(_req, res, next) {
  try { return apiResponse.success(res, await service.listActivePlans()); } catch (err) { next(err); }
}

async function submit(req, res, next) {
  try {
    const meta = auditMeta.fromRequest(req);
    const result = await service.submit(req.body, meta);
    return apiResponse.success(res, result, { status: 201 });
  } catch (err) { next(err); }
}

module.exports = { plans, submit };
