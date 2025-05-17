const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');
const User = require('./User');
const Ticket = require('./Ticket');

const Response = sequelize.define('Response', {
    id: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true
    },
    message: {
        type: DataTypes.TEXT,
        allowNull: false
    },
    isInternal: {
        type: DataTypes.BOOLEAN,
        defaultValue: false
    }
}, {
    timestamps: true
});

// Define associations
Response.belongsTo(Ticket, { foreignKey: 'ticketId' });
Response.belongsTo(User, { as: 'responder', foreignKey: 'userId' });

module.exports = Response; 