'use strict';

const { body } = require('express-validator');

const submitRules = [
  body('institution_name').isString().trim().isLength({ min: 2, max: 180 }),
  body('contact_name').isString().trim().isLength({ min: 2, max: 160 }),
  body('contact_email').isEmail().normalizeEmail({ gmail_remove_dots: false, gmail_remove_subaddress: false }),
  body('contact_phone').optional({ nullable: true }).isString().isLength({ max: 40 }),
  body('address').optional({ nullable: true }).isString().isLength({ max: 255 }),
  body('notes').optional({ nullable: true }).isString().isLength({ max: 2000 }),
  body('plan_code').optional({ nullable: true }).isString().trim().isLength({ max: 32 }),
];

module.exports = { submitRules };
