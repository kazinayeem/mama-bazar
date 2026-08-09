"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const env_1 = require("./config/env");
const app_1 = __importDefault(require("./app"));
const db_1 = require("./config/db");
const fs_1 = __importDefault(require("fs"));
const path_1 = __importDefault(require("path"));
// Ensure uploads directory exists
const uploadsDir = path_1.default.join(process.cwd(), env_1.env.UPLOAD_DIR);
if (!fs_1.default.existsSync(uploadsDir)) {
    fs_1.default.mkdirSync(uploadsDir, { recursive: true });
}
async function startServer() {
    try {
        // Test DB connection
        const connection = await db_1.pool.getConnection();
        console.log("Database connected successfully");
        connection.release();
        const server = app_1.default.listen(env_1.env.PORT, () => {
            console.log(`Server running on http://localhost:${env_1.env.PORT}`);
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
        console.error("Failed to start server:", error);
        process.exit(1);
    }
}
startServer();
//# sourceMappingURL=server.js.map