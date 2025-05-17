const { DataTypes } = require('sequelize');

module.exports = (sequelize) => {
  const Ticket = sequelize.define('Ticket', {
    id: {
      type: DataTypes.INTEGER,
      primaryKey: true,
      autoIncrement: true
    },
    title: {
      type: DataTypes.STRING,
      allowNull: false
    },
    description: {
      type: DataTypes.TEXT,
      allowNull: false
    },
    status: {
      type: DataTypes.ENUM('pending', 'in_progress', 'under_review', 'resolved', 'closed', 'approved', 'rejected'),
      defaultValue: 'pending'
    },
    priority: {
      type: DataTypes.ENUM('low', 'medium', 'high'),
      defaultValue: 'medium'
    },
    userId: {
      type: DataTypes.INTEGER,
      allowNull: false,
      references: {
        model: 'Users',
        key: 'id'
      }
    },
    agencyId: {
      type: DataTypes.INTEGER,
      allowNull: false,
      references: {
        model: 'Agencies',
        key: 'id'
      }
    },
    categoryId: {
      type: DataTypes.INTEGER,
      allowNull: false,
      references: {
        model: 'Categories',
        key: 'id'
      }
    },
    updatedBy: {
      type: DataTypes.INTEGER,
      references: {
        model: 'Users',
        key: 'id'
      }
    },
    adminComment: {
      type: DataTypes.TEXT
    },
    adminActionAt: {
      type: DataTypes.DATE
    }
  });

  Ticket.associate = (models) => {
    Ticket.belongsTo(models.User, {
      foreignKey: 'userId',
      as: 'user'
    });
    Ticket.belongsTo(models.Agency, {
      foreignKey: 'agencyId',
      as: 'agency'
    });
    Ticket.belongsTo(models.Category, {
      foreignKey: 'categoryId',
      as: 'category'
    });
    Ticket.belongsTo(models.User, {
      foreignKey: 'updatedBy',
      as: 'updatedByUser'
    });
    Ticket.hasMany(models.Response, {
      foreignKey: 'ticketId',
      as: 'responses'
    });
    Ticket.hasMany(models.Attachment, {
      foreignKey: 'ticketId',
      as: 'attachments'
    });
  };

  return Ticket;
}; 