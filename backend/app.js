/**
 * Root startup file for cPanel Phusion Passenger / PM2 / Node.js
 * Compatible with cPanel Setup Node.js App when startup file is 'app.js' (cPanel default)
 */
const path = require("path");

// Ensure environment variables from .env in application root are loaded
require("dotenv").config({ path: path.join(__dirname, ".env") });

// Start the pre-compiled Express application
require("./dist/server.js");
