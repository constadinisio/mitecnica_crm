'use strict';

const service = require('./paymentService');
const pagination = require('../../utils/pagination');
const apiResponse = require('../../utils/apiResponse');
const auditMeta = require('../../utils/auditMetadata');
const { writeCsv } = require('../../utils/csvExport');

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

async function create(req, res, next) {
  try {
    const meta = auditMeta.fromRequest(req);
    const row = await service.create(req.body, { actor: req.auth, ...meta });
    return apiResponse.success(res, row, { status: 201 });
  } catch (err) { next(err); }
}

async function update(req, res, next) {
  try {
    const meta = auditMeta.fromRequest(req);
    const row = await service.update(req.params.id, req.body, { actor: req.auth, ...meta });
    return apiResponse.success(res, row);
  } catch (err) { next(err); }
}

async function changeStatus(req, res, next) {
  try {
    const meta = auditMeta.fromRequest(req);
    const row = await service.changeStatus(req.params.id, req.body.status, { actor: req.auth, reason: req.body.reason, ...meta });
    return apiResponse.success(res, row);
  } catch (err) { next(err); }
}

async function summary(_req, res, next) {
  try { return apiResponse.success(res, await service.summary()); } catch (err) { next(err); }
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
      filename: `payments-${new Date().toISOString().slice(0, 10)}`,
      fields: [
        { key: 'id', header: 'id' },
        { key: 'institution_code', header: 'institution_code' },
        { key: 'institution_name', header: 'institution_name' },
        { key: 'subscription_id', header: 'subscription_id' },
        { key: 'plan_code', header: 'plan_code' },
        { key: 'plan_name', header: 'plan_name' },
        { key: 'amount', header: 'amount' },
        { key: 'currency_code', header: 'currency_code' },
        { key: 'status', header: 'status' },
        { key: 'payment_method', header: 'payment_method' },
        { key: 'reference_code', header: 'reference_code' },
        { key: 'payment_date', header: 'payment_date' },
        { key: 'period_start', header: 'period_start' },
        { key: 'period_end', header: 'period_end' },
        { key: 'notes', header: 'notes' },
        { key: 'created_at', header: 'created_at' },
      ],
      rows: result.rows,
    });
  } catch (err) { next(err); }
}

module.exports = { list, getById, create, update, changeStatus, summary, exportCsv };
