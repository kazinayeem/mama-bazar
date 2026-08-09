import { sql } from "drizzle-orm";
import { db } from "./config/db";

const run = async () => {
  try {
    // ---------- products: catalog + pricing + inventory columns ----------
    const productColumns: Array<[string, string]> = [
      ["sale_price", "DECIMAL(10,2)"],
      ["profit_margin", "DECIMAL(10,2) DEFAULT 0"],
      ["vat", "DECIMAL(10,2) DEFAULT 0"],
      ["cod_fee", "DECIMAL(10,2) DEFAULT 0"],
      ["flash_sale_price", "DECIMAL(10,2)"],
      ["wholesale_price", "DECIMAL(10,2)"],
      ["dealer_price", "DECIMAL(10,2)"],
      ["sub_category_id", "INT"],
      ["child_category_id", "INT"],
      ["collection_id", "INT"],
      ["vendor_id", "INT"],
      ["supplier_id", "INT"],
      ["country_of_origin", "VARCHAR(100)"],
      ["low_stock_alert", "INT DEFAULT 10"],
      ["min_order", "INT DEFAULT 1"],
      ["max_order", "INT"],
      ["unlimited_stock", "TINYINT DEFAULT 0"],
      ["backorder", "TINYINT DEFAULT 0"],
      ["track_inventory", "TINYINT DEFAULT 1"],
      ["stock_status", "VARCHAR(20) DEFAULT 'in_stock'"],
      ["product_status", "VARCHAR(30) DEFAULT 'published'"],
      ["is_limited_edition", "TINYINT DEFAULT 0"],
      ["is_official", "TINYINT DEFAULT 0"],
      ["is_hot_deal", "TINYINT DEFAULT 0"],
      ["canonical_url", "VARCHAR(500)"],
      ["og_image", "VARCHAR(500)"],
      ["twitter_image", "VARCHAR(500)"],
      ["structured_data", "JSON"],
      ["draft", "JSON"],
    ];
    for (const [column, definition] of productColumns) {
      await db.execute(
        sql`ALTER TABLE products ADD COLUMN IF NOT EXISTS ${sql.raw(column)} ${sql.raw(definition)}`
      );
    }

    // ---------- categories: hierarchy + catalog columns ----------
    const categoryColumns: Array<[string, string]> = [
      ["parent_id", "INT"],
      ["icon", "VARCHAR(500)"],
      ["banner", "VARCHAR(500)"],
      ["thumbnail", "VARCHAR(500)"],
      ["featured", "TINYINT DEFAULT 0"],
      ["sort_order", "INT DEFAULT 0"],
      ["seo_title", "VARCHAR(255)"],
      ["seo_description", "TEXT"],
      ["seo_keywords", "VARCHAR(500)"],
      ["status", "ENUM('active','inactive') DEFAULT 'active'"],
    ];
    for (const [column, definition] of categoryColumns) {
      await db.execute(
        sql`ALTER TABLE categories ADD COLUMN IF NOT EXISTS ${sql.raw(column)} ${sql.raw(definition)}`
      );
    }

    // ---------- colors ----------
    await db.execute(sql`
      CREATE TABLE IF NOT EXISTS colors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        hex VARCHAR(7) NOT NULL,
        status ENUM('active','inactive') DEFAULT 'active',
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // ---------- sizes ----------
    await db.execute(sql`
      CREATE TABLE IF NOT EXISTS sizes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        status ENUM('active','inactive') DEFAULT 'active',
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // ---------- collections ----------
    await db.execute(sql`
      CREATE TABLE IF NOT EXISTS collections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        description TEXT,
        image VARCHAR(500),
        featured TINYINT DEFAULT 0,
        sort_order INT DEFAULT 0,
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // ---------- vendors ----------
    await db.execute(sql`
      CREATE TABLE IF NOT EXISTS vendors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        logo VARCHAR(500),
        description TEXT,
        contact VARCHAR(100),
        phone VARCHAR(30),
        email VARCHAR(255),
        address VARCHAR(500),
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // ---------- suppliers ----------
    await db.execute(sql`
      CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        contact VARCHAR(100),
        phone VARCHAR(30),
        email VARCHAR(255),
        address VARCHAR(500),
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // ---------- product variants ----------
    await db.execute(sql`
      CREATE TABLE IF NOT EXISTS product_variants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        name VARCHAR(500) NOT NULL,
        options JSON NOT NULL,
        price DECIMAL(10,2),
        discount_price DECIMAL(10,2),
        sku VARCHAR(100),
        barcode VARCHAR(100),
        stock INT DEFAULT 0,
        weight VARCHAR(50),
        dimensions VARCHAR(100),
        images JSON,
        thumbnail VARCHAR(500),
        status ENUM('active','inactive') DEFAULT 'active',
        shipping_cost DECIMAL(10,2),
        warranty VARCHAR(100),
        availability TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_product_variants_product (product_id)
      )
    `);

    // ---------- product specs ----------
    await db.execute(sql`
      CREATE TABLE IF NOT EXISTS product_specs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        label VARCHAR(255) NOT NULL,
        value TEXT NOT NULL,
        sort_order INT DEFAULT 0,
        INDEX idx_product_specs_product (product_id)
      )
    `);

    // ---------- product relations ----------
    await db.execute(sql`
      CREATE TABLE IF NOT EXISTS product_relations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        related_product_id INT NOT NULL,
        type ENUM('frequently_bought_together','cross_sell','up_sell','accessories','similar') NOT NULL,
        INDEX idx_product_relations_product (product_id)
      )
    `);

    console.log("✓ catalog migration complete: product columns, categories hierarchy, colors, sizes, collections, vendors, suppliers, variants, specs, relations");
  } catch (error) {
    console.error("✗ catalog migration failed", error);
    process.exitCode = 1;
  }
};

run();
