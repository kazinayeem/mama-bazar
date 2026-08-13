const mysql = require("mysql2/promise");
const dotenv = require("dotenv");
const p = dotenv.config().parsed;
const u = new URL(p.DATABASE_URL);
u.port = "4000";
(async () => {
  const conn = await mysql.createConnection({ uri: u.toString(), ssl: { rejectUnauthorized: true } });
  const [s] = await conn.query("SELECT * FROM site_settings WHERE \`key\` = 'homepage_config'");
  if (!s[0]) { console.log("no homepage_config row"); await conn.end(); return }
  const cfg = JSON.parse(s[0].value);
  cfg.heroSlides = [
    { id: "s1", desktopImage: "/assets/hero.png", title: "Hero One", badge: "Sale", alignment: "left", status: "active", priority: 1 },
    { id: "s2", desktopImage: "/assets/hero.png", title: "Hero Two", alignment: "right", status: "active", priority: 2 },
  ];
  const cat = cfg.sections.find(x => x.type === "categories");
  if (cat) { cat.columns = 2; cat.limit = 8; }
  cfg.content = cfg.content || {};
  cfg.content.whyChooseUs = [{ icon: "Zap", title: "Fast", text: "x" }, { icon: "Shield", title: "Safe", text: "y" }];
  cfg.content.newsletter = cfg.content.newsletter || {};
  cfg.content.features = cfg.content.features || [];
  await conn.query("UPDATE site_settings SET value = ? WHERE \`key\` = 'homepage_config'", [JSON.stringify(cfg)]);
  console.log("homepage_config seeded: heroSlides=" + cfg.heroSlides.length + ", sections=" + cfg.sections.length);
  await conn.end();
})();
