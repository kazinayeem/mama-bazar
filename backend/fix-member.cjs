const mysql = require("mysql2/promise");
const dotenv = require("dotenv");
const p = dotenv.config().parsed;
const u = new URL(p.DATABASE_URL);
u.port = "4000";
(async () => {
  const conn = await mysql.createConnection({ uri: u.toString(), ssl: { rejectUnauthorized: true } });
  const [r] = await conn.query("INSERT INTO users (name, phone, password, role, status) VALUES (?,?,?,?,?)", ["Member One", "01720000001", "x", "user", "active"]);
  console.log("member ->", r.insertId);
  await conn.end();
})();
