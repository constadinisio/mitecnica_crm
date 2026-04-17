'use strict';

const { body, param } = require('express-validator');
const service = require('./planModuleService');
const apiResponse = require('../../utils/apiResponse');
const auditMeta = require('../../utils/auditMetadata');

async function matrix(_req, res, next) {
  try { return apiResponse.success(res, await service.getMatrix()); } catch (err) { next(err); }
}

async function listForPlan(req, res, next) {
  try { return apiResponse.success(res, await service.listForPlan(req.params.id)); } catch (err) { next(err); }
}

async function setForPlan(req, res, next) {
  try {
    const meta = auditMeta.fromRequest(req);
    const result = await service.setPlanModules(req.params.id, req.body.module_ids || [], { actor: req.auth, ...meta });
    return apiResponse.success(res, result);
  } catch (err) { next(err); }
}

const idRules = [param('id').isInt({ min: 1 }).toInt()];
const setRules = [
  param('id').isInt({ min: 1 }).toInt(),
  body('module_ids').isArray({ min: 0 }),
  body('module_ids.*').isInt({ min: 1 }).toInt(),
];

module.exports = { matrix, listForPlan, setForPlan, idRules, setRules };
