"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const dotenv_1 = __importDefault(require("dotenv"));
dotenv_1.default.config();
const db_1 = require("./config/db");
const bcryptjs_1 = __importDefault(require("bcryptjs"));
const createTablesSQL = `
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  image VARCHAR(500),
  description TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  discount DECIMAL(10,2) DEFAULT 0,
  category_id INT,
  stock INT NOT NULL DEFAULT 0,
  images JSON,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_name VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  address TEXT NOT NULL,
  shipping_area VARCHAR(100) NOT NULL,
  shipping_cost DECIMAL(10,2) NOT NULL,
  coupon_code VARCHAR(50),
  discount DECIMAL(10,2) DEFAULT 0,
  order_note TEXT,
  total_price DECIMAL(10,2) NOT NULL,
  payment_method ENUM('cod','online') NOT NULL DEFAULT 'cod',
  status ENUM('pending','confirmed','processing','packed','shipped','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE IF NOT EXISTS coupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  discount_type ENUM('percentage','fixed') NOT NULL,
  discount_value DECIMAL(10,2) NOT NULL,
  min_order_amount DECIMAL(10,2) DEFAULT 0,
  expiry_date TIMESTAMP NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','manager') NOT NULL DEFAULT 'admin',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS site_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  \`key\` VARCHAR(100) NOT NULL UNIQUE,
  value TEXT
);
`;
async function setup() {
    const conn = await db_1.pool.getConnection();
    try {
        console.log("📦 Creating tables...");
        const statements = createTablesSQL
            .split(";")
            .map((s) => s.trim())
            .filter((s) => s.length > 0);
        for (const stmt of statements) {
            await conn.execute(stmt);
        }
        console.log(" All tables created!");
        // Seed admin user
        console.log("🌱 Seeding admin user...");
        const hashedPassword = await bcryptjs_1.default.hash("admin123", 12);
        await conn.execute(`INSERT IGNORE INTO users (name, email, password, role) VALUES (?, ?, ?, ?)`, ["Admin", "admin@ghorerbazar.com", hashedPassword, "admin"]);
        console.log(" Admin user ready: admin@ghorerbazar.com / admin123");
        // Seed default notice
        console.log("🌱 Seeding default notice...");
        await conn.execute(`INSERT IGNORE INTO site_settings (\`key\`, value) VALUES (?, ?)`, ["notice_active", "true"]);
        await conn.execute(`INSERT IGNORE INTO site_settings (\`key\`, value) VALUES (?, ?)`, ["notice_text", "আমাদের যে কোন পণ্য অর্ডার করতে কল করুন অথবা WhatsApp করুন: 01XXXXXXXXX | সারা বাংলাদেশে হোম ডেলিভারি 🚚"]);
        console.log(" Default notice ready!");
    }
    catch (err) {
        console.error(" Setup error:", err);
    }
    finally {
        conn.release();
        await db_1.pool.end();
        process.exit(0);
    }
}
setup();
//# sourceMappingURL=setup.js.map