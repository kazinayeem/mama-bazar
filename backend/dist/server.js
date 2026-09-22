"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const env_1 = require("./config/env");
const app_1 = __importDefault(require("./app"));
const db_1 = require("./config/db");
const initRbac_1 = require("./config/initRbac");
const fs_1 = __importDefault(require("fs"));
const path_1 = __importDefault(require("path"));
// Ensure uploads directory exists
const uploadsDir = path_1.default.join(process.cwd(), env_1.env.UPLOAD_DIR);
if (!fs_1.default.existsSync(uploadsDir)) {
    fs_1.default.mkdirSync(uploadsDir, { recursive: true });
}
async function startServer() {
    try {
        console.log("=========================================");
        console.log("🚀 Mama Bazar Backend Server Starting...");
        console.log(`📌 Node: ${process.version}`);
        console.log(`📌 Environment: ${env_1.env.NODE_ENV}`);
        console.log(`📌 Working Directory: ${process.cwd()}`);
        console.log(`📌 Port / Pipe: ${env_1.env.PORT}`);
        if (!env_1.env.DATABASE_URL) {
            console.warn("⚠️ Warning: No DATABASE_URL or DB credentials found in .env! Database queries will fail.");
        }
        // Test DB connection
        const connection = await db_1.pool.getConnection();
        console.log("✅ Database connected successfully");
        connection.release();
        // Bootstrap and sync RBAC permissions, roles, and safety tables
        try {
            await (0, initRbac_1.initializeRbac)();
            console.log("✅ RBAC & security initialized");
        }
        catch (rbacError) {
            console.warn("⚠️ RBAC auto-init notice:", rbacError?.message || rbacError);
        }
        const server = app_1.default.listen(env_1.env.PORT, () => {
            console.log(`✅ Server is listening on ${env_1.env.PORT}`);
            console.log("=========================================");
        });
        // Graceful shutdown
        const shutdown = (signal) => {
            console.log(`\n${signal} received — shutting down gracefully`);
            server.close(async () => {
                await db_1.pool.end();
                console.log("Database pool closed");
                process.exit(0);
            });
        };
        process.on("SIGTERM", () => shutdown("SIGTERM"));
        process.on("SIGINT", () => shutdown("SIGINT"));
    }
    catch (error) {
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
//# sourceMappingURL=server.js.map