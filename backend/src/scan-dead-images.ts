import { db } from "./config/db";
import { products, categories, collections, brands } from "./config/schema";
import { sql } from "drizzle-orm";

const DEAD = ["photo-1511707175664-5f897ff02aa9", "photo-1583391733957-3750e2b408d8", "photo-1558317377-067fb923f30000"];

const run = async () => {
  for (const pattern of DEAD) {
    const like = `%${pattern}%`;
    const prods = await db
      .select({ id: products.id, title: products.title, images: products.images })
      .from(products)
      .where(sql`${products.images} LIKE ${like}`);
    if (prods.length) prods.forEach((p) => console.log(`PRODUCT ${p.id} ${p.title}: ${JSON.stringify((p.images || []).filter((i) => i.includes(pattern)))}`));

    const cats = await db
      .select({ id: categories.id, name: categories.name, image: categories.image, icon: categories.icon, banner: categories.banner, thumbnail: categories.thumbnail })
      .from(categories)
      .where(sql`${categories.image} LIKE ${like} OR ${categories.icon} LIKE ${like} OR ${categories.banner} LIKE ${like} OR ${categories.thumbnail} LIKE ${like}`);
    cats.forEach((c) => console.log(`CATEGORY ${c.id} ${c.name}: image=${c.image?.slice(0, 60)} icon=${c.icon?.slice(0, 60)}`));

    const colls = await db.select().from(collections).where(sql`${collections.image} LIKE ${like} OR ${collections.banner} LIKE ${like}`);
    colls.forEach((c) => console.log(`COLLECTION ${c.id} ${c.name}: image=${c.image?.slice(0, 60)} banner=${c.banner?.slice(0, 60)}`));

    const brs = await db.select().from(brands).where(sql`${brands.logo} LIKE ${like} OR ${brands.bannerImage} LIKE ${like}`);
    brs.forEach((b) => console.log(`BRAND ${b.id} ${b.name}: logo=${b.logo?.slice(0, 60)}`));
  }
  console.log("Scan done.");
};

run()
  .then(() => process.exit(0))
  .catch((err) => {
    console.error(err);
    process.exit(1);
  });
