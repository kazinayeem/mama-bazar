import { db } from "./config/db";
import { sql } from "drizzle-orm";

async function runMigrations() {
  console.log("🔄 Running admin email migration...");
  try {
    // Add nullable email column to users table if it does not exist
    await db.execute(sql`ALTER TABLE users ADD COLUMN email VARCHAR(255) NULL`);
    console.log("✓ Added email column to users table");

    // Unique index prevents duplicate admin emails while allowing multiple NULLs
    try {
      await db.execute(sql`CREATE UNIQUE INDEX users_email_unique ON users (email)`);
      console.log("✓ Added unique index on users.email");
    } catch (error: any) {
      if (!String(error?.message).includes("already exists") && !String(error?.message).includes("Duplicate")) {
        throw error;
      }
      console.log("ℹ️  Unique index already present, continuing...");
    }

    console.log("✅ Migration completed successfully");
    process.exit(0);
  } catch (error: any) {
    if (String(error?.message).includes("already exists") || String(error?.message).includes("Duplicate")) {
      console.log("ℹ️  Column already exists, continuing...");
      process.exit(0);
    }
    console.error("❌ Migration failed:", error.message);
    process.exit(1);
  }
}

runMigrations();