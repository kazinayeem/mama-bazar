import { db } from "../config/db";
import { sql } from "drizzle-orm";

async function main() {
  console.log("Analyzing tables...");
  await db.execute(sql.raw("ANALYZE TABLE `products`"));
  await db.execute(sql.raw("ANALYZE TABLE `reviews`"));
  console.log("Tables analyzed successfully.");

  const queries = [
    {
      name: "1. Price ASC (Simple)",
      sql: "SELECT id, title, price FROM products WHERE status = 'active' ORDER BY price ASC LIMIT 12"
    },
    {
      name: "2. Price ASC (fullQuery with joins)",
      sql: `SELECT products.id, products.title, products.price, categories.name AS categoryName 
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
    },
    {
      name: "3. Price DESC (fullQuery with joins)",
      sql: `SELECT products.id, products.title, products.price, categories.name AS categoryName 
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
    },
    {
      name: "4. Sale=True (fullQuery with joins)",
      sql: `SELECT products.id, products.title, products.discount, products.sale_price 
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
    },
    {
      name: "5. Reviews (getAll with product join)",
      sql: `SELECT reviews.id, reviews.product_id, reviews.rating, reviews.comment, products.title, JSON_UNQUOTE(JSON_EXTRACT(products.images, '$[0]')) AS productImage 
            FROM reviews 
            LEFT JOIN products ON reviews.product_id = products.id 
            WHERE reviews.status = 'approved'
            ORDER BY reviews.created_at DESC 
            LIMIT 12`
    }
  ];

  for (const q of queries) {
    console.log(`\n========================================================`);
    console.log(`EXPLAIN FOR: ${q.name}`);
    console.log(`========================================================`);
    const res = await db.execute(sql.raw(`EXPLAIN ${q.sql}`));
    const rows = Array.isArray(res[0]) ? res[0] : res;
    console.table(rows);
  }

  process.exit(0);
}

main().catch((err) => {
  console.error("Error:", err);
  process.exit(1);
});
