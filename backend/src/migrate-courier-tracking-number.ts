import { sql } from "drizzle-orm";
import { db } from "./config/db";

const run = async () => {
  try {
    await db.execute(sql`
      ALTER TABLE orders
      ADD COLUMN IF NOT EXISTS courier_tracking_number VARCHAR(120)
    `);

    console.log("✓ courier_tracking_number column is ready");
  } catch (error) {
    console.error("✗ failed to add courier_tracking_number column", error);
    process.exitCode = 1;
  }
};

run();
