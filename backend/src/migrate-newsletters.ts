import { sql } from "drizzle-orm";
import { db } from "./config/db";

const run = async () => {
  try {
    await db.execute(sql`
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
  } catch (error) {
    console.error("✗ failed to create newsletters table", error);
    process.exitCode = 1;
  }
};

run();
