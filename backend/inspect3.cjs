const mysql = require("mysql2/promise");
const dotenv = require("dotenv");
const p = dotenv.config().parsed;
const u = new URL(p.DATABASE_URL);
u.port = "4000";
(async () => {
  const conn = await mysql.createConnection({ uri: u.toString(), ssl: { rejectUnauthorized: true } });
  const cols = async (t) => { const [c] = await conn.query(`SHOW COLUMNS FROM \`${t}\``); console.log(t + ": " + c.map(x => x.Field).join(", ")) };
  await cols("banners");
  await cols("shipping_methods");
  await cols("expenses");
  await conn.end();
})();
