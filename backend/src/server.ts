import { env } from "./config/env";
import app from "./app";
import { pool } from "./config/db";
import { initializeRbac } from "./config/initRbac";
import fs from "fs";
import path from "path";
import http from "http";

// Ensure uploads directory exists
const uploadsDir = path.join(process.cwd(), env.UPLOAD_DIR);
if (!fs.existsSync(uploadsDir)) {
  fs.mkdirSync(uploadsDir, { recursive: true });
}

async function startServer() {
  try {
    // Test DB connection
    const connection = await pool.getConnection();
    console.log("Database connected successfully");
    connection.release();

    // Bootstrap and sync RBAC permissions, roles, and safety tables
    await initializeRbac();

    const server = app.listen(env.PORT, () => {
      console.log(`Server running on http://localhost:${env.PORT}`);
    });

    // Graceful shutdown
    const shutdown = (signal: string) => {
      console.log(`\n${signal} received — shutting down gracefully`);
      server.close(async () => {
        await pool.end();
        console.log("Database pool closed");
        process.exit(0);
      });
    };

    process.on("SIGTERM", () => shutdown("SIGTERM"));
    process.on("SIGINT", () => shutdown("SIGINT"));
  } catch (error) {
    console.error("Failed to start server:", error);
    process.exit(1);
  }
}

startServer();
