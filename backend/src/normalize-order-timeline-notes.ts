import { sql } from "drizzle-orm";
import { db } from "./config/db";

const run = async () => {
  try {
    await db.execute(sql`
      UPDATE order_status_history
      SET note = 'Order created'
      WHERE note LIKE '%fallback%' OR note LIKE '%Legacy order%'
    `);

    console.log("✓ normalized fallback timeline notes");
  } catch (error) {
    console.error("✗ failed to normalize timeline notes", error);
    process.exitCode = 1;
  }
};

run();
