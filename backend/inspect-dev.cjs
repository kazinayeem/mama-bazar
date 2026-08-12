const mysql = require("mysql2/promise");
const dotenv = require("dotenv");
const p = dotenv.config().parsed;
const u = new URL(p.DATABASE_URL);
u.port = "4000";
(async () => {
  const conn = await mysql.createConnection({ uri: u.toString(), ssl: { rejectUnauthorized: true } });
  const [tables] = await conn.query("SHOW TABLES");
  console.log("TABLES: " + tables.map(t => Object.values(t)[0]).join(", "));
  for (const t of ["categories","products","brands","collections","vendors","suppliers"]) {
    try {
      const [cols] = await conn.query(`SHOW COLUMNS FROM \`${t}\``);
      console.log(`\n${t}: ` + cols.map(c => c.Field).join(", "));
    } catch (e) { console.log(`\n${t}: MISSING (${e.code})`); }
  }
  await conn.end();
})();
