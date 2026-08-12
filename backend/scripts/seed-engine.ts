import bcrypt from "bcryptjs";
import { eq, inArray } from "drizzle-orm";
import { db } from "../src/config/db";
import * as schema from "../src/config/schema";
import {
  categorySeeds,
  brandSeeds,
  collectionSeeds,
  vendorSeeds,
  supplierSeeds,
  colorSeeds,
  sizeSeeds,
  DEV_ADMIN_PHONE,
  DEV_ADMIN_PASSWORD,
} from "./seed-data";
import { productSeeds, heroSlideSeeds } from "./seed-products";
import { userSeeds, orderSeeds, couponSeeds } from "./seed-extras";

const counters: Record<string, number> = {};
const count = (key: string, n = 1) => {
  counters[key] = (counters[key] || 0) + n;
};

const SHIPPING = {
  dhaka: { name: "Inside Dhaka", charge: 120, eta: "1-2 days" },
  outside: { name: "Outside Dhaka", charge: 180, eta: "2-4 days" },
  express: { name: "Express Dhaka", charge: 250, eta: "Same day" },
};

const FLOW = ["pending", "payment_pending", "payment_verification", "confirmed", "processing", "packed", "shipped", "out_for_delivery", "delivered"];

const statusChain = (status: string): string[] => {
  if (status === "cancelled") return ["pending", "confirmed", "processing", "cancelled"];
  if (status === "returned") return ["pending", "confirmed", "processing", "shipped", "delivered", "returned"];
  if (status === "refunded") return ["pending", "confirmed", "processing", "shipped", "delivered", "refunded"];
  const idx = FLOW.indexOf(status);
  return idx === -1 ? [status] : FLOW.slice(0, idx + 1);
};

const mk = (code: string, n: number) => `${code}-${String(n).padStart(3, "0")}`;

export const wipeAll = async () => {
  const tables = [
    schema.productSpecs,
    schema.productRelations,
    schema.productVariants,
    schema.orderItems,
    schema.reviews,
    schema.orderStatusHistory,
    schema.orders,
    schema.products,
    schema.banners,
    schema.mediaAssets,
    schema.newsletters,
    schema.coupons,
    schema.checkoutNotices,
    schema.paymentMethods,
    schema.shippingMethods,
    schema.trackingLogs,
    schema.marketingIntegrations,
    schema.collections,
    schema.brands,
    schema.vendors,
    schema.suppliers,
    schema.colors,
    schema.sizes,
    schema.categories,
    schema.userAddresses,
    schema.users,
    schema.siteSettings,
  ];
  for (const table of tables) {
    await db.delete(table);
  }
  console.log("  ✓ all application tables cleared");
};

