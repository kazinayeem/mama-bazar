const mysql = require("mysql2/promise");
const dotenv = require("dotenv");
const p = dotenv.config().parsed;
const u = new URL(p.DATABASE_URL);
u.port = "4000";
(async () => {
  const conn = await mysql.createConnection({ uri: u.toString(), ssl: { rejectUnauthorized: true } });
  const [c] = await conn.query("SELECT id, name, parent_id, status FROM categories");
  console.log(JSON.stringify(c));
  const [b] = await conn.query("SELECT id, name FROM brands"); console.log("brands:", JSON.stringify(b));
  const [co] = await conn.query("SELECT id, name FROM collections"); console.log("collections:", JSON.stringify(co));
  await conn.end();
})();
