const express = require('express');
const router = express.Router();
const { isAdmin } = require('../../middleware/auth');

// Import admin route handlers
const bulkActionsRoutes = require('./bulk-actions');
const analyticsRoutes = require('./analytics');

// Apply admin middleware to all routes
router.use(isAdmin);

// Bulk actions routes
router.use('/bulk-actions', bulkActionsRoutes);

// Analytics routes
router.use('/analytics', analyticsRoutes);

module.exports = router; 