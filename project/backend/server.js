const express = require('express');
const cors = require('cors');
const sequelize = require('./config/database');
const authRoutes = require('./routes/auth');
const ticketRoutes = require('./routes/tickets');

// Import models for associations
const User = require('./models/User');
const Agency = require('./models/Agency');
const Ticket = require('./models/Ticket');
const Response = require('./models/Response');

const app = express();

// Middleware
app.use(cors());
app.use(express.json());

// Routes
app.use('/api/auth', authRoutes);
app.use('/api/tickets', ticketRoutes);

// Database sync and server start
const PORT = process.env.PORT || 5000;

const startServer = async () => {
    try {
        // Sync all models with database
        await sequelize.sync();
        console.log('Database connected and synced successfully.');
        
        app.listen(PORT, () => {
            console.log(`Server is running on port ${PORT}`);
        });
    } catch (error) {
        console.error('Unable to connect to the database:', error);
    }
};

startServer(); 