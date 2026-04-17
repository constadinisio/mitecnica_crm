'use strict';

const service = require('./leadService');
const pagination = require('../../utils/pagination');
const apiResponse = require('../../utils/apiResponse');
const auditMeta = require('../../utils/auditMetadata');

async function list(req, res, next) {
  try {
    const { page, limit, offset } = pagination.parse(req.query);
    const result = await service.listPaginated({ query: req.query, page, limit, offset });
    return apiResponse.success(res, result.rows, { meta: pagination.buildMeta({ page, limit, total: result.total }) });
  } catch (err) { next(err); }
}

async function getById(req, res, next) {
  try { return apiResponse.success(res, await service.getById(req.params.id)); } catch (err) { next(err); }
}

async function changeStatus(req, res, next) {
  try {
    const meta = auditMeta.fromRequest(req);
    const row = await service.changeStatus(req.params.id, req.body.status, {
      actor: req.auth, reason: req.body.reason, ...meta,
    });
    return apiResponse.success(res, row);
  } catch (err) { next(err); }
}

async function assign(req, res, next) {
  try {
    const meta = auditMeta.fromRequest(req);
    const row = await service.assign(req.params.id, req.body.user_id ?? null, { actor: req.auth, ...meta });
    return apiResponse.success(res, row);
  } catch (err) { next(err); }
}

async function convert(req, res, next) {
  try {
    const meta = auditMeta.fromRequest(req);
    const result = await service.convert(req.params.id, req.body, { actor: req.auth, ...meta });
    return apiResponse.success(res, result, { status: 201 });
  } catch (err) { next(err); }
}

async function summary(_req, res, next) {
  try { return apiResponse.success(res, await service.summary()); } catch (err) { next(err); }
}

module.exports = { list, getById, changeStatus, assign, convert, summary };
