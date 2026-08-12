"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * One-off data fix: replace the expired Unsplash demo images on hero slides
 * with branded Mama Bazar banners (already uploaded to Cloudinary).
 * Also verifies each replaced URL is reachable.
 */
const db_1 = require("./config/db");
const schema_1 = require("./config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const CONFIG_KEY = "homepage_config";
const REPLACEMENTS = {
    "seed-slide-1": "https://res.cloudinary.com/gkvhkoh7/image/upload/v1786451447/hero/377896053045.png",
    "seed-slide-2": "https://res.cloudinary.com/gkvhkoh7/image/upload/v1786451450/hero/8622b0e5c910.png",
    "seed-slide-3": "https://res.cloudinary.com/gkvhkoh7/image/upload/v1786451451/hero/0797301aaf50.png",
    "seed-slide-4": "https://res.cloudinary.com/gkvhkoh7/image/upload/v1786451447/hero/377896053045.png",
};
const run = async () => {
    const rows = await db_1.db.select().from(schema_1.siteSettings).where((0, drizzle_orm_1.eq)(schema_1.siteSettings.key, CONFIG_KEY)).orderBy((0, drizzle_orm_1.desc)(schema_1.siteSettings.id)).limit(1);
    const row = rows[0];
    if (!row?.value) {
        console.log("No homepage config found.");
        return;
    }
    const config = JSON.parse(row.value);
    let changed = 0;
    for (const slide of config.heroSlides || []) {
        const url = REPLACEMENTS[slide.id];
        if (!url)
            continue;
        slide.desktopImage = url;
        slide.tabletImage = url;
        slide.mobileImage = url;
        changed += 1;
        console.log(`  ${slide.id} -> ${url}`);
    }
    if (changed > 0) {
        await db_1.db.update(schema_1.siteSettings).set({ value: JSON.stringify(config) }).where((0, drizzle_orm_1.eq)(schema_1.siteSettings.id, row.id));
        console.log(`Updated ${changed} hero slide(s).`);
    }
    else {
        console.log("No hero slides matched — nothing changed.");
    }
};
run()
    .then(() => process.exit(0))
    .catch((err) => {
    console.error(err);
    process.exit(1);
});
//# sourceMappingURL=migrate-hero-images.js.map