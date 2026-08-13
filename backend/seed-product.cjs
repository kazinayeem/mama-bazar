const mysql = require("mysql2/promise");
const dotenv = require("dotenv");
const p = dotenv.config().parsed;
const u = new URL(p.DATABASE_URL);
u.port = "4000";
(async () => {
  const conn = await mysql.createConnection({ uri: u.toString(), ssl: { rejectUnauthorized: true } });
  await conn.query(`INSERT INTO products (title, slug, description, price, category_id, sub_category_id, brand_id, collection_id, vendor_id, supplier_id, brand, stock, low_stock_alert, min_order, stock_status, product_status, images, status)
    VALUES (?, ?, ?, 9999, 1, 2, 1, 1, 1, 1, 'Samsung', 5, 5, 1, 'in_stock', 'published', ?, 'active') ON DUPLICATE KEY UPDATE title = VALUES(title)`,
    ["Samsung Galaxy Test", "samsung-galaxy-test", "Test product for Meta Pixel verification", JSON.stringify(["https://picsum.photos/600/600"])])
  const [r] = await conn.query("SELECT id, title, slug FROM products WHERE slug = 'samsung-galaxy-test'")
  console.log("product:", r[0])
  const [c] = await conn.query("SELECT id, name, type, pixel_id, status FROM marketing_integrations")
  console.log("integration:", c)
  await conn.end();
})();
