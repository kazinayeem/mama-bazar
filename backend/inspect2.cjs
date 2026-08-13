const mysql = require("mysql2/promise");
const dotenv = require("dotenv");
const p = dotenv.config().parsed;
const u = new URL(p.DATABASE_URL);
u.port = "4000";
(async () => {
  const conn = await mysql.createConnection({ uri: u.toString(), ssl: { rejectUnauthorized: true } });
  const q = async (sql) => { const [r] = await conn.query(sql); console.log(sql.split(" ").slice(0,2).join(" ") + ":", JSON.stringify(r.slice(0,8))); return r };
  await q("SELECT id, name, parent_id FROM categories LIMIT 10");
  await q("SELECT id, name FROM brands LIMIT 3");
  await q("SELECT id, title, category_id, sub_category_id, child_category_id, collection_id, brand_id, vendor_id, supplier_id, product_status FROM products LIMIT 5");
  const [co] = await conn.query("SELECT COUNT(*) c FROM coupons"); console.log("coupons:", co[0].c);
  const [pm] = await conn.query("SELECT id, name, type FROM payment_methods"); console.log("payment_methods:", JSON.stringify(pm));
  const [ex] = await conn.query("SELECT id, name FROM expense_categories LIMIT 5"); console.log("expense_categories:", JSON.stringify(ex));
  const [o] = await conn.query("SELECT id, status FROM orders LIMIT 5"); console.log("orders:", JSON.stringify(o));
  await conn.end();
})();
