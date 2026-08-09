"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const drizzle_orm_1 = require("drizzle-orm");
const db_1 = require("./config/db");
const run = async () => {
    try {
        await db_1.db.execute((0, drizzle_orm_1.sql) `
      CREATE TABLE IF NOT EXISTS \`newsletters\` (
        \`id\` int NOT NULL AUTO_INCREMENT,
        \`email\` varchar(255) NOT NULL,
        \`source\` varchar(100) DEFAULT 'homepage',
        \`status\` enum('subscribed','unsubscribed') NOT NULL DEFAULT 'subscribed',
        \`subscribed_at\` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (\`id\`),
        UNIQUE KEY \`newsletters_email_unique\` (\`email\`)
      )
    `);
        console.log("✓ newsletters table is ready");
    }
    catch (error) {
        console.error("✗ failed to create newsletters table", error);
        process.exitCode = 1;
    }
};
run();
//# sourceMappingURL=migrate-newsletters.js.map