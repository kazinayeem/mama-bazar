"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const drizzle_orm_1 = require("drizzle-orm");
const db_1 = require("./config/db");
const run = async () => {
    try {
        // ---------- products: enterprise columns ----------
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS short_description TEXT`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS cost_price DECIMAL(10,2) DEFAULT 0`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS tax DECIMAL(10,2) DEFAULT 0`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS shipping_charge DECIMAL(10,2) DEFAULT 0`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS brand_id INT`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS brand VARCHAR(100)`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS sku VARCHAR(100)`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS barcode VARCHAR(100)`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS tags JSON`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS warranty VARCHAR(100)`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS weight VARCHAR(50)`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS dimensions VARCHAR(100)`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS features JSON`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS return_policy TEXT`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS supplier VARCHAR(255)`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS warehouse VARCHAR(255)`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS video_url VARCHAR(500)`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS seo_title VARCHAR(255)`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS seo_description TEXT`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS seo_keywords VARCHAR(500)`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS emi_available TINYINT DEFAULT 0`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS is_featured TINYINT DEFAULT 0`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS is_trending TINYINT DEFAULT 0`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS is_flash_sale TINYINT DEFAULT 0`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS is_new_arrival TINYINT DEFAULT 0`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS is_best_seller TINYINT DEFAULT 0`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS is_archived TINYINT DEFAULT 0`);
        await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE products ADD COLUMN IF NOT EXISTS meta JSON`);
        // ---------- brands table ----------
        await db_1.db.execute((0, drizzle_orm_1.sql) `
      CREATE TABLE IF NOT EXISTS brands (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        logo VARCHAR(500),
        banner_image VARCHAR(500),
        description TEXT,
        website VARCHAR(500),
        featured TINYINT DEFAULT 0,
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
        // ---------- banners table ----------
        await db_1.db.execute((0, drizzle_orm_1.sql) `
      CREATE TABLE IF NOT EXISTS banners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255),
        subtitle VARCHAR(255),
        image VARCHAR(500) NOT NULL,
        image_mobile VARCHAR(500),
        image_tablet VARCHAR(500),
        link VARCHAR(500),
        position ENUM('hero','banner','promo','sidebar') DEFAULT 'hero',
        button_text VARCHAR(100),
        priority INT DEFAULT 0,
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      )
    `);
        // ---------- media assets table ----------
        await db_1.db.execute((0, drizzle_orm_1.sql) `
      CREATE TABLE IF NOT EXISTS media_assets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        url VARCHAR(1000) NOT NULL,
        public_id VARCHAR(500),
        filename VARCHAR(500) NOT NULL,
        mime_type VARCHAR(100) NOT NULL,
        size INT DEFAULT 0,
        width INT,
        height INT,
        provider ENUM('cloudinary','local') DEFAULT 'local',
        folder VARCHAR(200) DEFAULT 'general',
        alt VARCHAR(255),
        uploader_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
        console.log("✓ enterprise migration complete: products fields, brands, banners, media_assets");
    }
    catch (error) {
        console.error("✗ enterprise migration failed", error);
        process.exitCode = 1;
    }
};
run();
//# sourceMappingURL=migrate-enterprise.js.map