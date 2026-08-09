"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const drizzle_orm_1 = require("drizzle-orm");
const db_1 = require("./config/db");
const run = async () => {
    try {
        await db_1.db.execute((0, drizzle_orm_1.sql) `
      ALTER TABLE orders
      MODIFY COLUMN status ENUM(
        'pending',
        'confirmed',
        'processing',
        'packed',
        'shipped',
        'out_for_delivery',
        'delivered',
        'cancelled'
      ) NOT NULL DEFAULT 'pending'
    `);
        await db_1.db.execute((0, drizzle_orm_1.sql) `
      ALTER TABLE order_status_history
      MODIFY COLUMN status ENUM(
        'pending',
        'confirmed',
        'processing',
        'packed',
        'shipped',
        'out_for_delivery',
        'delivered',
        'cancelled'
      ) NOT NULL
    `);
        console.log("✓ order status steps expanded successfully");
    }
    catch (error) {
        console.error("✗ Failed to expand order status steps", error);
        process.exitCode = 1;
    }
};
run();
//# sourceMappingURL=migrate-order-status-steps.js.map