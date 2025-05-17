const { DataTypes } = require('sequelize');

module.exports = (sequelize) => {
  const Response = sequelize.define('Response', {
    id: {
      type: DataTypes.INTEGER,
      primaryKey: true,
      autoIncrement: true
    },
    content: {
      type: DataTypes.TEXT,
      allowNull: false
    },
    ticketId: {
      type: DataTypes.INTEGER,
      allowNull: false,
      references: {
        model: 'Tickets',
        key: 'id'
      }
    },
    userId: {
      type: DataTypes.INTEGER,
      allowNull: false,
      references: {
        model: 'Users',
        key: 'id'
      }
    },
    isInternal: {
      type: DataTypes.BOOLEAN,
      defaultValue: false
    }
  });

  Response.associate = (models) => {
    Response.belongsTo(models.Ticket, {
      foreignKey: 'ticketId',
      as: 'ticket'
    });
    Response.belongsTo(models.User, {
      foreignKey: 'userId',
      as: 'user'
    });
  };

  return Response;
}; 