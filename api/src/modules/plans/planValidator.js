'use strict';

const { body, param, query } = require('express-validator');

const STATUSES = ['active', 'inactive', 'archived'];
const FREQS = ['monthly', 'quarterly', 'yearly', 'custom'];

const listRules = [
  query('page').optional().isInt({ min: 1 }).toInt(),
  query('limit').optional().isInt({ min: 1, max: 100 }).toInt(),
  query('search').optional().isString().trim().isLength({ max: 120 }),
  query('status').optional().isString().trim(),
  query('billing_frequency').optional().isIn(FREQS),
  query('is_custom').optional().isIn(['true', 'false', '1', '0']),
  query('sort').optional().isIn(['id', 'code', 'name', 'price_amount', 'billing_frequency', 'status', 'created_at', 'updated_at']),
  query('order').optional().isIn(['asc', 'desc']),
];

const idRules = [param('id').isInt({ min: 1 }).toInt()];

const createRules = [
  body('code').isString().trim().isLength({ min: 2, max: 32 }).matches(/^[a-z0-9][a-z0-9_-]*$/i),
  body('name').isString().trim().isLength({ min: 2, max: 120 }),
  body('description').optional({ nullable: true }).isString().isLength({ max: 2000 }),
  body('billing_frequency').optional().isIn(FREQS),
  body('price_amount').optional().isFloat({ min: 0 }),
  body('currency_code').optional().isString().isLength({ min: 3, max: 10 }),
  body('status').optional().isIn(STATUSES),
  body('is_custom').optional().isBoolean().toBoolean(),
];

const updateRules = [
  param('id').isInt({ min: 1 }).toInt(),
  body('code').optional().isString().trim().isLength({ min: 2, max: 32 }).matches(/^[a-z0-9][a-z0-9_-]*$/i),
  body('name').optional().isString().trim().isLength({ min: 2, max: 120 }),
  body('description').optional({ nullable: true }).isString().isLength({ max: 2000 }),
  body('billing_frequency').optional().isIn(FREQS),
  body('price_amount').optional().isFloat({ min: 0 }),
  body('currency_code').optional().isString().isLength({ min: 3, max: 10 }),
  body('status').optional().isIn(STATUSES),
  body('is_custom').optional().isBoolean().toBoolean(),
];

const statusRules = [
  param('id').isInt({ min: 1 }).toInt(),
  body('status').isIn(STATUSES),
];

module.exports = { listRules, idRules, createRules, updateRules, statusRules, STATUSES, FREQS };
