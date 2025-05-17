const sequelize = require('./database');
const User = require('../models/User');
const Ticket = require('../models/Ticket');
const Response = require('../models/Response');
const Agency = require('../models/Agency');

const initDatabase = async () => {
  try {
    // Test the connection
    await sequelize.authenticate();
    console.log('Database connection established successfully.');

    // Sync all models
    await sequelize.sync({ alter: true });
    console.log('Database models synchronized successfully.');

  } catch (error) {
    console.error('Unable to connect to the database:', error);
    process.exit(1);
  }
};

module.exports = initDatabase; 