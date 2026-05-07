'use strict';

const service = require('./institutionService');
const pagination = require('../../utils/pagination');
const apiResponse = require('../../utils/apiResponse');
const auditMeta = require('../../utils/auditMetadata');
const auditRepo = require('../audit/auditRepository');
const { writeCsv } = require('../../utils/csvExport');

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

async function exportCsv(req, res, next) {
  try {
    const result = await service.listPaginated({
      query: req.query,
      page: 1,
      limit: 5000,
      offset: 0,
    });
    await writeCsv(res, {
      filename: `institutions-${new Date().toISOString().slice(0, 10)}`,
      fields: [
        { key: 'id', header: 'id' },
        { key: 'public_code', header: 'public_code' },
        { key: 'name', header: 'name' },
        { key: 'slug', header: 'slug' },
        { key: 'subdomain', header: 'subdomain' },
        { key: 'status', header: 'status' },
        { key: 'technical_status', header: 'technical_status' },
        { key: 'contact_email', header: 'contact_email' },
        { key: 'contact_phone', header: 'contact_phone' },
        { key: 'responsible_name', header: 'responsible_name' },
        { key: 'responsible_last_name', header: 'responsible_last_name' },
        { key: 'current_plan_name', header: 'current_plan' },
        { key: 'expiration_date', header: 'expiration_date' },
        { key: 'created_at', header: 'created_at' },
        { key: 'updated_at', header: 'updated_at' },
      ],
      rows: result.rows,
    });
  } catch (err) { next(err); }
}

module.exports = { list, getById, create, update, changeStatus, exportCsv };
