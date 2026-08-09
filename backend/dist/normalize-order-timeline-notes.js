"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const drizzle_orm_1 = require("drizzle-orm");
const db_1 = require("./config/db");
const run = async () => {
    try {
        await db_1.db.execute((0, drizzle_orm_1.sql) `
      UPDATE order_status_history
      SET note = 'Order created'
      WHERE note LIKE '%fallback%' OR note LIKE '%Legacy order%'
    `);
        console.log("✓ normalized fallback timeline notes");
    }
    catch (error) {
        console.error("✗ failed to normalize timeline notes", error);
        process.exitCode = 1;
    }
};
run();
//# sourceMappingURL=normalize-order-timeline-notes.js.map