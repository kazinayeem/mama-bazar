import { db } from "./src/config/db";
import { products, categories, collections, brands, banners, mediaAssets } from "./src/config/schema";
import { sql } from "drizzle-orm";

const uniq = (arr: string[]) => [...new Set(arr)];

const show = (label: string, rows: { url?: string | null }[]) => {
  const urls = uniq(rows.map((r) => r.url || "").filter(Boolean));
  console.log(`\n=== ${label} (${urls.length} unique) ===`);
  urls.slice(0, 15).forEach((u) => console.log("  ", u));
};

const run = async () => {
  const prods = await db.select({ url: products.images }).from(products).where(sql`${products.images} IS NOT NULL`);
  const flat = prods.flatMap((p) => (p.url || []) as string[]);
  const clean = uniq(flat.filter((u) => u.includes("cloudinary")));
  console.log(`\n=== PRODUCT cloudinary images (${clean.length} unique) ===`);
  clean.slice(0, 25).forEach((u) => console.log("  ", u));

  const cats = await db.select({ url: categories.image }).from(categories);
  show("CATEGORY image", cats);

  const colls = await db.select({ url: collections.image }).from(collections);
  show("COLLECTION image", colls);

  const brs = await db.select({ url: brands.logo }).from(brands);
  show("BRAND logo", brs);

  const slides = await db.select({ url: banners.image }).from(banners);
  show("BANNER image (hero)", slides);

  const bans = await db.select().from(banners);
  const bUrls = uniq(
    bans.flatMap((b) => [b.image, b.imageMobile, b.imageTablet]).filter((u): u is string => !!u && u.includes("cloudinary")),
  );
  console.log(`\n=== BANNER cloudinary images (${bUrls.length} unique) ===`);
  bUrls.slice(0, 25).forEach((u) => console.log("  ", u));

  const med = await db.select({ url: mediaAssets.url, provider: mediaAssets.provider, publicId: mediaAssets.publicId }).from(mediaAssets);
  const medC = med.filter((m) => m.url.includes("cloudinary") || m.provider === "cloudinary");
  console.log(`\n=== MEDIA ASSETS cloudinary (${medC.length} of ${med.length}) ===`);
  medC.slice(0, 25).forEach((m) => console.log("  ", m.url, "| provider:", m.provider, "| publicId:", m.publicId));

  process.exit(0);
};

run().catch((err) => {
  console.error(err);
  process.exit(1);
});
