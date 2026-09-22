/**
 * Root startup file for cPanel Phusion Passenger / PM2 / Node.js
 * Compatible with cPanel Setup Node.js App when startup file is 'server.js'
 */
const fs = require("fs");
const path = require("path");

// Load environment variables (.env, .env.local, or .env.production)
const candidateFiles = [".env", ".env.local", ".env.production"];
for (const file of candidateFiles) {
  const envPath = path.join(__dirname, file);
  if (fs.existsSync(envPath)) {
    require("dotenv").config({ path: envPath });
  }
}

// Start the pre-compiled Express application
require("./dist/server.js");

