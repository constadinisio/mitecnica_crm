'use strict';

const service = require('./auditService');
const pagination = require('../../utils/pagination');
const apiResponse = require('../../utils/apiResponse');
const ApiError = require('../../utils/ApiError');
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
    if (!row) throw ApiError.notFound('Audit log not found');
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
      filename: `audit-${new Date().toISOString().slice(0, 10)}`,
      fields: [
        { key: 'id', header: 'id' },
        { key: 'created_at', header: 'created_at' },
        { key: 'action', header: 'action' },
        { key: 'entity', header: 'entity' },
        { key: 'entity_id', header: 'entity_id' },
        { key: 'description', header: 'description' },
        { key: 'actor_name', header: 'actor_name' },
        { key: 'actor_email', header: 'actor_email' },
        { key: 'ip', header: 'ip' },
        { key: 'user_agent', header: 'user_agent' },
      ],
      rows: result.rows,
    });
  } catch (err) { next(err); }
}

module.exports = { list, getById, exportCsv };