export const seedAll = async () => {
  const now = new Date();
  const daysAgo = (d: number) => new Date(now.getTime() - d * 86_400_000);

  // ============ USERS ============
  const adminHash = bcrypt.hashSync(DEV_ADMIN_PASSWORD, 12);
  const adminResult = await db.insert(schema.users).values({
    name: "Super Admin",
    phone: DEV_ADMIN_PHONE,
    password: adminHash,
    role: "admin",
    status: "active",
  });
  count("admins");
  const adminId = Number(adminResult[0].insertId);

  const managerResult = await db.insert(schema.users).values({
    name: "Store Manager",
    phone: "01722222222",
    password: adminHash,
    role: "manager",
    status: "active",
  });
  count("managers");
  const managerId = Number(managerResult[0].insertId);

  const userIds: Record<string, number> = {};
  const userByKey: Record<string, { id: number; name: string; phone: string }> = {};
  for (const u of userSeeds) {
    const result = await db.insert(schema.users).values({
      name: u.name,
      phone: u.phone,
      password: adminHash,
      role: u.role,
      status: "active",
      shippingArea: u.shippingArea,
      shippingAddress: u.shippingAddress,
      createdAt: daysAgo(60),
    });
    const id = Number(result[0].insertId);
    userIds[u.phone] = id;
    userByKey[u.name.toLowerCase().replace(/\s+/g, "-")] = { id, name: u.name, phone: u.phone };
    count("customers");
  }

  const addressResult = await db.insert(schema.userAddresses).values(
    userSeeds.map((u, i) => ({
      userId: userIds[u.phone],
      recipientName: u.name,
      phone: u.phone,
      shippingArea: u.shippingArea,
      address: u.shippingAddress || "Main Road, Dhaka",
      isDefault: true,
    }))
  );
  count("addresses", userSeeds.length);

  // ============ CATEGORIES ============
  const catId: Record<string, number> = {};
  for (const c of categorySeeds) {
    const parent = c.parent ? catId[c.parent] : null;
    const result = await db.insert(schema.categories).values({
      name: c.name,
      slug: c.slug,
      parentId: parent,
      image: c.image ?? null,
      featured: c.featured ? true : false,
      homepageVisibility: true,
      description: null,
      sortOrder: 0,
      status: "active",
      createdAt: daysAgo(90),
    });
    catId[c.slug] = Number(result[0].insertId);
  }
  count("categories", categorySeeds.length);

  // ============ BRANDS / COLLECTIONS / VENDORS / SUPPLIERS ============
  const brandId: Record<string, number> = {};
  for (const b of brandSeeds) {
    const result = await db.insert(schema.brands).values({
      name: b.name, slug: b.slug, logo: null, description: null,
      website: b.website, countryOfOrigin: b.country, featured: b.featured ? true : false,
      homepageVisibility: true, status: "active", createdAt: daysAgo(90),
    });
    brandId[b.slug] = Number(result[0].insertId);
  }
  count("brands", brandSeeds.length);

  const collectionId: Record<string, number> = {};
  for (const c of collectionSeeds) {
    const result = await db.insert(schema.collections).values({
      name: c.name, slug: c.slug, description: c.desc, image: null, banner: null,
      featured: c.featured ? true : false, homepageVisibility: true, status: "active", createdAt: daysAgo(80),
    });
    collectionId[c.slug] = Number(result[0].insertId);
  }
  count("collections", collectionSeeds.length);

  const vendorId: Record<string, number> = {};
  for (const v of vendorSeeds) {
    const result = await db.insert(schema.vendors).values({
      name: v.name, slug: v.slug, logo: null, description: null,
      contact: v.contact, phone: v.phone, email: v.email, address: v.address,
      status: "active", createdAt: daysAgo(85),
    });
    vendorId[v.slug] = Number(result[0].insertId);
  }
  count("vendors", vendorSeeds.length);

  const supplierId: Record<string, number> = {};
  for (const s of supplierSeeds) {
    const result = await db.insert(schema.suppliers).values({
      name: s.name, slug: s.slug, logo: null, description: null,
      contact: s.contact, phone: s.phone, email: s.email, address: s.address,
      status: "active", createdAt: daysAgo(85),
    });
    supplierId[s.slug] = Number(result[0].insertId);
  }
  count("suppliers", supplierSeeds.length);

  // ============ COLORS / SIZES ============
  for (const c of colorSeeds) {
    await db.insert(schema.colors).values({ name: c.name, displayName: c.displayName, hex: c.hex, status: "active", sortOrder: 0 });
  }
  count("colors", colorSeeds.length);

  for (const s of sizeSeeds) {
    await db.insert(schema.sizes).values({ name: s.name, type: s.type, status: "active", sortOrder: 0 });
  }
  count("sizes", sizeSeeds.length);

  // ============ SHIPPING / PAYMENT / NOTICES ============
  await db.insert(schema.shippingMethods).values([
    { name: SHIPPING.dhaka.name, charge: SHIPPING.dhaka.charge, estimatedDelivery: SHIPPING.dhaka.eta, description: "Standard delivery across Dhaka city", priority: 1, codAvailable: true, status: "active" },
    { name: SHIPPING.outside.name, charge: SHIPPING.outside.charge, estimatedDelivery: SHIPPING.outside.eta, description: "Standard delivery nationwide", priority: 2, codAvailable: true, status: "active" },
    { name: SHIPPING.express.name, charge: SHIPPING.express.charge, estimatedDelivery: SHIPPING.express.eta, description: "Same-day express within Dhaka", priority: 0, codAvailable: true, status: "active" },
  ]);
  count("shippingMethods", 3);

  await db.insert(schema.paymentMethods).values([
    { code: "cod", name: "Cash on Delivery", type: "cod", enabled: true, sortOrder: 0 },
    { code: "bkash", name: "bKash", type: "mobile_banking", enabled: true, sortOrder: 1, config: { merchantNumber: "01700000000" } },
    { code: "nagad", name: "Nagad", type: "mobile_banking", enabled: true, sortOrder: 2, config: { merchantNumber: "01700000000" } },
    { code: "rocket", name: "Rocket", type: "mobile_banking", enabled: true, sortOrder: 3 },
    { code: "bank", name: "Bank Transfer", type: "bank", enabled: true, sortOrder: 4 },
  ]);
  count("paymentMethods", 5);

  await db.insert(schema.checkoutNotices).values([
    { text: "Free delivery on orders over ৳2,000 — shop today!", priority: 1, status: "active" },
    { text: "bKash payment is available at checkout.", priority: 0, status: "active" },
  ]);
  count("checkoutNotices", 2);

  await db.insert(schema.newsletters).values([
    { email: "demo1@example.dev", source: "homepage", status: "subscribed" },
    { email: "demo2@example.dev", source: "homepage", status: "subscribed" },
    { email: "demo3@example.dev", source: "footer", status: "subscribed" },
  ]);
  count("newsletters", 3);

  // ============ COUPONS ============
  for (const c of couponSeeds) {
    await db.insert(schema.coupons).values({
      code: c.code, discountType: c.discountType, discountValue: c.discountValue,
      minOrderAmount: c.minOrderAmount, status: c.status, expiryDate: daysAgo(-90),
    });
  }
  count("coupons", couponSeeds.length);

  // ============ BANNERS ============
  const bannerResult = await db.insert(schema.banners).values([
    {
      title: "Mid-Month Tech Sale",
      subtitle: "Up to 25% off smartphones & audio",
      image: "https://images.unsplash.com/photo-1511707175664-5f897ff02aa9?q=80&w=1600&auto=format&fit=crop",
      link: "/shop?label=flash_sale", position: "promo", buttonText: "Shop Now", priority: 1, status: "active",
    },
    {
      title: "Eid Collection 2026",
      subtitle: "Panjabi, sarees and more",
      image: "https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=1600&auto=format&fit=crop",
      link: "/shop?collection=eid-collection", position: "promo", buttonText: "Explore", priority: 0, status: "active",
    },
  ]);
  count("banners", bannerResult.length);

  // ============ PRODUCTS ============
  const productId: Record<string, number> = {};
  const productPrice: Record<string, number> = {};
  const skuSeen = new Set<string>();
  const barcodeSeen = new Set<string>();

  for (const p of productSeeds) {
    if (skuSeen.has(p.sku)) throw new Error(`Duplicate product SKU: ${p.sku}`);
    if (barcodeSeen.has(p.barcode)) throw new Error(`Duplicate product barcode: ${p.barcode}`);
    skuSeen.add(p.sku);
    barcodeSeen.add(p.barcode);

    const salePrice = p.salePrice ?? p.price;
    const discount = p.discount ?? (salePrice < p.price ? Math.round(((p.price - salePrice) / p.price) * 100) : 0);

    const result = await db.insert(schema.products).values({
      title: p.title,
      slug: p.slug,
      description: p.description ?? `Shop ${p.title} at the best price in Bangladesh with official warranty and fast delivery.`,
      shortDescription: p.description,
      price: String(p.price),
      salePrice: String(salePrice),
      discount: String(discount),
      costPrice: String(p.costPrice ?? Math.round(salePrice * 0.8)),
      categoryId: p.category ? catId[p.category] : null,
      subCategoryId: p.subCategory ? catId[p.subCategory] : null,
      childCategoryId: p.childCategory ? catId[p.childCategory] : null,
      collectionId: p.collection ? collectionId[p.collection] : null,
      brandId: p.brand ? brandId[p.brand] : null,
      brand: p.brand,
      vendorId: p.vendor ? vendorId[p.vendor] : null,
      supplierId: p.supplier ? supplierId[p.supplier] : null,
      supplier: p.supplier === "style-mart-bd" ? "Style Mart BD" : null,
      sku: p.sku,
      barcode: p.barcode,
      tags: p.tags ?? [],
      warranty: p.warranty ?? null,
      weight: p.weight ?? null,
      features: p.features ?? [],
      emiAvailable: p.emiAvailable ?? false,
      isFeatured: p.isFeatured ?? false,
      isTrending: p.isTrending ?? false,
      isFlashSale: p.isFlashSale ?? false,
      isNewArrival: p.isNewArrival ?? false,
      isBestSeller: p.isBestSeller ?? false,
      isLimitedEdition: p.isLimitedEdition ?? false,
      isOfficial: p.isOfficial ?? false,
      isHotDeal: p.isHotDeal ?? false,
      stock: p.stock,
      images: p.images,
      sizeOptions: p.sizes ?? [],
      colorOptions: p.colors ?? [],
      shippingCharge: p.shippingCharge ?? 0,
      paymentMethods: ["cod"] as any,
      status: "active",
      productStatus: "published",
      stockStatus: p.stock === 0 ? "out_of_stock" : p.stock <= 10 ? "low_stock" : "in_stock",
      createdAt: daysAgo(p.daysAgo ?? 45),
    });
    productId[p.slug] = Number(result[0].insertId);
    productPrice[p.slug] = salePrice;
  }
  count("products", productSeeds.length);

  // ============ PRODUCT VARIANTS ============
  let variantCount = 0;
  const variantSeeds: { product: string; name: string; options: Record<string, string>; price: number; discountPrice?: number; sku: string; barcode?: string; stock: number; images?: string[]; thumbnail?: string }[] = [];

  for (const p of productSeeds) {
    const colors = p.colors ?? [];
    const sizes = p.sizes ?? [];
    const base = productPrice[p.slug];
    if (colors.length > 0 && sizes.length === 0 && colors.length <= 3) {
      colors.forEach((c, i) => {
        variantSeeds.push({
          product: p.slug,
          name: `${p.title} — ${c.name}`,
          options: { color: c.name },
          price: base,
          sku: `${p.sku}-${c.name.slice(0, 3).toUpperCase()}`,
          barcode: `${p.barcode}${i + 1}`,
          stock: Math.max(0, p.stock - i * 7),
          images: [c.image ?? p.images[0], ...p.images.slice(1, 3)],
          thumbnail: c.image ?? p.images[0],
        });
      });
    } else if (colors.length === 0 && sizes.length > 0 && sizes.length <= 5) {
      sizes.forEach((s, i) => {
        variantSeeds.push({
          product: p.slug,
          name: `${p.title} — Size ${s}`,
          options: { size: s },
          price: base + (i % 2 === 0 ? 0 : 50),
          sku: `${p.sku}-${s}`,
          barcode: `${p.barcode}${i + 1}`,
          stock: Math.max(0, p.stock - i * 6),
          images: p.images.slice(0, 2),
          thumbnail: p.images[0],
        });
      });
    } else if (colors.length > 0 && sizes.length > 0) {
      const combos = [];
      for (const c of colors.slice(0, 2)) {
        for (const s of sizes.slice(0, 3)) {
          combos.push({ color: c.name, size: s, image: c.image ?? p.images[0] });
        }
      }
      combos.forEach((combo, i) => {
        variantSeeds.push({
          product: p.slug,
          name: `${p.title} — ${combo.color} / ${combo.size}`,
          options: { color: combo.color, size: combo.size },
          price: base + (i % 3) * 50,
          sku: `${p.sku}-${combo.color.slice(0, 3).toUpperCase()}-${combo.size}`,
          barcode: `${p.barcode}${i + 1}`,
          stock: Math.max(0, p.stock - i * 5),
          images: [combo.image, ...p.images.slice(1, 3)],
          thumbnail: combo.image,
        });
      });
    }
  }

  for (const v of variantSeeds) {
    await db.insert(schema.productVariants).values({
      productId: productId[v.product],
      name: v.name,
      options: v.options,
      price: String(v.price),
      discountPrice: v.discountPrice ? String(v.discountPrice) : null,
      sku: v.sku,
      barcode: v.barcode ?? null,
      stock: v.stock,
      images: v.images ?? [],
      thumbnail: v.thumbnail ?? null,
      status: "active",
      availability: v.stock > 0,
    });
    variantCount++;
  }
  count("variants", variantCount);

  // ============ PRODUCT SPECS (a few flagship) ============
  for (const slug of ["samsung-galaxy-s24-ultra", "philips-air-fryer-55l", "xiaomi-robot-vacuum"]) {
    const pid = productId[slug];
    if (!pid) continue;
    const specs = slug === "samsung-galaxy-s24-ultra"
      ? [["Display", "6.8\" QHD+ AMOLED, 120Hz"], ["Camera", "200MP main + 50MP telephoto"], ["Battery", "5000mAh"], ["Charging", "45W wired / 15W wireless"]]
      : slug === "philips-air-fryer-55l"
        ? [["Capacity", "5.5L"], ["Technology", "Rapid Air"], ["Power", "1500W"], ["Temperature", "Up to 200°C"]]
        : [["Suction", "4000Pa"], ["Navigation", "Laser LDS"], ["Runtime", "Up to 120 min"], ["Control", "App + Voice"]];
    await db.insert(schema.productSpecs).values(specs.map(([label, value], i) => ({ productId: pid, label, value, sortOrder: i })));
  }

  // ============ PRODUCT RELATIONS ============
  await db.insert(schema.productRelations).values([
    { productId: productId["samsung-galaxy-s24-ultra"], relatedProductId: productId["baseus-65w-gan-charger"], type: "accessories" },
    { productId: productId["samsung-galaxy-s24-ultra"], relatedProductId: productId["samsung-galaxy-watch-7"], type: "frequently_bought_together" },
    { productId: productId["philips-air-fryer-55l"], relatedProductId: productId["walton-25l-rice-cooker"], type: "similar" },
    { productId: productId["jbl-tune-510bt-headphones"], relatedProductId: productId["jbl-go-4-speaker"], type: "cross_sell" },
    { productId: productId["xiaomi-smart-band-9"], relatedProductId: productId["xiaomi-redmi-note-13-pro"], type: "frequently_bought_together" },
    { productId: productId["premium-cotton-panjabi"], relatedProductId: productId["mens-slim-fit-formal-shirt"], type: "cross_sell" },
  ]);

  // ============ ORDERS ============
  const orderIdMap: number[] = [];
  let orderSeq = 2000;
  const couponByCode: Record<string, { type: string; value: number; min: number }> = {};
  for (const c of couponSeeds) {
    couponByCode[c.code] = { type: c.discountType, value: Number(c.discountValue), min: Number(c.minOrderAmount) };
  }

  for (const o of orderSeeds) {
    const items = o.items.map((it) => ({
      productId: productId[it.product],
      quantity: it.quantity,
      price: productPrice[it.product],
    }));
    if (items.some((i) => !i.productId)) throw new Error(`Order references unknown product: ${JSON.stringify(o.items)}`);

    const subtotal = items.reduce((sum, i) => sum + i.price * i.quantity, 0);
    const isDhaka = o.area === "Dhanmondi" || o.area === "Uttara" || o.area === "Banani" || o.area === "Mirpur" || o.area === "Bashundhara";
    const shippingCost = isDhaka ? SHIPPING.dhaka.charge : SHIPPING.outside.charge;
    const shippingName = isDhaka ? SHIPPING.dhaka.name : SHIPPING.outside.name;

    let discount = 0;
    if (o.coupon) {
      const coupon = couponByCode[o.coupon];
      if (!coupon) throw new Error(`Order references unknown coupon: ${o.coupon}`);
      if (subtotal >= coupon.min) {
        discount = coupon.type === "percentage" ? Math.round((subtotal * coupon.value) / 100) : coupon.value;
      }
    }
    const total = subtotal - discount + shippingCost;

    const result = await db.insert(schema.orders).values({
      orderId: `GB-${orderSeq++}`,
      userId: userByKey[o.user]?.id ?? null,
      customerName: userByKey[o.user]?.name ?? "Test Customer",
      phone: userByKey[o.user]?.phone ?? "01700000000",
      address: o.address,
      area: o.area,
      division: isDhaka ? "Dhaka" : "Outside Dhaka",
      district: isDhaka ? "Dhaka" : o.area,
      shippingMethodName: shippingName,
      shippingCost: String(shippingCost),
      subtotal: String(subtotal),
      couponCode: o.coupon ?? null,
      discount: String(discount),
      totalPrice: String(total),
      paymentMethod: o.paymentMethod as any,
      transactionId: o.transactionId ?? null,
      senderNumber: o.senderNumber ?? null,
      paymentStatus: o.paymentStatus as any,
      status: o.status as any,
      orderNote: o.note ?? null,
      createdAt: daysAgo(o.daysAgo),
    });
    const orderId = Number(result[0].insertId);
    orderIdMap.push(orderId);

    await db.insert(schema.orderItems).values(
      items.map((i) => ({ orderId, productId: i.productId, quantity: i.quantity, price: String(i.price) }))
    );
    count("orderItems", items.length);

    const chain = statusChain(o.status);
    await db.insert(schema.orderStatusHistory).values(
      chain.map((status, i) => ({
        orderId,
        status: status as any,
        note: i === 0 ? "Order placed" : status === "cancelled" ? "Cancelled by customer" : undefined,
        createdAt: daysAgo(Math.max(0, o.daysAgo - (chain.length - 1 - i) * 2)),
      }))
    );
  }
  count("orders", orderSeeds.length);

  // ============ REVIEWS ============
  const reviewPool = [
    { name: "Karim Rahman", phone: "01710000001", rating: 5, title: "Excellent quality", comment: "Fast delivery and the product matched the description perfectly." },
    { name: "Farzana Akter", phone: "01710000002", rating: 4, title: "Really good", comment: "Very happy with the purchase. Would recommend to friends." },
    { name: "Tanvir Islam", phone: "01710000003", rating: 5, title: "Best price online", comment: "Cheaper than local shops and original product with warranty." },
    { name: "Nusrat Jahan", phone: "01710000004", rating: 3, title: "Good but slow delivery", comment: "Product is fine, but the delivery took longer than expected." },
    { name: "Rakib Hasan", phone: "01710000005", rating: 4, title: "Great value", comment: "Solid build quality for the price. Battery life is impressive." },
    { name: "Saima Chowdhury", phone: "01710000006", rating: 5, title: "Love it", comment: "Looks exactly like the photos. Very premium feel." },
    { name: "Arif Hossain", phone: "01710000007", rating: 4, title: "Worth it", comment: "Good product, packaging could be improved though." },
    { name: "Mim Khan", phone: "01710000008", rating: 5, title: "Perfect", comment: "Exceeded expectations. Will order again." },
  ];
  const reviewProducts = ["samsung-galaxy-s24-ultra", "philips-air-fryer-55l", "jbl-tune-510bt-headphones", "premium-cotton-panjabi", "designer-silk-saree", "xiaomi-smart-band-9", "tp-link-smart-wifi-bulb", "wifi-security-camera-360", "running-performance-sneakers", "philips-600w-blender", "sony-wh-ch520", "walton-ceiling-fan-56"];

  let reviewCount = 0;
  for (let i = 0; i < 30; i++) {
    const person = reviewPool[i % reviewPool.length];
    const productSlug = reviewProducts[i % reviewProducts.length];
    const product = productSeeds.find((p) => p.slug === productSlug);
    const user = userSeeds.find((u) => u.name === person.name);
    if (!product || !user) continue;
    await db.insert(schema.reviews).values({
      productId: productId[productSlug],
      userId: userIds[user.phone],
      customerName: person.name,
      rating: person.rating,
      title: person.title,
      comment: person.comment,
      status: i % 15 === 14 ? "pending" : "approved",
      createdAt: daysAgo(5 + i),
    });
    reviewCount++;
  }
  count("reviews", reviewCount);

  // ============ HOMEPAGE CONFIG (hero slides etc.) ============
  const { getConfig, saveConfig } = await import("../src/modules/homepage/homepage.service");
  const config = await getConfig();
  config.heroSlides = heroSlideSeeds as any;
  config.announcement = { enabled: true, text: "Free delivery on orders over ৳2,000 — shop today!" };
  config.flashSaleWindow = { enabled: true, start: daysAgo(3).toISOString(), end: daysAgo(-31).toISOString() };
  config.popularSearches = ["smartphone", "air fryer", "panjabi", "smart bulb", "saree", "sneakers", "power bank", "headphones"];
  await saveConfig(config);
  count("heroSlides", heroSlideSeeds.length);

  // ============ POLICY PAGES ============
  const { policyPageSeeds } = await import("./seed-pages");
  const lastUpdated = Math.floor(Date.now() / 1000);
  let pageCount = 0;
  for (const page of policyPageSeeds) {
    const existing = await db.select({ id: schema.policyPages.id }).from(schema.policyPages).where(eq(schema.policyPages.slug, page.slug)).limit(1);
    if (existing[0]) continue;
    await db.insert(schema.policyPages).values({
      slug: page.slug,
      title: page.title,
      content: page.content,
      status: "published",
      lastUpdated: lastUpdated,
      updatedBy: adminId,
    });
    pageCount++;
  }
  count("policyPages", pageCount);

  // ============ CONTACT INFO SETTINGS ============
  const contactInfo = {
    phone: "",
    email: "",
    address: "",
    supportHours: "",
    hotline: "",
    whatsapp: "",
  };
  const existingContact = await db.select({ id: schema.siteSettings.id }).from(schema.siteSettings).where(eq(schema.siteSettings.key, "contact_info")).limit(1);
  if (!existingContact[0]) {
    await db.insert(schema.siteSettings).values({ key: "contact_info", value: JSON.stringify(contactInfo) });
    count("contactSettings");
  }

  // ============ MEDIA ASSETS ============
  const seen: Record<string, boolean> = {};
  const mediaRows: (typeof schema.mediaAssets.$inferInsert)[] = [];
  const urls = productSeeds.flatMap((p) => p.images).concat(heroSlideSeeds.map((h) => h.desktopImage));
  for (const url of urls) {
    if (seen[url]) continue;
    seen[url] = true;
    mediaRows.push({
      url, filename: url.split("/").pop() ?? "asset", mimeType: "image/jpeg", size: 0,
      width: 1080, height: 720, provider: "cloudinary", folder: "products", alt: null,
      uploaderId: adminId,
    });
  }
  for (let i = 0; i < mediaRows.length; i += 50) {
    await db.insert(schema.mediaAssets).values(mediaRows.slice(i, i + 50));
  }
  count("mediaAssets", mediaRows.length);

  // ============ VALIDATION ============
  const errors: string[] = [];
  const productRows = await db.select().from(schema.products);
  const allCatRows = await db.select({ id: schema.categories.id }).from(schema.categories);
  const catIdSet = new Set(allCatRows.map((r) => r.id));
  const allBrandRows = await db.select({ id: schema.brands.id }).from(schema.brands);
  const brandIdSet = new Set(allBrandRows.map((r) => r.id));
  const allCollectionRows = await db.select({ id: schema.collections.id }).from(schema.collections);
  const collectionIdSet = new Set(allCollectionRows.map((r) => r.id));
  const allVendorRows = await db.select({ id: schema.vendors.id }).from(schema.vendors);
  const vendorIdSet = new Set(allVendorRows.map((r) => r.id));
  const allSupplierRows = await db.select({ id: schema.suppliers.id }).from(schema.suppliers);
  const supplierIdSet = new Set(allSupplierRows.map((r) => r.id));
  const userRows = await db.select().from(schema.users);
  const userIdSet = new Set(userRows.map((r) => r.id));
  const productIdSet = new Set(productRows.map((r) => r.id));

  for (const row of productRows) {
    if (row.categoryId !== null && !catIdSet.has(row.categoryId)) errors.push(`Product ${row.slug}: invalid categoryId ${row.categoryId}`);
    if (row.brandId !== null && !brandIdSet.has(row.brandId)) errors.push(`Product ${row.slug}: invalid brandId ${row.brandId}`);
    if (row.collectionId !== null && !collectionIdSet.has(row.collectionId)) errors.push(`Product ${row.slug}: invalid collectionId ${row.collectionId}`);
    if (row.vendorId !== null && !vendorIdSet.has(row.vendorId)) errors.push(`Product ${row.slug}: invalid vendorId ${row.vendorId}`);
    if (row.supplierId !== null && !supplierIdSet.has(row.supplierId)) errors.push(`Product ${row.slug}: invalid supplierId ${row.supplierId}`);
    if (row.subCategoryId !== null && !catIdSet.has(row.subCategoryId)) errors.push(`Product ${row.slug}: invalid subCategoryId ${row.subCategoryId}`);
    if (row.childCategoryId !== null && !catIdSet.has(row.childCategoryId)) errors.push(`Product ${row.slug}: invalid childCategoryId ${row.childCategoryId}`);
  }
  const variantRows = await db.select().from(schema.productVariants);
  for (const v of variantRows) {
    if (!productIdSet.has(v.productId)) errors.push(`Variant ${v.sku}: invalid productId ${v.productId}`);
  }
  const orderRows = await db.select().from(schema.orders);
  const orderIdSet = new Set(orderRows.map((r) => r.id));
  const itemRows = await db.select().from(schema.orderItems);
  for (const it of itemRows) {
    if (!orderIdSet.has(it.orderId)) errors.push(`OrderItem: invalid orderId ${it.orderId}`);
    if (!productIdSet.has(it.productId)) errors.push(`OrderItem: invalid productId ${it.productId}`);
  }
  const reviewRows = await db.select().from(schema.reviews);
  for (const r of reviewRows) {
    if (!productIdSet.has(r.productId)) errors.push(`Review: invalid productId ${r.productId}`);
    if (r.userId !== null && !userIdSet.has(r.userId)) errors.push(`Review: invalid userId ${r.userId}`);
  }
  for (const o of orderRows) {
    if (o.userId !== null && !userIdSet.has(o.userId)) errors.push(`Order ${o.orderId}: invalid userId ${o.userId}`);
  }
  const slugSeen = new Set<string>();
  for (const r of productRows) {
    if (slugSeen.has(r.slug)) errors.push(`Duplicate product slug: ${r.slug}`);
    slugSeen.add(r.slug);
  }
  const skuRows = await db.select({ sku: schema.products.sku }).from(schema.products);
  const skuMap = new Map<string, number>();
  for (const r of skuRows) {
    if (r.sku) {
      skuMap.set(r.sku, (skuMap.get(r.sku) ?? 0) + 1);
      if (skuMap.get(r.sku)! > 1) errors.push(`Duplicate product SKU: ${r.sku}`);
    }
  }
  const vSku = new Map<string, number>();
  for (const v of variantRows) {
    if (!v.sku) continue;
    vSku.set(v.sku, (vSku.get(v.sku) ?? 0) + 1);
    if (vSku.get(v.sku)! > 1) errors.push(`Duplicate variant SKU: ${v.sku}`);
  }
  const catRows = await db.select({ name: schema.categories.name, slug: schema.categories.slug }).from(schema.categories);
  const catNames = new Set<string>();
  for (const c of catRows) {
    if (catNames.has(c.name)) errors.push(`Duplicate category name: ${c.name}`);
    catNames.add(c.name);
  }
  const emailRows = await db.select({ email: schema.newsletters.email }).from(schema.newsletters);
  const emailSet = new Set<string>();
  for (const n of emailRows) {
    if (emailSet.has(n.email)) errors.push(`Duplicate newsletter email: ${n.email}`);
    emailSet.add(n.email);
  }

  if (errors.length > 0) {
    throw new Error(`SEED VALIDATION FAILED:\n${errors.join("\n")}`);
  }

  // ============ SUMMARY ============
  console.log("\n========================================");
  console.log("DATABASE SEED COMPLETED");
  console.log("========================================");
  console.log(`Super Admins:  ${counters.admins ?? 0}`);
  console.log(`Managers:      ${counters.managers ?? 0}`);
  console.log(`Customers:     ${counters.customers ?? 0}`);
  console.log(`Addresses:     ${counters.addresses ?? 0}`);
  console.log("");
  console.log(`Categories:    ${counters.categories ?? 0}`);
  console.log(`Brands:        ${counters.brands ?? 0}`);
  console.log(`Collections:   ${counters.collections ?? 0}`);
  console.log(`Vendors:       ${counters.vendors ?? 0}`);
  console.log(`Suppliers:     ${counters.suppliers ?? 0}`);
  console.log(`Colors:        ${counters.colors ?? 0}`);
  console.log(`Sizes:         ${counters.sizes ?? 0}`);
  console.log("");
  console.log(`Products:      ${counters.products ?? 0}`);
  console.log(`Variants:      ${counters.variants ?? 0}`);
  console.log(`Hero Slides:   ${counters.heroSlides ?? 0}`);
  console.log(`Media Assets:  ${counters.mediaAssets ?? 0}`);
  console.log("");
  console.log(`Orders:        ${counters.orders ?? 0}`);
  console.log(`Order Items:   ${counters.orderItems ?? 0}`);
  console.log(`Reviews:       ${counters.reviews ?? 0}`);
  console.log(`Coupons:       ${counters.coupons ?? 0}`);
  console.log(`Shipping:      ${counters.shippingMethods ?? 0}`);
  console.log(`Payments:      ${counters.paymentMethods ?? 0}`);
  console.log(`Newsletters:   ${counters.newsletters ?? 0}`);
  console.log(`Banners:       ${counters.banners ?? 0}`);
  console.log(`Policy Pages:  ${counters.policyPages ?? 0}`);
  console.log(`Contact Set:   ${counters.contactSettings ?? 0}`);
  console.log("");
  console.log("DEV SUPER ADMIN");
  console.log(`Phone:    ${DEV_ADMIN_PHONE}`);
  console.log(`Password: ${DEV_ADMIN_PASSWORD}`);
  console.log("");
  console.log("DEV CUSTOMERS (password: DevAdmin@12345)");
  for (const u of userSeeds) console.log(`  ${u.name}: ${u.phone}`);
  console.log("");
  console.log("========================================\n");

  return { adminId, managerId };
};
