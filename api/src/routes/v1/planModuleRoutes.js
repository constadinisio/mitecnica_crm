'use strict';

const express = require('express');
const controller = require('../../modules/planModules/planModuleController');
const authMiddleware = require('../../middlewares/authMiddleware');
const authorizeRoles = require('../../middlewares/authorizeRoles');

const router = express.Router();
router.use(authMiddleware);

router.get('/matrix', authorizeRoles('support', 'commercial', 'finance', 'developer'), controller.matrix);

module.exports = router;
