const express = require('express');
const router = express.Router();
const { Ticket, User, Agency, Category, Response, Attachment } = require('../models');
const { authenticate, authorize } = require('../middleware/auth');
const multer = require('multer');
const path = require('path');

// Configure multer for file uploads
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, 'uploads/tickets');
  },
  filename: (req, file, cb) => {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
    cb(null, 'ticket-' + uniqueSuffix + path.extname(file.originalname));
  }
});

const upload = multer({
  storage,
  limits: { fileSize: 5 * 1024 * 1024 }, // 5MB limit
  fileFilter: (req, file, cb) => {
    const allowedTypes = /jpeg|jpg|png|pdf|doc|docx/;
    const extname = allowedTypes.test(path.extname(file.originalname).toLowerCase());
    const mimetype = allowedTypes.test(file.mimetype);
    if (extname && mimetype) {
      return cb(null, true);
    }
    cb(new Error('Invalid file type'));
  }
});

// Get all tickets (admin only)
router.get('/', authenticate, authorize('admin'), async (req, res) => {
  try {
    const tickets = await Ticket.findAll({
      include: [
        { model: User, as: 'user', attributes: ['id', 'name', 'email'] },
        { model: Agency, as: 'agency' },
        { model: Category, as: 'category' }
      ],
      order: [['createdAt', 'DESC']]
    });
    res.json(tickets);
  } catch (error) {
    res.status(500).json({ error: 'Error fetching tickets' });
  }
});

// Get user's tickets
router.get('/my-tickets', authenticate, async (req, res) => {
  try {
    const tickets = await Ticket.findAll({
      where: { userId: req.user.id },
      include: [
        { model: Agency, as: 'agency' },
        { model: Category, as: 'category' }
      ],
      order: [['createdAt', 'DESC']]
    });
    res.json(tickets);
  } catch (error) {
    res.status(500).json({ error: 'Error fetching tickets' });
  }
});

// Get single ticket
router.get('/:id', authenticate, async (req, res) => {
  try {
    const ticket = await Ticket.findOne({
      where: { 
        id: req.params.id,
        userId: req.user.role === 'admin' ? undefined : req.user.id
      },
      include: [
        { model: User, as: 'user', attributes: ['id', 'name', 'email'] },
        { model: Agency, as: 'agency' },
        { model: Category, as: 'category' },
        { 
          model: Response, 
          as: 'responses',
          include: [{ model: User, as: 'user', attributes: ['id', 'name', 'role'] }]
        },
        { model: Attachment, as: 'attachments' }
      ]
    });

    if (!ticket) {
      return res.status(404).json({ error: 'Ticket not found' });
    }

    res.json(ticket);
  } catch (error) {
    res.status(500).json({ error: 'Error fetching ticket' });
  }
});

// Create ticket
router.post('/', authenticate, upload.single('attachment'), async (req, res) => {
  try {
    const { title, description, agencyId, categoryId, priority = 'medium' } = req.body;

    const ticket = await Ticket.create({
      title,
      description,
      userId: req.user.id,
      agencyId,
      categoryId,
      priority,
      status: 'pending'
    });

    // Handle attachment if present
    if (req.file) {
      await Attachment.create({
        ticketId: ticket.id,
        fileName: req.file.originalname,
        filePath: req.file.path,
        fileType: req.file.mimetype
      });
    }

    res.status(201).json(ticket);
  } catch (error) {
    res.status(500).json({ error: 'Error creating ticket' });
  }
});

// Update ticket status (admin only)
router.patch('/:id/status', authenticate, authorize('admin'), async (req, res) => {
  try {
    const { status, adminComment } = req.body;
    const ticket = await Ticket.findByPk(req.params.id);

    if (!ticket) {
      return res.status(404).json({ error: 'Ticket not found' });
    }

    await ticket.update({
      status,
      adminComment,
      updatedBy: req.user.id,
      adminActionAt: new Date()
    });

    // Add response for status change
    await Response.create({
      ticketId: ticket.id,
      userId: req.user.id,
      content: `Status updated to ${status}${adminComment ? ': ' + adminComment : ''}`,
      isInternal: true
    });

    res.json(ticket);
  } catch (error) {
    res.status(500).json({ error: 'Error updating ticket status' });
  }
});

// Add response to ticket
router.post('/:id/responses', authenticate, async (req, res) => {
  try {
    const ticket = await Ticket.findByPk(req.params.id);
    if (!ticket) {
      return res.status(404).json({ error: 'Ticket not found' });
    }

    // Check if user has permission to respond
    if (req.user.role !== 'admin' && ticket.userId !== req.user.id) {
      return res.status(403).json({ error: 'Not authorized' });
    }

    const response = await Response.create({
      ticketId: ticket.id,
      userId: req.user.id,
      content: req.body.content,
      isInternal: req.user.role === 'admin' && req.body.isInternal
    });

    // Include user info in response
    const responseWithUser = await Response.findByPk(response.id, {
      include: [{ model: User, as: 'user', attributes: ['id', 'name', 'role'] }]
    });

    res.status(201).json(responseWithUser);
  } catch (error) {
    res.status(500).json({ error: 'Error adding response' });
  }
});

module.exports = router; 