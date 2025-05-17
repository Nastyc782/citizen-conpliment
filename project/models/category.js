const { DataTypes } = require('sequelize');

module.exports = (sequelize) => {
  const Category = sequelize.define('Category', {
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
    agencyId: {
      type: DataTypes.INTEGER,
      allowNull: false,
      references: {
        model: 'Agencies',
        key: 'id'
      }
    }
  });

  Category.associate = (models) => {
    Category.belongsTo(models.Agency, {
      foreignKey: 'agencyId',
      as: 'agency'
    });
    Category.hasMany(models.Ticket, {
      foreignKey: 'categoryId',
      as: 'tickets'
    });
  };

  return Category;
}; 