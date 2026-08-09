import { eq } from "drizzle-orm";
import { db } from "../src/config/db";
import * as schema from "../src/config/schema";
import { policyPageSeeds } from "./seed-pages";

async function main() {
  if (process.env.NODE_ENV === "production") {
    throw new Error("Seeding is disabled in production");
  }

  const admin = await db.select({ id: schema.users.id }).from(schema.users).where(eq(schema.users.role, "SUPER_ADMIN")).limit(1);
  const adminId = admin[0]?.id ?? null;

  const now = Math.floor(Date.now() / 1000);
  let inserted = 0;
  let existing = 0;

  for (const page of policyPageSeeds) {
    const found = await db
      .select({ id: schema.policyPages.id })
      .from(schema.policyPages)
      .where(eq(schema.policyPages.slug, page.slug))
      .limit(1);
    if (found[0]) {
      existing++;
      continue;
    }
    await db.insert(schema.policyPages).values({
      slug: page.slug,
      title: page.title,
      content: page.content,
      status: "published",
      lastUpdated: now,
      updatedBy: adminId,
    });
    inserted++;
  }

  const contactSetting = await db
    .select({ id: schema.siteSettings.id })
    .from(schema.siteSettings)
    .where(eq(schema.siteSettings.key, "contact_info"))
    .limit(1);
  if (!contactSetting[0]) {
    await db.insert(schema.siteSettings).values({
      key: "contact_info",
      value: JSON.stringify({ phone: "", email: "", address: "", supportHours: "", hotline: "", whatsapp: "" }),
    });
  }

  console.log(`policy_pages: ${inserted} inserted, ${existing} already present`);
  console.log("DONE");
  process.exit(0);
}

main().catch((err) => {
  console.error("SEED-PAGES:", err instanceof Error ? err.message : err);
  process.exit(1);
});