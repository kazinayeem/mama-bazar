"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const db_1 = require("./config/db");
const drizzle_orm_1 = require("drizzle-orm");
async function runMigrations() {
    console.log("🔄 Running admin email migration...");
    try {
        // Add nullable email column to users table if it does not exist
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE users ADD COLUMN email VARCHAR(255) NULL`);
        console.log("✓ Added email column to users table");
        // Unique index prevents duplicate admin emails while allowing multiple NULLs
        try {
            await db_1.db.execute((0, drizzle_orm_1.sql) `CREATE UNIQUE INDEX users_email_unique ON users (email)`);
            console.log("✓ Added unique index on users.email");
        }
        catch (error) {
            if (!String(error?.message).includes("already exists") && !String(error?.message).includes("Duplicate")) {
                throw error;
            }
            console.log("ℹ️  Unique index already present, continuing...");
        }
        console.log("✅ Migration completed successfully");
        process.exit(0);
    }
    catch (error) {
        if (String(error?.message).includes("already exists") || String(error?.message).includes("Duplicate")) {
            console.log("ℹ️  Column already exists, continuing...");
            process.exit(0);
        }
        console.error("❌ Migration failed:", error.message);
        process.exit(1);
    }
}
runMigrations();
//# sourceMappingURL=migrate-add-admin-email.js.map