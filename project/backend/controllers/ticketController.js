const Ticket = require('../models/Ticket');
const Agency = require('../models/Agency');
const Response = require('../models/Response');
const User = require('../models/User');

// Create a new ticket
const createTicket = async (req, res) => {
    try {
        const { subject, message, category, agencyId, priority } = req.body;
        const ticket = await Ticket.create({
            subject,
            message,
            category,
            agencyId,
            priority: priority || 'medium',
            userId: req.user.id
        });

        res.status(201).json(ticket);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
};

// Get all tickets (with filtering for admin/agency)
const getTickets = async (req, res) => {
    try {
        const where = {};
        if (req.user.role === 'citizen') {
            where.userId = req.user.id;
        }
        if (req.query.status) {
            where.status = req.query.status;
        }
        if (req.query.category) {
            where.category = req.query.category;
        }

        const tickets = await Ticket.findAll({
            where,
            include: [
                { model: User, as: 'citizen', attributes: ['id', 'name', 'email'] },
                { model: Agency, attributes: ['id', 'name', 'category'] }
            ],
            order: [['createdAt', 'DESC']]
        });

        res.json(tickets);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
};

// Get ticket by ID
const getTicketById = async (req, res) => {
    try {
        const ticket = await Ticket.findByPk(req.params.id, {
            include: [
                { model: User, as: 'citizen', attributes: ['id', 'name', 'email'] },
                { model: Agency, attributes: ['id', 'name', 'category'] },
                {
                    model: Response,
                    include: [
                        { model: User, as: 'responder', attributes: ['id', 'name', 'role'] }
                    ]
                }
            ]
        });

        if (!ticket) {
            return res.status(404).json({ error: 'Ticket not found' });
        }

        // Check if user has access to this ticket
        if (req.user.role === 'citizen' && ticket.userId !== req.user.id) {
            return res.status(403).json({ error: 'Access denied' });
        }

        res.json(ticket);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
};

// Update ticket status
const updateTicketStatus = async (req, res) => {
    try {
        const { status } = req.body;
        const ticket = await Ticket.findByPk(req.params.id);

        if (!ticket) {
            return res.status(404).json({ error: 'Ticket not found' });
        }

        // Only admin can update status
        if (req.user.role !== 'admin') {
            return res.status(403).json({ error: 'Access denied' });
        }

        ticket.status = status;
        await ticket.save();

        res.json(ticket);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
};

// Add response to ticket
const addResponse = async (req, res) => {
    try {
        const { message, isInternal } = req.body;
        const ticket = await Ticket.findByPk(req.params.id);

        if (!ticket) {
            return res.status(404).json({ error: 'Ticket not found' });
        }

        // Check if user can respond to this ticket
        if (req.user.role === 'citizen' && ticket.userId !== req.user.id) {
            return res.status(403).json({ error: 'Access denied' });
        }

        const response = await Response.create({
            message,
            isInternal: isInternal && req.user.role === 'admin',
            ticketId: ticket.id,
            userId: req.user.id
        });

        res.status(201).json(response);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
};

module.exports = {
    createTicket,
    getTickets,
    getTicketById,
    updateTicketStatus,
    addResponse
}; 