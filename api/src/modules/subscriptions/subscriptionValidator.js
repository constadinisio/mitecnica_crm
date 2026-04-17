'use strict';

const { body, param, query } = require('express-validator');

const STATUSES = ['trial', 'active', 'suspended', 'expired', 'canceled'];
const RENEWAL = ['manual', 'automatic'];

const listRules = [
  query('page').optional().isInt({ min: 1 }).toInt(),
  query('limit').optional().isInt({ min: 1, max: 100 }).toInt(),
  query('search').optional().isString().trim().isLength({ max: 120 }),
  query('institution_id').optional().isInt({ min: 1 }).toInt(),
  query('plan_id').optional().isInt({ min: 1 }).toInt(),
  query('status').optional().isString().trim(),
  query('renewal_mode').optional().isIn(RENEWAL),
  query('sort').optional().isIn(['id', 'status', 'start_date', 'end_date', 'trial_ends_at', 'renewal_mode', 'created_at', 'updated_at']),
  query('order').optional().isIn(['asc', 'desc']),
];

const idRules = [param('id').isInt({ min: 1 }).toInt()];

const createRules = [
  body('institution_id').isInt({ min: 1 }).toInt(),
  body('plan_id').isInt({ min: 1 }).toInt(),
  body('status').optional().isIn(STATUSES),
  body('start_date').isISO8601(),
  body('end_date').optional({ nullable: true }).isISO8601(),
  body('trial_ends_at').optional({ nullable: true }).isISO8601(),
  body('renewal_mode').optional().isIn(RENEWAL),
  body('billing_notes').optional({ nullable: true }).isString().isLength({ max: 2000 }),
];

const updateRules = [
  param('id').isInt({ min: 1 }).toInt(),
  body('plan_id').optional().isInt({ min: 1 }).toInt(),
  body('status').optional().isIn(STATUSES),
  body('start_date').optional().isISO8601(),
  body('end_date').optional({ nullable: true }).isISO8601(),
  body('trial_ends_at').optional({ nullable: true }).isISO8601(),
  body('renewal_mode').optional().isIn(RENEWAL),
  body('billing_notes').optional({ nullable: true }).isString().isLength({ max: 2000 }),
];

const statusRules = [
  param('id').isInt({ min: 1 }).toInt(),
  body('status').isIn(STATUSES),
  body('reason').optional({ nullable: true }).isString().isLength({ max: 500 }),
];

module.exports = { listRules, idRules, createRules, updateRules, statusRules, STATUSES, RENEWAL };
