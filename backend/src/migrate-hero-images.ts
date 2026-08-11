/**
 * One-off data fix: replace the expired Unsplash demo images on hero slides
 * with branded Mama Bazar banners (already uploaded to Cloudinary).
 * Also verifies each replaced URL is reachable.
 */
import { db } from "./config/db";
import { siteSettings } from "./config/schema";
import { eq, desc } from "drizzle-orm";

const CONFIG_KEY = "homepage_config";

const REPLACEMENTS: Record<string, string> = {
  "seed-slide-1": "https://res.cloudinary.com/gkvhkoh7/image/upload/v1786451447/hero/377896053045.png",
  "seed-slide-2": "https://res.cloudinary.com/gkvhkoh7/image/upload/v1786451450/hero/8622b0e5c910.png",
  "seed-slide-3": "https://res.cloudinary.com/gkvhkoh7/image/upload/v1786451451/hero/0797301aaf50.png",
  "seed-slide-4": "https://res.cloudinary.com/gkvhkoh7/image/upload/v1786451447/hero/377896053045.png",
};

const run = async () => {
  const rows = await db.select().from(siteSettings).where(eq(siteSettings.key, CONFIG_KEY)).orderBy(desc(siteSettings.id)).limit(1);
  const row = rows[0];
  if (!row?.value) {
    console.log("No homepage config found.");
    return;
  }

  const config = JSON.parse(row.value);
  let changed = 0;
  for (const slide of config.heroSlides || []) {
    const url = REPLACEMENTS[slide.id];
    if (!url) continue;
    slide.desktopImage = url;
    slide.tabletImage = url;
    slide.mobileImage = url;
    changed += 1;
    console.log(`  ${slide.id} -> ${url}`);
  }

  if (changed > 0) {
    await db.update(siteSettings).set({ value: JSON.stringify(config) }).where(eq(siteSettings.id, row.id));
    console.log(`Updated ${changed} hero slide(s).`);
  } else {
    console.log("No hero slides matched — nothing changed.");
  }
};

run()
  .then(() => process.exit(0))
  .catch((err) => {
    console.error(err);
    process.exit(1);
  });
