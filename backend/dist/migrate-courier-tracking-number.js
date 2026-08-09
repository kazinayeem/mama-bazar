"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const drizzle_orm_1 = require("drizzle-orm");
const db_1 = require("./config/db");
const run = async () => {
    try {
        await db_1.db.execute((0, drizzle_orm_1.sql) `
      ALTER TABLE orders
      ADD COLUMN IF NOT EXISTS courier_tracking_number VARCHAR(120)
    `);
        console.log("✓ courier_tracking_number column is ready");
    }
    catch (error) {
        console.error("✗ failed to add courier_tracking_number column", error);
        process.exitCode = 1;
    }
};
run();
//# sourceMappingURL=migrate-courier-tracking-number.js.map