"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * One-off data fix: replace dead Unsplash image URLs (404s) in categories,
 * products (images + colorOptions) and product_variants with branded
 * Mama Bazar placeholders already uploaded to Cloudinary.
 * Idempotent: only replaces URLs that still contain a dead pattern.
 */
const db_1 = require("./config/db");
const schema_1 = require("./config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const TECH = "https://res.cloudinary.com/gkvhkoh7/image/upload/v1786452759/categories/7c3c55bedd96.png";
const SAREES = "https://res.cloudinary.com/gkvhkoh7/image/upload/v1786452761/categories/bb57f46280af.png";
const DEAD_PATTERNS = [
    { pattern: "photo-1511707175664-5f897ff02aa9", replacement: TECH },
    { pattern: "photo-1583391733957-3750e2b408d8", replacement: SAREES },
    { pattern: "photo-1558317377-067fb923f30000", replacement: TECH },
];
const fixUrl = (url) => {
    if (!url)
        return url;
    for (const d of DEAD_PATTERNS) {
        if (url.includes(d.pattern))
            return d.replacement;
    }
    return url;
};
const run = async () => {
    // 1. Categories (image, icon, banner, thumbnail)
    for (const d of DEAD_PATTERNS) {
        const rows = await db_1.db
            .select({
            id: schema_1.categories.id,
            name: schema_1.categories.name,
            image: schema_1.categories.image,
            icon: schema_1.categories.icon,
            banner: schema_1.categories.banner,
            thumbnail: schema_1.categories.thumbnail,
        })
            .from(schema_1.categories)
            .where((0, drizzle_orm_1.sql) `${schema_1.categories.image} LIKE ${`%${d.pattern}%`} OR ${schema_1.categories.icon} LIKE ${`%${d.pattern}%`} OR ${schema_1.categories.banner} LIKE ${`%${d.pattern}%`} OR ${schema_1.categories.thumbnail} LIKE ${`%${d.pattern}%`}`);
        for (const c of rows) {
            await db_1.db
                .update(schema_1.categories)
                .set({ image: fixUrl(c.image) ?? undefined, icon: fixUrl(c.icon) ?? undefined, banner: fixUrl(c.banner) ?? undefined, thumbnail: fixUrl(c.thumbnail) ?? undefined })
                .where((0, drizzle_orm_1.sql) `${schema_1.categories.id} = ${c.id}`);
            console.log(`category ${c.id} ${c.name}: fixed ${d.pattern.slice(8, 16)}`);
        }
    }
    // 2. Products (images + colorOptions)
    const prods = await db_1.db
        .select({ id: schema_1.products.id, title: schema_1.products.title, images: schema_1.products.images, colorOptions: schema_1.products.colorOptions })
        .from(schema_1.products);
    let prodFixed = 0;
    for (const p of prods) {
        const imgList = Array.isArray(p.images) ? p.images : [];
        const nextImgs = imgList.map((u) => fixUrl(u) ?? u);
        const opts = Array.isArray(p.colorOptions) ? p.colorOptions : [];
        const nextOpts = opts.map((o) => ({ ...o, image: fixUrl(o.image) ?? o.image }));
        const imgChanged = JSON.stringify(nextImgs) !== JSON.stringify(imgList);
        const optChanged = JSON.stringify(nextOpts) !== JSON.stringify(opts);
        if (imgChanged || optChanged) {
            await db_1.db.update(schema_1.products).set({ images: nextImgs, colorOptions: nextOpts }).where((0, drizzle_orm_1.sql) `${schema_1.products.id} = ${p.id}`);
            prodFixed += 1;
            console.log(`product ${p.id} ${p.title}: fixed (images=${imgChanged} colorOptions=${optChanged})`);
        }
    }
    console.log(`products fixed: ${prodFixed}`);
    // 3. Product variants (images + thumbnail)
    for (const d of DEAD_PATTERNS) {
        const rows = await db_1.db
            .select({ id: schema_1.productVariants.id, images: schema_1.productVariants.images, thumbnail: schema_1.productVariants.thumbnail })
            .from(schema_1.productVariants)
            .where((0, drizzle_orm_1.sql) `${schema_1.productVariants.images} LIKE ${`%${d.pattern}%`} OR ${schema_1.productVariants.thumbnail} LIKE ${`%${d.pattern}%`}`);
        for (const v of rows) {
            const imgList = Array.isArray(v.images) ? v.images : [];
            await db_1.db
                .update(schema_1.productVariants)
                .set({ images: imgList.map((u) => fixUrl(u) ?? u), thumbnail: fixUrl(v.thumbnail) ?? undefined })
                .where((0, drizzle_orm_1.sql) `${schema_1.productVariants.id} = ${v.id}`);
            console.log(`variant ${v.id}: fixed ${d.pattern.slice(8, 16)}`);
        }
    }
    console.log("Done.");
};
run()
    .then(() => process.exit(0))
    .catch((err) => {
    console.error(err);
    process.exit(1);
});
//# sourceMappingURL=migrate-fix-images.js.map