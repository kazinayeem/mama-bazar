import { sql } from "drizzle-orm";
import { db } from "./config/db";

const run = async () => {
  try {
    // ---------- New tables ----------
    await db.execute(sql`
      CREATE TABLE IF NOT EXISTS shipping_methods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        charge DECIMAL(10,2) NOT NULL DEFAULT 0,
        estimated_delivery VARCHAR(100),
        description TEXT,
        priority INT DEFAULT 0,
        free_shipping_min_amount DECIMAL(10,2),
        cod_available TINYINT DEFAULT 1,
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    await db.execute(sql`
      CREATE TABLE IF NOT EXISTS payment_methods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(30) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        type ENUM('cod','mobile_banking','bank','online') NOT NULL,
        enabled TINYINT DEFAULT 1,
        sort_order INT DEFAULT 0,
        maintenance_mode TINYINT DEFAULT 0,
        config JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      )
    `);

    await db.execute(sql`
      CREATE TABLE IF NOT EXISTS checkout_notices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        text TEXT NOT NULL,
        priority INT DEFAULT 0,
        background_color VARCHAR(50) DEFAULT '#FFF7ED',
        text_color VARCHAR(50) DEFAULT '#9A3412',
        icon VARCHAR(50) DEFAULT 'alert',
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // ---------- orders: new columns ----------
    const orderColumns: Array<[string, string]> = [
      ["order_id", "VARCHAR(20) NOT NULL"],
      ["user_id", "INT"],
      ["transaction_id", "VARCHAR(100)"],
      ["courier_tracking_number", "VARCHAR(120)"],
      ["payment_status", "ENUM('pending','payment_pending','payment_verification','verified','success','failed','rejected','refunded') NOT NULL DEFAULT 'pending'"],
      ["alternative_phone", "VARCHAR(20)"],
      ["email", "VARCHAR(255)"],
      ["country", "VARCHAR(100)"],
      ["division", "VARCHAR(100)"],
      ["district", "VARCHAR(100)"],
      ["upazila", "VARCHAR(100)"],
      ["area", "VARCHAR(150)"],
      ["apartment", "VARCHAR(255)"],
      ["postal_code", "VARCHAR(20)"],
      ["shipping_method_id", "INT"],
      ["shipping_method_name", "VARCHAR(255)"],
      ["subtotal", "DECIMAL(10,2) DEFAULT 0"],
      ["tax", "DECIMAL(10,2) DEFAULT 0"],
      ["checkout_notes", "TEXT"],
      ["admin_notes", "TEXT"],
      ["sender_number", "VARCHAR(30)"],
      ["payment_screenshot", "VARCHAR(500)"],
      ["payment_date", "TIMESTAMP NULL"],
      ["amount_sent", "DECIMAL(10,2)"],
      ["payment_instructions", "TEXT"],
    ];
    for (const [column, definition] of orderColumns) {
      await db.execute(
        sql`ALTER TABLE orders ADD COLUMN IF NOT EXISTS ${sql.raw(column)} ${sql.raw(definition)}`
      );
    }

    // ---------- orders: unique index on order_id (added separately; TiDB rejects inline UNIQUE) ----------
    const uniqueRows = await db.execute(
      sql`SELECT COUNT(*) AS c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND NON_UNIQUE = 0 AND COLUMN_NAME = 'order_id'`
    );
    const hasUniqueIndex = (uniqueRows as unknown as Array<{ c: number }>)[0].c > 0;
    if (!hasUniqueIndex) {
      await db.execute(sql`ALTER TABLE orders ADD UNIQUE INDEX orders_order_id_unique (order_id)`);
      console.log("orders.order_id unique index added");
    }

    // ---------- orders: remove legacy shipping_area (full address fields replaced it) ----------
    await db.execute(sql`ALTER TABLE orders DROP COLUMN IF EXISTS shipping_area`);
    console.log("orders.shipping_area dropped");

    // ---------- orders: extend enums ----------
    const getColumnType = async (table: string, column: string): Promise<string | null> => {
      const rows = await db.execute(
        sql`SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ${table} AND COLUMN_NAME = ${column}`
      );
      const r = rows as unknown as Array<{ COLUMN_TYPE: string }>;
      return r && r.length ? r[0].COLUMN_TYPE : null;
    };

    const paymentMethodType = await getColumnType("orders", "payment_method");
    if (!paymentMethodType || !paymentMethodType.includes("bkash")) {
      await db.execute(sql`
        ALTER TABLE orders MODIFY COLUMN payment_method ENUM('cod','bkash','nagad','rocket','bank','stripe','sslcommerz','paypal') NOT NULL DEFAULT 'cod'
      `);
      console.log("orders.payment_method enum extended");
    }

    const paymentStatusType = await getColumnType("orders", "payment_status");
    if (!paymentStatusType || !paymentStatusType.includes("payment_verification")) {
      await db.execute(sql`
        ALTER TABLE orders MODIFY COLUMN payment_status ENUM('pending','payment_pending','payment_verification','verified','success','failed','rejected','refunded') NOT NULL DEFAULT 'pending'
      `);
      console.log("orders.payment_status enum extended");
    }

    const orderStatusType = await getColumnType("orders", "status");
    if (!orderStatusType || !orderStatusType.includes("refunded")) {
      await db.execute(sql`
        ALTER TABLE orders MODIFY COLUMN status ENUM('pending','payment_pending','payment_verification','confirmed','processing','packed','shipped','out_for_delivery','delivered','returned','cancelled','refunded') NOT NULL DEFAULT 'pending'
      `);
      console.log("orders.status enum extended");
    }

    const historyStatusType = await getColumnType("order_status_history", "status");
    if (!historyStatusType || !historyStatusType.includes("refunded")) {
      await db.execute(sql`
        ALTER TABLE order_status_history MODIFY COLUMN status ENUM('pending','payment_pending','payment_verification','confirmed','processing','packed','shipped','out_for_delivery','delivered','returned','cancelled','refunded') NOT NULL
      `);
      console.log("order_status_history.status enum extended");
    }

    // ---------- order_items: new columns ----------
    const orderItemColumns: Array<[string, string]> = [
      ["variant_id", "INT"],
      ["size", "VARCHAR(30)"],
      ["color", "VARCHAR(50)"],
    ];
    for (const [column, definition] of orderItemColumns) {
      await db.execute(
        sql`ALTER TABLE order_items ADD COLUMN IF NOT EXISTS ${sql.raw(column)} ${sql.raw(definition)}`
      );
    }

    // ---------- user_addresses: new columns ----------
    const addressColumns: Array<[string, string]> = [
      ["alternative_phone", "VARCHAR(20)"],
      ["email", "VARCHAR(255)"],
      ["country", "VARCHAR(100)"],
      ["division", "VARCHAR(100)"],
      ["district", "VARCHAR(100)"],
      ["upazila", "VARCHAR(100)"],
      ["area", "VARCHAR(150)"],
      ["apartment", "VARCHAR(255)"],
      ["postal_code", "VARCHAR(20)"],
    ];
    for (const [column, definition] of addressColumns) {
      await db.execute(
        sql`ALTER TABLE user_addresses ADD COLUMN IF NOT EXISTS ${sql.raw(column)} ${sql.raw(definition)}`
      );
    }

    console.log("Checkout migration complete");
    process.exit(0);
  } catch (error) {
    console.error("Migration failed:", error);
    process.exit(1);
  }
};

run();
