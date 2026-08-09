"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const db_1 = require("./config/db");
const drizzle_orm_1 = require("drizzle-orm");
async function runMigrations() {
    console.log("🔄 Running migrations...");
    try {
        // Add payment_methods to products if not exists
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS payment_methods JSON`);
        console.log("✓ Added payment_methods column to products");
        // Set default for payment_methods on existing rows
        await db_1.db.execute((0, drizzle_orm_1.sql) `UPDATE products SET payment_methods = '["cod"]' WHERE payment_methods IS NULL`);
        // Add payment_phone_number to products if not exists
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS payment_phone_number VARCHAR(20)`);
        console.log("✓ Added payment_phone_number column to products");
        // Add transaction_id to orders if not exists
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE orders ADD COLUMN IF NOT EXISTS transaction_id VARCHAR(100)`);
        console.log("✓ Added transaction_id column to orders");
        // Add payment_status to orders if not exists
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'verified', 'success') DEFAULT 'pending'`);
        console.log("✓ Added payment_status column to orders");
        console.log("✅ Migrations completed successfully");
        process.exit(0);
    }
    catch (error) {
        if (error.message.includes("Duplicate column")) {
            console.log("ℹ️  Columns already exist, skipping...");
            process.exit(0);
        }
        console.error("❌ Migration failed:", error.message);
        process.exit(1);
    }
}
runMigrations();
//# sourceMappingURL=migrate-payment.js.map