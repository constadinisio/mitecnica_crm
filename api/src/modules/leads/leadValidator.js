'use strict';

const { body, param, query } = require('express-validator');

const STATUSES = ['new', 'contacted', 'in_negotiation', 'converted', 'lost'];

const listRules = [
  query('page').optional().isInt({ min: 1 }).toInt(),
  query('limit').optional().isInt({ min: 1, max: 100 }).toInt(),
  query('search').optional().isString().trim().isLength({ max: 120 }),
  query('status').optional().isString().trim(),
  query('assigned_to').optional().isString().trim(), // int id OR 'unassigned'
  query('sort').optional().isIn(['id', 'status', 'institution_name', 'contact_email', 'created_at', 'updated_at']),
  query('order').optional().isIn(['asc', 'desc']),
];

const idRules = [param('id').isInt({ min: 1 }).toInt()];

const statusRules = [
  param('id').isInt({ min: 1 }).toInt(),
  body('status').isIn(['contacted', 'in_negotiation', 'lost']), // converted goes through /convert
  body('reason').optional({ nullable: true }).isString().isLength({ max: 500 }),
];

const assignRules = [
  param('id').isInt({ min: 1 }).toInt(),
  body('user_id').optional({ nullable: true }).isInt({ min: 1 }).toInt(),
];

const convertRules = [
  param('id').isInt({ min: 1 }).toInt(),
  body('institution_name').optional().isString().trim().isLength({ min: 2, max: 180 }),
  body('contact_email').optional().isEmail().normalizeEmail(),
  body('contact_phone').optional({ nullable: true }).isString().isLength({ max: 40 }),
  body('address').optional({ nullable: true }).isString().isLength({ max: 255 }),
  body('responsible_name').optional({ nullable: true }).isString().isLength({ max: 160 }),
  body('responsible_email').optional({ nullable: true }).isEmail().normalizeEmail(),
  body('notes_internal').optional({ nullable: true }).isString().isLength({ max: 2000 }),
  body('subdomain').optional().isString().isLength({ max: 120 }),
  body('slug').optional().isString().isLength({ max: 180 }),
  body('institution_status').optional().isIn(['trial', 'active', 'maintenance']),
  body('plan_id').optional({ nullable: true }).isInt({ min: 1 }).toInt(),
  body('create_subscription').optional().isBoolean().toBoolean(),
  body('subscription_status').optional().isIn(['trial', 'active']),
  body('start_date').optional().isISO8601(),
  body('end_date').optional({ nullable: true }).isISO8601(),
  body('trial_ends_at').optional({ nullable: true }).isISO8601(),
  body('renewal_mode').optional().isIn(['manual', 'automatic']),
  body('billing_notes').optional({ nullable: true }).isString().isLength({ max: 2000 }),
];

module.exports = { listRules, idRules, statusRules, assignRules, convertRules, STATUSES };
