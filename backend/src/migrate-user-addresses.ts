import { sql } from "drizzle-orm";
import { db } from "./config/db";

const run = async () => {
  try {
    await db.execute(sql`
      CREATE TABLE IF NOT EXISTS user_addresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        recipient_name VARCHAR(255) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        shipping_area VARCHAR(100) NOT NULL,
        address TEXT NOT NULL,
        is_default BOOLEAN NOT NULL DEFAULT FALSE,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_user_addresses_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
      )
    `);

    console.log("✓ user_addresses table is ready");
  } catch (error) {
    console.error("✗ Failed to prepare user_addresses table", error);
    process.exitCode = 1;
  }
};

run();
