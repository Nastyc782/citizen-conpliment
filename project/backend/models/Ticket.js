const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');
const User = require('./User');
const Agency = require('./Agency');

const Ticket = sequelize.define('Ticket', {
    id: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true
    },
    subject: {
        type: DataTypes.STRING(255),
        allowNull: false
    },
    message: {
        type: DataTypes.TEXT,
        allowNull: false
    },
    category: {
        type: DataTypes.STRING(100),
        allowNull: false
    },
    status: {
        type: DataTypes.ENUM('submitted', 'in_progress', 'resolved'),
        defaultValue: 'submitted'
    },
    priority: {
        type: DataTypes.ENUM('low', 'medium', 'high'),
        defaultValue: 'medium'
    }
}, {
    timestamps: true
});

// Define associations
Ticket.belongsTo(User, { as: 'citizen', foreignKey: 'userId' });
Ticket.belongsTo(Agency, { foreignKey: 'agencyId' });

module.exports = Ticket; 