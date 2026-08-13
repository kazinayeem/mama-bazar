const mysql = require("mysql2/promise");
const dotenv = require("dotenv");
const p = dotenv.config().parsed;
const u = new URL(p.DATABASE_URL);
u.port = "4000";
(async () => {
  const conn = await mysql.createConnection({ uri: u.toString(), ssl: { rejectUnauthorized: true } });
  const [r] = await conn.query("SELECT id, title, category_id, sub_category_id, child_category_id, brand_id, collection_id, vendor_id, supplier_id, price, stock, product_status, status FROM products WHERE title LIKE 'E2E Submission%' ORDER BY id DESC LIMIT 1");
  console.log(JSON.stringify(r[0], null, 1));
  await conn.end();
})();
