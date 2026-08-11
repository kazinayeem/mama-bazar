import { sql } from "drizzle-orm";
import { db } from "./config/db";

async function runMigrations() {
  try {
    console.log("Running performance index migrations...");

    // Products indexes (most critical for shop performance)
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_products_category ON products(category_id)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_products_sub_category ON products(sub_category_id)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_products_child_category ON products(child_category_id)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_products_brand ON products(brand_id)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_products_collection ON products(collection_id)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_products_vendor ON products(vendor_id)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_products_supplier ON products(supplier_id)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_products_status ON products(status)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_products_featured ON products(is_featured)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_products_trending ON products(is_trending)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_products_flash_sale ON products(is_flash_sale)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_products_created ON products(created_at)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_products_slug ON products(slug)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_products_price ON products(price)`
    );

    // Product variants indexes
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_variants_product ON product_variants(product_id)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_variants_status ON product_variants(status)`
    );

    // Orders indexes (critical for admin dashboard and user orders)
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_orders_user ON orders(user_id)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_orders_payment_status ON orders(payment_status)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_orders_created ON orders(created_at)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_orders_phone ON orders(phone)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_orders_order_id ON orders(order_id)`
    );

    // Order items indexes
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_order_items_order ON order_items(order_id)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_order_items_product ON order_items(product_id)`
    );

    // Order status history indexes
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_order_history_order ON order_status_history(order_id)`
    );

    // Reviews indexes
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_reviews_product ON reviews(product_id)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_reviews_user ON reviews(user_id)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_reviews_status ON reviews(status)`
    );

    // User addresses indexes
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_user_addresses_user ON user_addresses(user_id)`
    );

    // Categories indexes
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_categories_parent ON categories(parent_id)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_categories_status ON categories(status)`
    );

    // Product specs indexes
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_product_specs_product ON product_specs(product_id)`
    );

    // Product relations indexes
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_product_relations_product ON product_relations(product_id)`
    );
    await db.execute(
      sql`CREATE INDEX IF NOT EXISTS idx_product_relations_related ON product_relations(related_product_id)`
    );

    console.log("✓ Performance index migration complete");
    process.exit(0);
  } catch (error: any) {
    console.error("✗ Index migration failed:", error?.message || error);
    process.exit(1);
  }
}

runMigrations();
