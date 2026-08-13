const mysql = require("mysql2/promise");
const dotenv = require("dotenv");
const p = dotenv.config().parsed;
const u = new URL(p.DATABASE_URL);
u.port = "4000";
(async () => {
  const conn = await mysql.createConnection({ uri: u.toString(), ssl: { rejectUnauthorized: true } });
  const [r] = await conn.query("SELECT id, title, slug FROM products ORDER BY id DESC LIMIT 5");
  console.log(r);
  const [c] = await conn.query("SELECT COUNT(*) n FROM marketing_integrations");
  console.log("integrations:", c);
  await conn.end();
})();
