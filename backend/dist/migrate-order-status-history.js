"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const drizzle_orm_1 = require("drizzle-orm");
const db_1 = require("./config/db");
const run = async () => {
    try {
        await db_1.db.execute((0, drizzle_orm_1.sql) `
      CREATE TABLE IF NOT EXISTS order_status_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        status ENUM('pending', 'confirmed', 'processing', 'packed', 'shipped', 'out_for_delivery', 'delivered', 'cancelled') NOT NULL,
        note TEXT,
        created_by_user_id INT,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_order_status_history_order_id FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        CONSTRAINT fk_order_status_history_created_by_user_id FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
      )
    `);
        console.log("✓ order_status_history table is ready");
    }
    catch (error) {
        console.error("✗ Failed to prepare order_status_history table", error);
        process.exitCode = 1;
    }
};
run();
//# sourceMappingURL=migrate-order-status-history.js.map