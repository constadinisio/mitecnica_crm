'use strict';

const { body, param, query } = require('express-validator');

const STATUSES = ['trial', 'active', 'maintenance', 'suspended', 'expired', 'inactive'];
const TECH_STATUSES = ['pending', 'optimal', 'updating', 'offline'];

const listRules = [
  query('page').optional().isInt({ min: 1 }).toInt(),
  query('limit').optional().isInt({ min: 1, max: 100 }).toInt(),
  query('search').optional().isString().trim().isLength({ max: 120 }),
  query('status').optional().isString().trim(),
  query('technical_status').optional().isString().trim(),
  query('plan').optional().isString().trim().isLength({ max: 120 }),
  query('sort').optional().isIn(['id', 'name', 'status', 'technical_status', 'expiration_date', 'created_at', 'updated_at', 'last_activity_at']),
  query('order').optional().isIn(['asc', 'desc']),
  query('expiration_from').optional().isISO8601(),
  query('expiration_to').optional().isISO8601(),
];

const idRules = [
  param('id').isInt({ min: 1 }).toInt(),
];

const createRules = [
  body('name').isString().trim().isLength({ min: 2, max: 180 }),
  body('contact_email').isEmail().normalizeEmail({ gmail_remove_dots: false, gmail_remove_subaddress: false }),
  body('contact_phone').optional({ nullable: true }).isString().isLength({ max: 40 }),
  body('address').optional({ nullable: true }).isString().isLength({ max: 255 }),
  body('responsible_name').optional({ nullable: true }).isString().isLength({ max: 160 }),
  body('responsible_last_name').optional({ nullable: true }).isString().isLength({ max: 160 }),
  body('responsible_email').optional({ nullable: true }).isEmail().normalizeEmail({ gmail_remove_dots: false, gmail_remove_subaddress: false }),
  body('notes_internal').optional({ nullable: true }).isString().isLength({ max: 2000 }),
  body('current_plan_name').optional({ nullable: true }).isString().isLength({ max: 120 }),
  body('expiration_date').optional({ nullable: true }).isISO8601(),
  body('slug').optional({ nullable: true }).isString().isLength({ max: 180 }),
  body('subdomain').optional({ nullable: true }).isString().isLength({ max: 120 }),
  body('status').optional().isIn(STATUSES),
  body('technical_status').optional().isIn(TECH_STATUSES),
];

const updateRules = [
  param('id').isInt({ min: 1 }).toInt(),
  body('name').optional().isString().trim().isLength({ min: 2, max: 180 }),
  body('contact_email').optional().isEmail().normalizeEmail({ gmail_remove_dots: false, gmail_remove_subaddress: false }),
  body('contact_phone').optional({ nullable: true }).isString().isLength({ max: 40 }),
  body('address').optional({ nullable: true }).isString().isLength({ max: 255 }),
  body('responsible_name').optional({ nullable: true }).isString().isLength({ max: 160 }),
  body('responsible_last_name').optional({ nullable: true }).isString().isLength({ max: 160 }),
  body('responsible_email').optional({ nullable: true }).isEmail().normalizeEmail({ gmail_remove_dots: false, gmail_remove_subaddress: false }),
  body('notes_internal').optional({ nullable: true }).isString().isLength({ max: 2000 }),
  body('current_plan_name').optional({ nullable: true }).isString().isLength({ max: 120 }),
  body('expiration_date').optional({ nullable: true }).isISO8601(),
  body('slug').optional().isString().isLength({ max: 180 }),
  body('subdomain').optional().isString().isLength({ max: 120 }),
  body('status').optional().isIn(STATUSES),
  body('technical_status').optional().isIn(TECH_STATUSES),
];

const statusRules = [
  param('id').isInt({ min: 1 }).toInt(),
  body('status').isIn(STATUSES),
  body('reason').optional({ nullable: true }).isString().isLength({ max: 500 }),
];

module.exports = { listRules, idRules, createRules, updateRules, statusRules, STATUSES, TECH_STATUSES };
