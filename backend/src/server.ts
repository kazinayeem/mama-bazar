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
    console.log("=========================================");
    console.log("🚀 Mama Bazar Backend Server Starting...");
    console.log(`📌 Node: ${process.version}`);
    console.log(`📌 Environment: ${env.NODE_ENV}`);
    console.log(`📌 Working Directory: ${process.cwd()}`);
    console.log(`📌 Port / Pipe: ${env.PORT}`);

    if (!env.DATABASE_URL) {
      console.warn("⚠️ Warning: No DATABASE_URL or DB credentials found in .env! Database queries will fail.");
    }

    // Test DB connection
    const connection = await pool.getConnection();
    console.log("✅ Database connected successfully");
    connection.release();

    // Bootstrap and sync RBAC permissions, roles, and safety tables
    try {
      await initializeRbac();
      console.log("✅ RBAC & security initialized");
    } catch (rbacError: any) {
      console.warn("⚠️ RBAC auto-init notice:", rbacError?.message || rbacError);
    }

    const server = app.listen(env.PORT, () => {
      console.log(`✅ Server is listening on ${env.PORT}`);
      console.log("=========================================");
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
  } catch (error: any) {
    console.error("=========================================");
    console.error("❌ Failed to start server:", error?.message || error);
    if (error?.stack) {
      console.error(error.stack);
    }
    console.error("Please verify your database connection in .env and check credentials.");
    console.error("=========================================");
    process.exit(1);
  }
}

startServer();

