'use strict';

const { body, param, query } = require('express-validator');

const STATUSES = ['pending', 'approved', 'rejected', 'expired', 'canceled'];

const listRules = [
  query('page').optional().isInt({ min: 1 }).toInt(),
  query('limit').optional().isInt({ min: 1, max: 100 }).toInt(),
  query('search').optional().isString().trim().isLength({ max: 120 }),
  query('institution_id').optional().isInt({ min: 1 }).toInt(),
  query('subscription_id').optional().isInt({ min: 1 }).toInt(),
  query('status').optional().isString().trim(),
  query('payment_method').optional().isString().isLength({ max: 80 }),
  query('from').optional().isISO8601(),
  query('to').optional().isISO8601(),
  query('sort').optional().isIn(['id', 'amount', 'payment_date', 'status', 'created_at', 'updated_at']),
  query('order').optional().isIn(['asc', 'desc']),
];

const idRules = [param('id').isInt({ min: 1 }).toInt()];

const createRules = [
  body('institution_id').isInt({ min: 1 }).toInt(),
  body('subscription_id').optional({ nullable: true }).isInt({ min: 1 }).toInt(),
  body('amount').isFloat({ min: 0 }),
  body('currency_code').optional().isString().isLength({ min: 3, max: 10 }),
  body('payment_date').optional().isISO8601(),
  body('status').optional().isIn(STATUSES),
  body('payment_method').optional({ nullable: true }).isString().isLength({ max: 80 }),
  body('reference_code').optional({ nullable: true }).isString().isLength({ max: 120 }),
  body('notes').optional({ nullable: true }).isString().isLength({ max: 2000 }),
];

const updateRules = [
  param('id').isInt({ min: 1 }).toInt(),
  body('subscription_id').optional({ nullable: true }).isInt({ min: 1 }).toInt(),
  body('amount').optional().isFloat({ min: 0 }),
  body('currency_code').optional().isString().isLength({ min: 3, max: 10 }),
  body('payment_date').optional().isISO8601(),
  body('status').optional().isIn(STATUSES),
  body('payment_method').optional({ nullable: true }).isString().isLength({ max: 80 }),
  body('reference_code').optional({ nullable: true }).isString().isLength({ max: 120 }),
  body('notes').optional({ nullable: true }).isString().isLength({ max: 2000 }),
];

const statusRules = [
  param('id').isInt({ min: 1 }).toInt(),
  body('status').isIn(STATUSES),
  body('reason').optional({ nullable: true }).isString().isLength({ max: 500 }),
];

module.exports = { listRules, idRules, createRules, updateRules, statusRules, STATUSES };
