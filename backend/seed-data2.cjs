const mysql = require("mysql2/promise");
const dotenv = require("dotenv");
const p = dotenv.config().parsed;
const u = new URL(p.DATABASE_URL);
u.port = "4000";
(async () => {
  const conn = await mysql.createConnection({ uri: u.toString(), ssl: { rejectUnauthorized: true } });
  const run = async (t, sql, args) => { try { const [r] = await conn.query(sql, args); console.log(t, "->", r.insertId ?? "ok"); } catch (e) { console.log(t, "ERR", e.message) } };
  await run("color", "INSERT INTO colors (name, display_name, hex, status) VALUES (?,?,?,?)", ["Black", "Black", "#000000", "active"]);
  await run("size", "INSERT INTO sizes (name, type, status) VALUES (?,?,?)", ["Medium", "general", "active"]);
  await run("pm", "INSERT INTO payment_methods (code, name, type, enabled) VALUES (?,?,?,?)", ["cod", "Cash on Delivery", "cod", 1]);
  await run("expense", "INSERT INTO expenses (title, category_id, amount, payment_method, expense_date, status) VALUES (?,?,?,?,?,?)", ["Electricity bill", 1, 500, "cash", new Date(), "approved"]);
  const [pri] = await conn.query("SELECT id FROM policy_pages LIMIT 1");
  console.log("policy ids:", JSON.stringify(pri));
  await run("user-member", "INSERT INTO users (name, phone, password, role, status) VALUES (?,?,?,?,?)", ["Member One", "01720000001", "x", "member", "active"]);
  await conn.end();
})();
