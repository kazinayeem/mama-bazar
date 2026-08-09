import { sql } from "drizzle-orm";
import { db } from "./config/db";

const run = async () => {
  try {
    await db.execute(sql`
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

    await db.execute(sql`
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
  } catch (error) {
    console.error("✗ Failed to expand order status steps", error);
    process.exitCode = 1;
  }
};

run();
