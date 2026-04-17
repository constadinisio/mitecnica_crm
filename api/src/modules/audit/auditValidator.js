'use strict';

const { query, param } = require('express-validator');

const listRules = [
  query('page').optional().isInt({ min: 1 }).toInt(),
  query('limit').optional().isInt({ min: 1, max: 100 }).toInt(),
  query('sort').optional().isIn(['created_at', 'action', 'entity']),
  query('order').optional().isIn(['asc', 'desc']),
  query('action').optional().isString().trim().isLength({ max: 80 }),
  query('entity').optional().isString().trim().isLength({ max: 80 }),
  query('entity_id').optional().isString().trim().isLength({ max: 64 }),
  query('actor_user_id').optional().isInt({ min: 1 }).toInt(),
  query('search').optional().isString().trim().isLength({ max: 120 }),
  query('from').optional().isISO8601().toDate(),
  query('to').optional().isISO8601().toDate(),
];

const byIdRules = [
  param('id').isInt({ min: 1 }).toInt(),
];

module.exports = { listRules, byIdRules };
