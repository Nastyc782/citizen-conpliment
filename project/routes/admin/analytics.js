const express = require('express');
const router = express.Router();
const { Ticket, Agency, sequelize } = require('../../models');
const { isAdmin } = require('../../middleware/auth');
const { Op } = require('sequelize');
const logger = require('../../utils/logger');

router.get('/', isAdmin, async (req, res) => {
  try {
    const dateFrom = req.query.dateFrom || new Date(Date.now() - 30 * 24 * 60 * 60 * 1000);
    const dateTo = req.query.dateTo || new Date();

    // Overall statistics
    const stats = await Ticket.findOne({
      attributes: [
        [sequelize.fn('COUNT', sequelize.col('id')), 'totalTickets'],
        [
          sequelize.fn('AVG', 
            sequelize.fn('TIMESTAMPDIFF', 
              sequelize.literal('HOUR'), 
              sequelize.col('createdAt'),
              sequelize.fn('CASE',
                sequelize.literal("WHEN status IN ('resolved', 'closed') THEN updatedAt ELSE NOW() END")
              )
            )
          ),
          'avgResolutionTime'
        ],
        [
          sequelize.fn('SUM', 
            sequelize.literal("CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END")
          ),
          'resolvedTickets'
        ],
        [
          sequelize.fn('SUM',
            sequelize.literal("CASE WHEN priority = 'high' THEN 1 ELSE 0 END")
          ),
          'highPriorityTickets'
        ]
      ],
      where: {
        createdAt: {
          [Op.between]: [dateFrom, dateTo]
        }
      }
    });

    // Status distribution
    const statusStats = await Ticket.findAll({
      attributes: [
        'status',
        [sequelize.fn('COUNT', sequelize.col('id')), 'count'],
        [
          sequelize.literal('COUNT(*) * 100.0 / (SELECT COUNT(*) FROM tickets WHERE createdAt BETWEEN :dateFrom AND :dateTo)'),
          'percentage'
        ]
      ],
      where: {
        createdAt: {
          [Op.between]: [dateFrom, dateTo]
        }
      },
      group: ['status'],
      replacements: { dateFrom, dateTo }
    });

    // Agency performance
    const agencyStats = await Agency.findAll({
      attributes: [
        'name',
        [sequelize.fn('COUNT', sequelize.col('tickets.id')), 'ticketCount'],
        [
          sequelize.fn('AVG',
            sequelize.fn('TIMESTAMPDIFF',
              sequelize.literal('HOUR'),
              sequelize.col('tickets.createdAt'),
              sequelize.fn('CASE',
                sequelize.literal("WHEN tickets.status IN ('resolved', 'closed') THEN tickets.updatedAt ELSE NOW() END")
              )
            )
          ),
          'avgResolutionTime'
        ],
        [
          sequelize.literal("SUM(CASE WHEN tickets.status IN ('resolved', 'closed') THEN 1 ELSE 0 END) * 100.0 / COUNT(tickets.id)"),
          'resolutionRate'
        ]
      ],
      include: [{
        model: Ticket,
        attributes: [],
        where: {
          createdAt: {
            [Op.between]: [dateFrom, dateTo]
          }
        },
        required: false
      }],
      group: ['Agency.id', 'Agency.name'],
      order: [[sequelize.literal('ticketCount'), 'DESC']]
    });

    // Daily statistics
    const dailyStats = await Ticket.findAll({
      attributes: [
        [sequelize.fn('DATE', sequelize.col('createdAt')), 'date'],
        [sequelize.fn('COUNT', sequelize.col('id')), 'newTickets'],
        [
          sequelize.fn('SUM',
            sequelize.literal("CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END")
          ),
          'resolvedTickets'
        ]
      ],
      where: {
        createdAt: {
          [Op.between]: [dateFrom, dateTo]
        }
      },
      group: [sequelize.fn('DATE', sequelize.col('createdAt'))],
      order: [[sequelize.fn('DATE', sequelize.col('createdAt')), 'ASC']]
    });

    res.json({
      stats: stats.get({ plain: true }),
      statusStats: statusStats.map(stat => stat.get({ plain: true })),
      agencyStats: agencyStats.map(stat => stat.get({ plain: true })),
      dailyStats: dailyStats.map(stat => stat.get({ plain: true }))
    });

  } catch (error) {
    logger.error('Analytics error:', error);
    res.status(500).json({ error: 'Error fetching analytics data' });
  }
});

