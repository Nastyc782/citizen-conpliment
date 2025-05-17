const express = require('express');
const router = express.Router();
const { Ticket, AuditLog, Agency, sequelize } = require('../../models');
const { isAdmin } = require('../../middleware/auth');
const logger = require('../../utils/logger');

router.post('/', isAdmin, async (req, res) => {
  const { action, ticketIds, comment } = req.body;
  
  if (!action || !ticketIds || !Array.isArray(ticketIds) || ticketIds.length === 0) {
    return res.status(400).json({ error: 'Invalid input' });
  }

  const transaction = await sequelize.transaction();

  try {
    switch (action) {
      case 'update_status': {
        const { status } = req.body;
        if (!['pending', 'in_progress', 'under_review', 'resolved', 'closed'].includes(status)) {
          throw new Error('Invalid status');
        }

        await Ticket.update({
          status,
          updatedAt: new Date(),
          updatedBy: req.user.id,
          adminComment: comment || sequelize.literal('admin_comment')
        }, {
          where: {
            id: ticketIds
          },
          transaction
        });

        // Add audit logs
        await Promise.all(ticketIds.map(ticketId => 
          AuditLog.create({
            ticketId,
            userId: req.user.id,
            action: 'bulk_status_update',
            details: JSON.stringify({ newStatus: status, comment })
          }, { transaction })
        ));
        break;
      }

      case 'assign_priority': {
        const { priority } = req.body;
        if (!['low', 'medium', 'high'].includes(priority)) {
          throw new Error('Invalid priority');
        }

        await Ticket.update({
          priority,
          updatedAt: new Date(),
          updatedBy: req.user.id,
          adminComment: comment || sequelize.literal('admin_comment')
        }, {
          where: {
            id: ticketIds
          },
          transaction
        });

        // Add audit logs
        await Promise.all(ticketIds.map(ticketId => 
          AuditLog.create({
            ticketId,
            userId: req.user.id,
            action: 'bulk_priority_update',
            details: JSON.stringify({ newPriority: priority, comment })
          }, { transaction })
        ));
        break;
      }

      case 'assign_agency': {
        const { agencyId } = req.body;
        if (!agencyId) {
          throw new Error('Invalid agency ID');
        }

        // Verify agency exists
        const agency = await Agency.findByPk(agencyId);
        if (!agency) {
          throw new Error('Agency not found');
        }

        await Ticket.update({
          agencyId,
          updatedAt: new Date(),
          updatedBy: req.user.id,
          adminComment: comment || sequelize.literal('admin_comment')
        }, {
          where: {
            id: ticketIds
          },
          transaction
        });

        // Add audit logs
        await Promise.all(ticketIds.map(ticketId => 
          AuditLog.create({
            ticketId,
            userId: req.user.id,
            action: 'bulk_agency_update',
            details: JSON.stringify({ newAgencyId: agencyId, comment })
          }, { transaction })
        ));
        break;
      }

      default:
        throw new Error('Invalid action');
    }

    await transaction.commit();
    res.json({
      success: true,
      message: 'Bulk action completed successfully',
      affectedTickets: ticketIds.length
    });

  } catch (error) {
    await transaction.rollback();
    logger.error('Bulk action error:', error);
    res.status(400).json({ error: error.message });
  }
});

module.exports = router; 