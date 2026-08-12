import { db } from "./src/config/db";
import { siteSettings } from "./src/config/schema";

const run = async () => {
  const rows = await db.select().from(siteSettings);
  for (const r of rows) {
    console.log("KEY:", r.key);
    console.log(JSON.stringify(r.value, null, 1)?.slice(0, 4000));
    console.log("----");
  }
  process.exit(0);
};
run().catch((e) => { console.error(e); process.exit(1); });
