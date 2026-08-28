import { db } from "../config/db";
import { sql } from "drizzle-orm";

async function measure(name: string, fn: () => Promise<any>, runs = 10) {
  // warm up
  await fn();
  await fn();

  const times: number[] = [];
  for (let i = 0; i < runs; i++) {
    const t0 = performance.now();
    await fn();
    times.push(performance.now() - t0);
  }
  times.sort((a, b) => a - b);
  const avg = times.reduce((a, b) => a + b, 0) / times.length;
  const p50 = times[Math.floor(times.length * 0.5)];
  const p95 = times[Math.floor(times.length * 0.95)];
  console.log(`${name.padEnd(50)} Avg: ${avg.toFixed(1)}ms | P50: ${p50.toFixed(1)}ms | P95: ${p95.toFixed(1)}ms`);
}

async function main() {
  console.log("\n--- Benchmarking Price Sort Query Strategies ---");

  // 1. Current Price ASC with 7 joins
  await measure("1. Price ASC (7 Joins - Current)", () =>
    db.execute(sql.raw(`
      SELECT products.id, products.title, products.price, categories.name AS categoryName 
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
      LIMIT 12
    `))
  );

  // 2. Price ASC with Derived Table (Find 12 IDs first, then join)
  await measure("2. Price ASC (Derived Table + 12 Joins)", () =>
    db.execute(sql.raw(`
      SELECT p.*, categories.name AS categoryName, brands.name AS brandName, brands.logo AS brandLogo
      FROM (
        SELECT id FROM products WHERE status = 'active' ORDER BY price ASC LIMIT 12
      ) AS selected
      JOIN products p ON selected.id = p.id
      LEFT JOIN categories ON p.category_id = categories.id
      LEFT JOIN categories AS sc ON p.sub_category_id = sc.id
      LEFT JOIN categories AS cc ON p.child_category_id = cc.id
      LEFT JOIN collections ON p.collection_id = collections.id
      LEFT JOIN vendors ON p.vendor_id = vendors.id
      LEFT JOIN suppliers ON p.supplier_id = suppliers.id
      LEFT JOIN brands ON p.brand_id = brands.id
      ORDER BY p.price ASC
    `))
  );

  // 3. Price DESC with 7 joins
  await measure("3. Price DESC (7 Joins - Current)", () =>
    db.execute(sql.raw(`
      SELECT products.id, products.title, products.price, categories.name AS categoryName 
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
      LIMIT 12
    `))
  );

  // 4. Price DESC with Derived Table
  await measure("4. Price DESC (Derived Table + 12 Joins)", () =>
    db.execute(sql.raw(`
      SELECT p.*, categories.name AS categoryName, brands.name AS brandName, brands.logo AS brandLogo
      FROM (
        SELECT id FROM products WHERE status = 'active' ORDER BY price DESC LIMIT 12
      ) AS selected
      JOIN products p ON selected.id = p.id
      LEFT JOIN categories ON p.category_id = categories.id
      LEFT JOIN categories AS sc ON p.sub_category_id = sc.id
      LEFT JOIN categories AS cc ON p.child_category_id = cc.id
      LEFT JOIN collections ON p.collection_id = collections.id
      LEFT JOIN vendors ON p.vendor_id = vendors.id
      LEFT JOIN suppliers ON p.supplier_id = suppliers.id
      LEFT JOIN brands ON p.brand_id = brands.id
      ORDER BY p.price DESC
    `))
  );

  // 5. Sale=true Current
  await measure("5. Sale=true (Current)", () =>
    db.execute(sql.raw(`
      SELECT products.id, products.title, products.discount, products.sale_price 
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
      LIMIT 12
    `))
  );

  // 6. Sale=true Derived Table
  await measure("6. Sale=true (Derived Table)", () =>
    db.execute(sql.raw(`
      SELECT p.*, categories.name AS categoryName, brands.name AS brandName, brands.logo AS brandLogo
      FROM (
        SELECT id FROM products WHERE status = 'active' AND (discount > 0 OR sale_price IS NOT NULL) ORDER BY created_at DESC LIMIT 12
      ) AS selected
      JOIN products p ON selected.id = p.id
      LEFT JOIN categories ON p.category_id = categories.id
      LEFT JOIN categories AS sc ON p.sub_category_id = sc.id
      LEFT JOIN categories AS cc ON p.child_category_id = cc.id
      LEFT JOIN collections ON p.collection_id = collections.id
      LEFT JOIN vendors ON p.vendor_id = vendors.id
      LEFT JOIN suppliers ON p.supplier_id = suppliers.id
      LEFT JOIN brands ON p.brand_id = brands.id
      ORDER BY p.created_at DESC
    `))
  );

  // 7. Reviews Current
  await measure("7. Reviews (Current Left Join)", () =>
    db.execute(sql.raw(`
      SELECT reviews.id, reviews.product_id, reviews.rating, reviews.comment, products.title, JSON_UNQUOTE(JSON_EXTRACT(products.images, '$[0]')) AS productImage 
      FROM reviews 
      LEFT JOIN products ON reviews.product_id = products.id 
      WHERE reviews.status = 'approved'
      ORDER BY reviews.created_at DESC 
      LIMIT 12
    `))
  );

  // 8. Reviews Two-Step: Fetch 12 reviews, then batch lookup product titles/images
  await measure("8. Reviews (Two-Step: Reviews first, then batch product info)", async () => {
    const [revs]: any = await db.execute(sql.raw(`
      SELECT id, product_id, user_id, customer_name, rating, title, comment, status, created_at
      FROM reviews
      WHERE status = 'approved'
      ORDER BY created_at DESC
      LIMIT 12
    `));
    const pids = [...new Set(revs.map((r: any) => r.product_id).filter(Boolean))];
    if (pids.length > 0) {
      await db.execute(sql.raw(`
        SELECT id, title, slug, JSON_UNQUOTE(JSON_EXTRACT(images, '$[0]')) AS productImage
        FROM products
        WHERE id IN (${pids.join(",")})
      `));
    }
  });

  process.exit(0);
}

main().catch(err => { console.error(err); process.exit(1); });
