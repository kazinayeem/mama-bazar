/**
 * One-off data fix: replace dead Unsplash image URLs (404s) in categories,
 * products (images + colorOptions) and product_variants with branded
 * Mama Bazar placeholders already uploaded to Cloudinary.
 * Idempotent: only replaces URLs that still contain a dead pattern.
 */
import { db } from "./config/db";
import { categories, products, productVariants } from "./config/schema";
import { sql } from "drizzle-orm";

const TECH = "https://res.cloudinary.com/gkvhkoh7/image/upload/v1786452759/categories/7c3c55bedd96.png";
const SAREES = "https://res.cloudinary.com/gkvhkoh7/image/upload/v1786452761/categories/bb57f46280af.png";

const DEAD_PATTERNS: Array<{ pattern: string; replacement: string }> = [
  { pattern: "photo-1511707175664-5f897ff02aa9", replacement: TECH },
  { pattern: "photo-1583391733957-3750e2b408d8", replacement: SAREES },
  { pattern: "photo-1558317377-067fb923f30000", replacement: TECH },
];

const fixUrl = (url?: string | null): string | undefined | null => {
  if (!url) return url;
  for (const d of DEAD_PATTERNS) {
    if (url.includes(d.pattern)) return d.replacement;
  }
  return url;
};

const run = async () => {
  // 1. Categories (image, icon, banner, thumbnail)
  for (const d of DEAD_PATTERNS) {
    const rows = await db
      .select({
        id: categories.id,
        name: categories.name,
        image: categories.image,
        icon: categories.icon,
        banner: categories.banner,
        thumbnail: categories.thumbnail,
      })
      .from(categories)
      .where(sql`${categories.image} LIKE ${`%${d.pattern}%`} OR ${categories.icon} LIKE ${`%${d.pattern}%`} OR ${categories.banner} LIKE ${`%${d.pattern}%`} OR ${categories.thumbnail} LIKE ${`%${d.pattern}%`}`);
    for (const c of rows) {
      await db
        .update(categories)
        .set({ image: fixUrl(c.image) ?? undefined, icon: fixUrl(c.icon) ?? undefined, banner: fixUrl(c.banner) ?? undefined, thumbnail: fixUrl(c.thumbnail) ?? undefined })
        .where(sql`${categories.id} = ${c.id}`);
      console.log(`category ${c.id} ${c.name}: fixed ${d.pattern.slice(8, 16)}`);
    }
  }

  // 2. Products (images + colorOptions)
  const prods = await db
    .select({ id: products.id, title: products.title, images: products.images, colorOptions: products.colorOptions })
    .from(products);
  let prodFixed = 0;
  for (const p of prods) {
    const imgList: string[] = Array.isArray(p.images) ? p.images : [];
    const nextImgs = imgList.map((u) => fixUrl(u) ?? u);
    const opts = Array.isArray(p.colorOptions) ? p.colorOptions : [];
    const nextOpts = opts.map((o) => ({ ...o, image: fixUrl(o.image) ?? o.image }));
    const imgChanged = JSON.stringify(nextImgs) !== JSON.stringify(imgList);
    const optChanged = JSON.stringify(nextOpts) !== JSON.stringify(opts);
    if (imgChanged || optChanged) {
      await db.update(products).set({ images: nextImgs, colorOptions: nextOpts }).where(sql`${products.id} = ${p.id}`);
      prodFixed += 1;
      console.log(`product ${p.id} ${p.title}: fixed (images=${imgChanged} colorOptions=${optChanged})`);
    }
  }
  console.log(`products fixed: ${prodFixed}`);

  // 3. Product variants (images + thumbnail)
  for (const d of DEAD_PATTERNS) {
    const rows = await db
      .select({ id: productVariants.id, images: productVariants.images, thumbnail: productVariants.thumbnail })
      .from(productVariants)
      .where(sql`${productVariants.images} LIKE ${`%${d.pattern}%`} OR ${productVariants.thumbnail} LIKE ${`%${d.pattern}%`}`);
    for (const v of rows) {
      const imgList: string[] = Array.isArray(v.images) ? v.images : [];
      await db
        .update(productVariants)
        .set({ images: imgList.map((u) => fixUrl(u) ?? u), thumbnail: fixUrl(v.thumbnail) ?? undefined })
        .where(sql`${productVariants.id} = ${v.id}`);
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