// Export report endpoint
router.get('/export', isAdmin, async (req, res) => {
  try {
    const dateFrom = req.query.dateFrom || new Date(Date.now() - 30 * 24 * 60 * 60 * 1000);
    const dateTo = req.query.dateTo || new Date();

    // Fetch all the data
    const [stats, statusStats, agencyStats, dailyStats] = await Promise.all([
      // Overall statistics
      Ticket.findOne({
        attributes: [
          [sequelize.fn('COUNT', sequelize.col('id')), 'totalTickets'],
          [sequelize.fn('AVG', sequelize.fn('TIMESTAMPDIFF', sequelize.literal('HOUR'), sequelize.col('createdAt'), sequelize.fn('CASE', sequelize.literal("WHEN status IN ('resolved', 'closed') THEN updatedAt ELSE NOW() END")))), 'avgResolutionTime'],
          [sequelize.fn('SUM', sequelize.literal("CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END")), 'resolvedTickets'],
          [sequelize.fn('SUM', sequelize.literal("CASE WHEN priority = 'high' THEN 1 ELSE 0 END")), 'highPriorityTickets']
        ],
        where: { createdAt: { [Op.between]: [dateFrom, dateTo] } }
      }),
      // Status distribution
      Ticket.findAll({
        attributes: [
          'status',
          [sequelize.fn('COUNT', sequelize.col('id')), 'count'],
          [sequelize.literal('COUNT(*) * 100.0 / (SELECT COUNT(*) FROM tickets WHERE createdAt BETWEEN :dateFrom AND :dateTo)'), 'percentage']
        ],
        where: { createdAt: { [Op.between]: [dateFrom, dateTo] } },
        group: ['status'],
        replacements: { dateFrom, dateTo }
      }),
      // Agency performance
      Agency.findAll({
        attributes: [
          'name',
          [sequelize.fn('COUNT', sequelize.col('tickets.id')), 'ticketCount'],
          [sequelize.fn('AVG', sequelize.fn('TIMESTAMPDIFF', sequelize.literal('HOUR'), sequelize.col('tickets.createdAt'), sequelize.fn('CASE', sequelize.literal("WHEN tickets.status IN ('resolved', 'closed') THEN tickets.updatedAt ELSE NOW() END")))), 'avgResolutionTime'],
          [sequelize.literal("SUM(CASE WHEN tickets.status IN ('resolved', 'closed') THEN 1 ELSE 0 END) * 100.0 / COUNT(tickets.id)"), 'resolutionRate']
        ],
        include: [{
          model: Ticket,
          attributes: [],
          where: { createdAt: { [Op.between]: [dateFrom, dateTo] } },
          required: false
        }],
        group: ['Agency.id', 'Agency.name'],
        order: [[sequelize.literal('ticketCount'), 'DESC']]
      }),
      // Daily statistics
      Ticket.findAll({
        attributes: [
          [sequelize.fn('DATE', sequelize.col('createdAt')), 'date'],
          [sequelize.fn('COUNT', sequelize.col('id')), 'newTickets'],
          [sequelize.fn('SUM', sequelize.literal("CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END")), 'resolvedTickets']
        ],
        where: { createdAt: { [Op.between]: [dateFrom, dateTo] } },
        group: [sequelize.fn('DATE', sequelize.col('createdAt'))],
        order: [[sequelize.fn('DATE', sequelize.col('createdAt')), 'ASC']]
      })
    ]);

    // Set headers for CSV download
    res.setHeader('Content-Type', 'text/csv');
    res.setHeader('Content-Disposition', `attachment; filename=ticket_report_${dateFrom.toISOString().split('T')[0]}_to_${dateTo.toISOString().split('T')[0]}.csv`);

    // Write CSV data
    const csvData = [];

    // Summary statistics
    csvData.push(['Summary Statistics']);
    csvData.push(['Total Tickets', stats.get('totalTickets')]);
    csvData.push(['Resolved Tickets', stats.get('resolvedTickets')]);
    csvData.push(['Average Resolution Time (Days)', (stats.get('avgResolutionTime') / 24).toFixed(1)]);
    csvData.push(['High Priority Tickets', stats.get('highPriorityTickets')]);
    csvData.push([]);

    // Status distribution
    csvData.push(['Status Distribution']);
    csvData.push(['Status', 'Count', 'Percentage']);
    statusStats.forEach(stat => {
      csvData.push([
        stat.status,
        stat.get('count'),
        stat.get('percentage').toFixed(1) + '%'
      ]);
    });
    csvData.push([]);

    // Agency performance
    csvData.push(['Agency Performance']);
    csvData.push(['Agency', 'Total Tickets', 'Avg. Resolution Time (Days)', 'Resolution Rate']);
    agencyStats.forEach(stat => {
      csvData.push([
        stat.name,
        stat.get('ticketCount'),
        (stat.get('avgResolutionTime') / 24).toFixed(1),
        stat.get('resolutionRate').toFixed(1) + '%'
      ]);
    });
    csvData.push([]);

    // Daily statistics
    csvData.push(['Daily Statistics']);
    csvData.push(['Date', 'New Tickets', 'Resolved Tickets']);
    dailyStats.forEach(stat => {
      csvData.push([
        stat.get('date'),
        stat.get('newTickets'),
        stat.get('resolvedTickets')
      ]);
    });

    // Send CSV data
    res.write('\ufeff'); // BOM for Excel
    csvData.forEach(row => {
      res.write(row.join(',') + '\n');
    });
    res.end();

  } catch (error) {
    logger.error('Export report error:', error);
    res.status(500).json({ error: 'Error generating report' });
  }
});

module.exports = router; 