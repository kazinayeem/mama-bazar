"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const db_1 = require("./config/db");
const drizzle_orm_1 = require("drizzle-orm");
async function runMigrations() {
    console.log("🔄 Running email removal migrations...");
    try {
        // Remove email column from users table if it exists
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE users DROP COLUMN IF EXISTS email`);
        console.log("✓ Dropped email column from users table");
        // Make phone unique if not already
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE users MODIFY phone VARCHAR(20) NOT NULL UNIQUE`);
        console.log("✓ Made phone column unique and not null");
        console.log("✅ Migration completed successfully");
        process.exit(0);
    }
    catch (error) {
        if (error.message.includes("already exists") || error.message.includes("Duplicate")) {
            console.log("ℹ️  Column adjustments already applied, continuing...");
            process.exit(0);
        }
        console.error("❌ Migration failed:", error.message);
        process.exit(1);
    }
}
runMigrations();
//# sourceMappingURL=migrate-remove-email.js.map