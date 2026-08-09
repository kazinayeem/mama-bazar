"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const drizzle_orm_1 = require("drizzle-orm");
const db_1 = require("./config/db");
async function runMigrations() {
    try {
        console.log("Running product variant migrations...");
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS size_options JSON`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS color_options JSON`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE order_items ADD COLUMN IF NOT EXISTS size VARCHAR(30)`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE order_items ADD COLUMN IF NOT EXISTS color VARCHAR(50)`);
        console.log("Migration completed successfully");
        process.exit(0);
    }
    catch (error) {
        console.error("Migration failed:", error?.message || error);
        process.exit(1);
    }
}
runMigrations();
//# sourceMappingURL=migrate-product-variants.js.map