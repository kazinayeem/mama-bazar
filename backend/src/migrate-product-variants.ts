import { sql } from "drizzle-orm";
import { db } from "./config/db";

async function runMigrations() {
  try {
    console.log("Running product variant migrations...");

    await db.execute(
      sql`ALTER TABLE products ADD COLUMN IF NOT EXISTS size_options JSON`
    );
    await db.execute(
      sql`ALTER TABLE products ADD COLUMN IF NOT EXISTS color_options JSON`
    );

    await db.execute(
      sql`ALTER TABLE order_items ADD COLUMN IF NOT EXISTS variant_id INT`
    );
    await db.execute(
      sql`ALTER TABLE order_items ADD FOREIGN KEY IF NOT EXISTS fk_order_items_variant (variant_id) REFERENCES product_variants(id)`
    );
    await db.execute(
      sql`ALTER TABLE order_items ADD COLUMN IF NOT EXISTS size VARCHAR(30)`
    );
    await db.execute(
      sql`ALTER TABLE order_items ADD COLUMN IF NOT EXISTS color VARCHAR(50)`
    );

    console.log("Migration completed successfully");
    process.exit(0);
  } catch (error: any) {
    console.error("Migration failed:", error?.message || error);
    process.exit(1);
  }
}

runMigrations();
