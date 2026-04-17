'use strict';

const service = require('./auditService');
const pagination = require('../../utils/pagination');
const apiResponse = require('../../utils/apiResponse');
const ApiError = require('../../utils/ApiError');

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

module.exports = { list, getById };
