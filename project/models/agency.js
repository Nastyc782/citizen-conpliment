const { DataTypes } = require('sequelize');

module.exports = (sequelize) => {
  const Agency = sequelize.define('Agency', {
    id: {
      type: DataTypes.INTEGER,
      primaryKey: true,
      autoIncrement: true
    },
    name: {
      type: DataTypes.STRING,
      allowNull: false
    },
    description: {
      type: DataTypes.TEXT
    },
    contactEmail: {
      type: DataTypes.STRING,
      validate: {
        isEmail: true
      }
    },
    contactPhone: {
      type: DataTypes.STRING
    }
  });

  Agency.associate = (models) => {
    Agency.hasMany(models.Category, {
      foreignKey: 'agencyId',
      as: 'categories'
    });
    Agency.hasMany(models.Ticket, {
      foreignKey: 'agencyId',
      as: 'tickets'
    });
  };

  return Agency;
}; 