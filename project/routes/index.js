const express = require('express');
const router = express.Router();

// Import route modules
const authRoutes = require('./auth');
const ticketRoutes = require('./tickets');
const adminRoutes = require('./admin');
const agencyRoutes = require('./agencies');
const categoryRoutes = require('./categories');
const userRoutes = require('./users');

// Use route modules
router.use('/auth', authRoutes);
router.use('/tickets', ticketRoutes);
router.use('/admin', adminRoutes);
router.use('/agencies', agencyRoutes);
router.use('/categories', categoryRoutes);
router.use('/users', userRoutes);

module.exports = router; 