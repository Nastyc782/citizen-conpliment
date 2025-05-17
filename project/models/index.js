const { Sequelize } = require('sequelize');
const config = require('../config/database');

const sequelize = new Sequelize(
  config.database,
  config.username,
  config.password,
  {
    host: config.host,
    dialect: 'mysql',
    logging: false
  }
);

const models = {
  User: require('./user')(sequelize),
  Agency: require('./agency')(sequelize),
  Category: require('./category')(sequelize),
  Ticket: require('./ticket')(sequelize),
  Response: require('./response')(sequelize),
  Attachment: require('./attachment')(sequelize)
};

// Define relationships
Object.keys(models).forEach(modelName => {
  if (models[modelName].associate) {
    models[modelName].associate(models);
  }
});

module.exports = {
  sequelize,
  ...models
}; 