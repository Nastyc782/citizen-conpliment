const express = require('express');
const router = express.Router();
const { auth, checkRole } = require('../middleware/auth');
const {
    createTicket,
    getTickets,
    getTicketById,
    updateTicketStatus,
    addResponse
} = require('../controllers/ticketController');

// All routes require authentication
router.use(auth);

// Create a new ticket (all authenticated users)
router.post('/', createTicket);

// Get all tickets (filtered by role)
router.get('/', getTickets);

// Get specific ticket
router.get('/:id', getTicketById);

// Update ticket status (admin only)
router.patch('/:id/status', checkRole(['admin']), updateTicketStatus);

// Add response to ticket
router.post('/:id/responses', addResponse);

module.exports = router; 