'use strict';

const service = require('./institutionService');
const pagination = require('../../utils/pagination');
const apiResponse = require('../../utils/apiResponse');
const auditMeta = require('../../utils/auditMetadata');
const auditRepo = require('../audit/auditRepository');

async function list(req, res, next) {
  try {
    const { page, limit, offset } = pagination.parse(req.query);
    const result = await service.listPaginated({ query: req.query, page, limit, offset });
    return apiResponse.success(res, result.rows, {
      meta: pagination.buildMeta({ page, limit, total: result.total }),
    });
  } catch (err) { next(err); }
}

async function getById(req, res, next) {
  try {
    const row = await service.getById(req.params.id);
    const recentAudit = await auditRepo.list({
      filters: { entity: 'institutions', entityId: row.id },
      limit: 20,
      offset: 0,
    });
    return apiResponse.success(res, {
      institution: row,
      audit: recentAudit.rows,
    });
  } catch (err) { next(err); }
}

async function create(req, res, next) {
  try {
    const meta = auditMeta.fromRequest(req);
    const row = await service.create(req.body, { actor: req.auth, ip: meta.ip, userAgent: meta.userAgent });
    return apiResponse.success(res, row, { status: 201 });
  } catch (err) { next(err); }
}

async function update(req, res, next) {
  try {
    const meta = auditMeta.fromRequest(req);
    const row = await service.update(req.params.id, req.body, { actor: req.auth, ip: meta.ip, userAgent: meta.userAgent });
    return apiResponse.success(res, row);
  } catch (err) { next(err); }
}

async function changeStatus(req, res, next) {
  try {
    const meta = auditMeta.fromRequest(req);
    const row = await service.changeStatus(req.params.id, req.body.status, {
      actor: req.auth, ip: meta.ip, userAgent: meta.userAgent, reason: req.body.reason,
    });
    return apiResponse.success(res, row);
  } catch (err) { next(err); }
}

module.exports = { list, getById, create, update, changeStatus };
