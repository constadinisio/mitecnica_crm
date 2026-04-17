'use strict';

const { param, body } = require('express-validator');
const service = require('./institutionModuleService');
const apiResponse = require('../../utils/apiResponse');
const auditMeta = require('../../utils/auditMetadata');

async function effective(req, res, next) {
  try {
    const data = await service.getEffectiveModules(Number(req.params.id));
    return apiResponse.success(res, data);
  } catch (err) { next(err); }
}

async function putOverrides(req, res, next) {
  try {
    const meta = auditMeta.fromRequest(req);
    const overrides = Array.isArray(req.body.overrides) ? req.body.overrides : [];
    const data = await service.replaceOverrides(
      Number(req.params.id),
      overrides,
      { actor: req.auth, ip: meta.ip, userAgent: meta.userAgent },
    );
    return apiResponse.success(res, data);
  } catch (err) { next(err); }
}

async function licenseSummary(req, res, next) {
  try {
    const data = await service.getLicenseSummary(Number(req.params.id));
    return apiResponse.success(res, data);
  } catch (err) { next(err); }
}

const idRules = [param('id').isInt({ min: 1 }).toInt()];

const overridesRules = [
  param('id').isInt({ min: 1 }).toInt(),
  body('overrides').isArray(),
  body('overrides.*.module_id').isInt({ min: 1 }).toInt(),
  body('overrides.*.override_mode').isIn(service.ALLOWED_OVERRIDE_MODES),
  body('overrides.*.notes').optional({ nullable: true }).isString().isLength({ max: 500 }),
];

module.exports = {
  effective,
  putOverrides,
  licenseSummary,
  idRules,
  overridesRules,
};
