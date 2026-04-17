'use strict';

const { body, param, query } = require('express-validator');

const STATUSES = ['active', 'inactive'];
const CATEGORIES = ['academic', 'communication', 'administration', 'technical', 'analytics', 'other'];

const listRules = [
  query('page').optional().isInt({ min: 1 }).toInt(),
  query('limit').optional().isInt({ min: 1, max: 200 }).toInt(),
  query('search').optional().isString().trim().isLength({ max: 120 }),
  query('status').optional().isString().trim(),
  query('category').optional().isIn(CATEGORIES),
  query('is_core').optional().isIn(['true', 'false', '1', '0']),
  query('sort').optional().isIn(['id', 'code', 'name', 'category', 'status', 'is_core', 'created_at', 'updated_at']),
  query('order').optional().isIn(['asc', 'desc']),
];

const idRules = [param('id').isInt({ min: 1 }).toInt()];

const createRules = [
  body('code').isString().trim().isLength({ min: 2, max: 48 }).matches(/^[a-z0-9][a-z0-9_-]*$/i),
  body('name').isString().trim().isLength({ min: 2, max: 120 }),
  body('description').optional({ nullable: true }).isString().isLength({ max: 2000 }),
  body('category').optional({ nullable: true }).isIn(CATEGORIES),
  body('status').optional().isIn(STATUSES),
  body('is_core').optional().isBoolean().toBoolean(),
];

const updateRules = [
  param('id').isInt({ min: 1 }).toInt(),
  body('code').optional().isString().trim().isLength({ min: 2, max: 48 }).matches(/^[a-z0-9][a-z0-9_-]*$/i),
  body('name').optional().isString().trim().isLength({ min: 2, max: 120 }),
  body('description').optional({ nullable: true }).isString().isLength({ max: 2000 }),
  body('category').optional({ nullable: true }).isIn(CATEGORIES),
  body('status').optional().isIn(STATUSES),
  body('is_core').optional().isBoolean().toBoolean(),
];

const statusRules = [
  param('id').isInt({ min: 1 }).toInt(),
  body('status').isIn(STATUSES),
];

module.exports = { listRules, idRules, createRules, updateRules, statusRules, STATUSES, CATEGORIES };
