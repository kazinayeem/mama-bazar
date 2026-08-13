const mysql = require("mysql2/promise");
const dotenv = require("dotenv");
const p = dotenv.config().parsed;
const u = new URL(p.DATABASE_URL);
u.port = "4000";
(async () => {
  const conn = await mysql.createConnection({ uri: u.toString(), ssl: { rejectUnauthorized: true } });
  const run = async (t, sql, args) => { try { const [r] = await conn.query(sql, args); console.log(t, "->", r.insertId ?? "ok"); } catch (e) { console.log(t, "ERR", e.message) } };
  await run("cat-root", "INSERT INTO categories (name, slug, status, sort_order) VALUES (?,?,?,?)", ["Electronics", "electronics", "active", 1]);
  await run("cat-sub", "INSERT INTO categories (name, slug, status, parent_id, sort_order) VALUES (?,?,?,?,?)", ["Mobile & Tablets", "mobile-tablets", "active", 1, 1]);
  await run("cat-child", "INSERT INTO categories (name, slug, status, parent_id, sort_order) VALUES (?,?,?,?,?)", ["Smartphones", "smartphones", "active", 2, 1]);
  await run("brand", "INSERT INTO brands (name, slug, status) VALUES (?,?,?)", ["Samsung", "samsung", "active"]);
  await run("collection", "INSERT INTO collections (name, slug, status) VALUES (?,?,?)", ["Summer Sale", "summer-sale", "active"]);
  await run("vendor", "INSERT INTO vendors (name, slug, status) VALUES (?,?,?)", ["Tech Mart", "tech-mart", "active"]);
  await run("supplier", "INSERT INTO suppliers (name, slug, status) VALUES (?,?,?)", ["Global Traders", "global-traders", "active"]);
  await run("color", "INSERT INTO colors (name, slug, status) VALUES (?,?,?)", ["Black", "black", "active"]);
  await run("size", "INSERT INTO sizes (name, slug, status) VALUES (?,?,?)", ["Medium", "medium", "active"]);
  await run("product", "INSERT INTO products (title, slug, price, stock, product_status, category_id, sub_category_id, child_category_id, brand_id, collection_id, vendor_id, supplier_id, status, description, short_description, sku) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
    ["Samsung Galaxy Test", "samsung-galaxy-test", 25000, 10, "draft", 1, 2, 3, 1, 1, 1, 1, "active", "desc", "short", "SG-TEST-1"]);
  await run("coupon", "INSERT INTO coupons (code, discount_type, discount_value, status) VALUES (?,?,?,?)", ["TEST10", "percentage", 10, "active"]);
  await run("pm", "INSERT INTO payment_methods (name, type, status) VALUES (?,?,?)", ["Cash on Delivery", "cod", "active"]);
  await run("expcat", "INSERT INTO expense_categories (name, status) VALUES (?,?)", ["Utilities", "active"]);
  await run("expense", "INSERT INTO expenses (title, category_id, amount, payment_method, expense_date, status) VALUES (?,?,?,?,?,?)", ["Electricity bill", 1, 500, "cash", new Date(), "active"]);
  await run("ship", "INSERT INTO shipping_methods (name, charge, status) VALUES (?,?,?)", ["Standard", 80, "active"]);
  await run("banner", "INSERT INTO banners (title, image, position, status) VALUES (?,?,?,?)", ["Hero Banner", "/assets/hero.png", "hero", "active"]);
  await run("policy", "INSERT INTO policy_pages (slug, title, status, content) VALUES (?,?,?,?)", ["privacy-policy", "Privacy Policy", "published", "<p>x</p>"]);
  await conn.end();
})();
