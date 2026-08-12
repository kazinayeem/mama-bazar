"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const db_1 = require("./config/db");
const schema_1 = require("./config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const DEAD = ["photo-1511707175664-5f897ff02aa9", "photo-1583391733957-3750e2b408d8", "photo-1558317377-067fb923f30000"];
const run = async () => {
    for (const pattern of DEAD) {
        const like = `%${pattern}%`;
        const prods = await db_1.db
            .select({ id: schema_1.products.id, title: schema_1.products.title, images: schema_1.products.images })
            .from(schema_1.products)
            .where((0, drizzle_orm_1.sql) `${schema_1.products.images} LIKE ${like}`);
        if (prods.length)
            prods.forEach((p) => console.log(`PRODUCT ${p.id} ${p.title}: ${JSON.stringify((p.images || []).filter((i) => i.includes(pattern)))}`));
        const cats = await db_1.db
            .select({ id: schema_1.categories.id, name: schema_1.categories.name, image: schema_1.categories.image, icon: schema_1.categories.icon, banner: schema_1.categories.banner, thumbnail: schema_1.categories.thumbnail })
            .from(schema_1.categories)
            .where((0, drizzle_orm_1.sql) `${schema_1.categories.image} LIKE ${like} OR ${schema_1.categories.icon} LIKE ${like} OR ${schema_1.categories.banner} LIKE ${like} OR ${schema_1.categories.thumbnail} LIKE ${like}`);
        cats.forEach((c) => console.log(`CATEGORY ${c.id} ${c.name}: image=${c.image?.slice(0, 60)} icon=${c.icon?.slice(0, 60)}`));
        const colls = await db_1.db.select().from(schema_1.collections).where((0, drizzle_orm_1.sql) `${schema_1.collections.image} LIKE ${like} OR ${schema_1.collections.banner} LIKE ${like}`);
        colls.forEach((c) => console.log(`COLLECTION ${c.id} ${c.name}: image=${c.image?.slice(0, 60)} banner=${c.banner?.slice(0, 60)}`));
        const brs = await db_1.db.select().from(schema_1.brands).where((0, drizzle_orm_1.sql) `${schema_1.brands.logo} LIKE ${like} OR ${schema_1.brands.bannerImage} LIKE ${like}`);
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
//# sourceMappingURL=scan-dead-images.js.map