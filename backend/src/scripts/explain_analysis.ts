import { db } from "../config/db";
import { sql } from "drizzle-orm";

async function explainQuery(title: string, queryStr: string) {
  console.log(`\n========================================================`);
  console.log(`QUERY: ${title}`);
  console.log(`SQL: ${queryStr}`);
  console.log(`========================================================`);

  try {
    const explainRes = await db.execute(sql.raw(`EXPLAIN ${queryStr}`));
    const rows = Array.isArray(explainRes[0]) ? explainRes[0] : explainRes;
    console.log("EXPLAIN Result:");
    console.table(rows);
  } catch (err: any) {
    console.error(`EXPLAIN error: ${err.message}`);
  }

  try {
    const analyzeRes = await db.execute(sql.raw(`EXPLAIN ANALYZE ${queryStr}`));
    const analyzeRows = Array.isArray(analyzeRes[0]) ? analyzeRes[0] : analyzeRes;
    console.log("EXPLAIN ANALYZE Result:");
    console.log(JSON.stringify(analyzeRows, null, 2));
  } catch (err: any) {
    console.log(`(EXPLAIN ANALYZE not supported or errored: ${err.message})`);
  }
}

async function showIndexes(tableName: string) {
  console.log(`\nINDEXES ON ${tableName}:`);
  const res = await db.execute(sql.raw(`SHOW INDEX FROM \`${tableName}\``));
  const rows = Array.isArray(res[0]) ? res[0] : res;
  console.table(rows);
}

async function main() {
  await showIndexes("products");
  await showIndexes("reviews");

  // 1. price_asc
  await explainQuery(
    "Products ORDER BY price ASC",
    "SELECT p.id, p.title, p.price FROM products p WHERE p.status = 'active' ORDER BY p.price ASC LIMIT 12"
  );

  // 1b. price_asc with all joins
  await explainQuery(
    "Products fullQuery ORDER BY price ASC",
    `SELECT products.id, products.title, products.price, categories.name AS categoryName 
     FROM products 
     LEFT JOIN categories ON products.category_id = categories.id 
     LEFT JOIN categories AS sc ON products.sub_category_id = sc.id 
     LEFT JOIN categories AS cc ON products.child_category_id = cc.id 
     LEFT JOIN collections ON products.collection_id = collections.id 
     LEFT JOIN vendors ON products.vendor_id = vendors.id 
     LEFT JOIN suppliers ON products.supplier_id = suppliers.id 
     LEFT JOIN brands ON products.brand_id = brands.id 
     WHERE products.status = 'active' 
     ORDER BY products.price ASC 
     LIMIT 12`
  );

  // 2. price_desc
  await explainQuery(
    "Products fullQuery ORDER BY price DESC",
    `SELECT products.id, products.title, products.price, categories.name AS categoryName 
     FROM products 
     LEFT JOIN categories ON products.category_id = categories.id 
     LEFT JOIN categories AS sc ON products.sub_category_id = sc.id 
     LEFT JOIN categories AS cc ON products.child_category_id = cc.id 
     LEFT JOIN collections ON products.collection_id = collections.id 
     LEFT JOIN vendors ON products.vendor_id = vendors.id 
     LEFT JOIN suppliers ON products.supplier_id = suppliers.id 
     LEFT JOIN brands ON products.brand_id = brands.id 
     WHERE products.status = 'active' 
     ORDER BY products.price DESC 
     LIMIT 12`
  );

  // 3. sale=true
  await explainQuery(
    "Products sale=true",
    `SELECT products.id, products.title, products.discount, products.sale_price 
     FROM products 
     LEFT JOIN categories ON products.category_id = categories.id 
     LEFT JOIN categories AS sc ON products.sub_category_id = sc.id 
     LEFT JOIN categories AS cc ON products.child_category_id = cc.id 
     LEFT JOIN collections ON products.collection_id = collections.id 
     LEFT JOIN vendors ON products.vendor_id = vendors.id 
     LEFT JOIN suppliers ON products.supplier_id = suppliers.id 
     LEFT JOIN brands ON products.brand_id = brands.id 
     WHERE products.status = 'active' AND (products.discount > 0 OR products.sale_price IS NOT NULL) 
     ORDER BY products.created_at DESC 
     LIMIT 12`
  );

  // 4. reviews
  await explainQuery(
    "Reviews getAll",
    `SELECT reviews.id, reviews.product_id, reviews.rating, reviews.comment, products.title, JSON_UNQUOTE(JSON_EXTRACT(products.images, '$[0]')) AS productImage 
     FROM reviews 
     LEFT JOIN products ON reviews.product_id = products.id 
     ORDER BY reviews.created_at DESC 
     LIMIT 12`
  );

  // 5. related products
  await explainQuery(
    "Related products query",
    `SELECT products.id, products.title, products.category_id 
     FROM products 
     LEFT JOIN categories ON products.category_id = categories.id 
     LEFT JOIN categories AS sc ON products.sub_category_id = sc.id 
     LEFT JOIN categories AS cc ON products.child_category_id = cc.id 
     LEFT JOIN collections ON products.collection_id = collections.id 
     LEFT JOIN vendors ON products.vendor_id = vendors.id 
     LEFT JOIN suppliers ON products.supplier_id = suppliers.id 
     LEFT JOIN brands ON products.brand_id = brands.id 
     WHERE products.category_id = 45 AND products.status = 'active' AND products.id != 270053 
     LIMIT 8`
  );

  process.exit(0);
}

main().catch((err) => {
  console.error("Explain error:", err);
  process.exit(1);
});
