import {
  mysqlTable,
  int,
  varchar,
  text,
  decimal,
  timestamp,
  datetime,
  mysqlEnum,
  json,
  boolean,
  index,
} from "drizzle-orm/mysql-core";
import type { AnyMySqlColumn } from "drizzle-orm/mysql-core";
import { sql, relations } from "drizzle-orm";

// ==================== CATEGORIES ====================
export const categories = mysqlTable("categories", {
  id: int("id").primaryKey().autoincrement(),
  name: varchar("name", { length: 255 }).notNull(),
  slug: varchar("slug", { length: 255 }).notNull().unique(),
  parentId: int("parent_id").references((): AnyMySqlColumn => categories.id),
  image: varchar("image", { length: 500 }),
  icon: varchar("icon", { length: 500 }),
  banner: varchar("banner", { length: 500 }),
  thumbnail: varchar("thumbnail", { length: 500 }),
  description: text("description"),
  featured: boolean("featured").default(false).notNull(),
  sortOrder: int("sort_order").default(0).notNull(),
  homepageVisibility: boolean("homepage_visibility").default(true).notNull(),
  seoTitle: varchar("seo_title", { length: 255 }),
  seoDescription: text("seo_description"),
  seoKeywords: varchar("seo_keywords", { length: 500 }),
  status: mysqlEnum("status", ["active", "inactive", "archived"]).default("active").notNull(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

export const categoriesRelations = relations(categories, ({ many }) => ({
  products: many(products),
}));

// ==================== PRODUCTS ====================
export const products = mysqlTable("products", {
  id: int("id").primaryKey().autoincrement(),
  title: varchar("title", { length: 255 }).notNull(),
  slug: varchar("slug", { length: 255 }).notNull().unique(),
  description: text("description"),
  shortDescription: text("short_description"),
  price: decimal("price", { precision: 10, scale: 2 }).notNull(),
  salePrice: decimal("sale_price", { precision: 10, scale: 2 }),
  discount: decimal("discount", { precision: 10, scale: 2 }).default("0"),
  costPrice: decimal("cost_price", { precision: 10, scale: 2 }).default("0"),
  profitMargin: decimal("profit_margin", { precision: 10, scale: 2 }).default("0"),
  tax: decimal("tax", { precision: 10, scale: 2 }).default("0"),
  vat: decimal("vat", { precision: 10, scale: 2 }).default("0"),
  shippingCharge: decimal("shipping_charge", { precision: 10, scale: 2 }).default("0"),
  codFee: decimal("cod_fee", { precision: 10, scale: 2 }).default("0"),
  flashSalePrice: decimal("flash_sale_price", { precision: 10, scale: 2 }),
  wholesalePrice: decimal("wholesale_price", { precision: 10, scale: 2 }),
  dealerPrice: decimal("dealer_price", { precision: 10, scale: 2 }),
  categoryId: int("category_id").references(() => categories.id),
  subCategoryId: int("sub_category_id"),
  childCategoryId: int("child_category_id"),
  collectionId: int("collection_id").references(() => collections.id),
  brandId: int("brand_id"),
  brand: varchar("brand", { length: 100 }),
  vendorId: int("vendor_id").references(() => vendors.id),
  supplierId: int("supplier_id").references(() => suppliers.id),
  supplier: varchar("supplier", { length: 255 }),
  countryOfOrigin: varchar("country_of_origin", { length: 100 }),
  sku: varchar("sku", { length: 100 }),
  barcode: varchar("barcode", { length: 100 }),
  tags: json("tags").$type<string[]>(),
  warranty: varchar("warranty", { length: 100 }),
  weight: varchar("weight", { length: 50 }),
  dimensions: varchar("dimensions", { length: 100 }),
  features: json("features").$type<string[]>(),
  returnPolicy: text("return_policy"),
  warehouse: varchar("warehouse", { length: 255 }),
  videoUrl: varchar("video_url", { length: 500 }),
  seoTitle: varchar("seo_title", { length: 255 }),
  seoDescription: text("seo_description"),
  seoKeywords: varchar("seo_keywords", { length: 500 }),
  canonicalUrl: varchar("canonical_url", { length: 500 }),
  ogImage: varchar("og_image", { length: 500 }),
  twitterImage: varchar("twitter_image", { length: 500 }),
  structuredData: json("structured_data").$type<Record<string, unknown>>(),
  draft: json("draft").$type<Record<string, unknown>>(),
  emiAvailable: boolean("emi_available").default(false).notNull(),
  isFeatured: boolean("is_featured").default(false).notNull(),
  isTrending: boolean("is_trending").default(false).notNull(),
  isFlashSale: boolean("is_flash_sale").default(false).notNull(),
  isNewArrival: boolean("is_new_arrival").default(false).notNull(),
  isBestSeller: boolean("is_best_seller").default(false).notNull(),
  isLimitedEdition: boolean("is_limited_edition").default(false).notNull(),
  isOfficial: boolean("is_official").default(false).notNull(),
  isHotDeal: boolean("is_hot_deal").default(false).notNull(),
  isArchived: boolean("is_archived").default(false).notNull(),
  meta: json("meta").$type<Record<string, unknown>>(),
  stock: int("stock").default(0).notNull(),
  lowStockAlert: int("low_stock_alert").default(10).notNull(),
  minOrder: int("min_order").default(1).notNull(),
  maxOrder: int("max_order"),
  unlimitedStock: boolean("unlimited_stock").default(false).notNull(),
  backorder: boolean("backorder").default(false).notNull(),
  trackInventory: boolean("track_inventory").default(true).notNull(),
  stockStatus: varchar("stock_status", { length: 20 }).default("in_stock"),
  productStatus: varchar("product_status", { length: 30 }).default("published"),
  images: json("images").$type<string[]>(),
  sizeOptions: json("size_options").$type<string[]>(),
  colorOptions: json("color_options").$type<Array<{ name: string; value?: string; image?: string }>>(),
  paymentMethods: json("payment_methods").$type<["cod", "online"]>(),
  paymentPhoneNumber: varchar("payment_phone_number", { length: 20 }),
  status: mysqlEnum("status", ["active", "inactive"]).default("active").notNull(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

export const productsRelations = relations(products, ({ one, many }) => ({
  category: one(categories, {
    fields: [products.categoryId],
    references: [categories.id],
  }),
  subCategory: one(categories, {
    fields: [products.subCategoryId],
    references: [categories.id],
  }),
  childCategory: one(categories, {
    fields: [products.childCategoryId],
    references: [categories.id],
  }),
  collection: one(collections, {
    fields: [products.collectionId],
    references: [collections.id],
  }),
  vendor: one(vendors, {
    fields: [products.vendorId],
    references: [vendors.id],
  }),
  supplier: one(suppliers, {
    fields: [products.supplierId],
    references: [suppliers.id],
  }),
  brandRel: one(brands, {
    fields: [products.brandId],
    references: [brands.id],
  }),
  variants: many(productVariants),
  specs: many(productSpecs),
  relations: many(productRelations),
  orderItems: many(orderItems),
}));

// ==================== CATALOG: COLORS ====================
export const colors = mysqlTable("colors", {
  id: int("id").primaryKey().autoincrement(),
  name: varchar("name", { length: 100 }).notNull().unique(),
  displayName: varchar("display_name", { length: 100 }),
  hex: varchar("hex", { length: 7 }).notNull(),
  status: mysqlEnum("status", ["active", "inactive", "archived"]).default("active").notNull(),
  sortOrder: int("sort_order").default(0).notNull(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

// ==================== CATALOG: SIZES ====================
export const sizes = mysqlTable("sizes", {
  id: int("id").primaryKey().autoincrement(),
  name: varchar("name", { length: 100 }).notNull().unique(),
  type: mysqlEnum("type", ["clothing", "shoes", "general", "custom"]).default("general").notNull(),
  status: mysqlEnum("status", ["active", "inactive", "archived"]).default("active").notNull(),
  sortOrder: int("sort_order").default(0).notNull(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

// ==================== CATALOG: COLLECTIONS ====================
export const collections = mysqlTable("collections", {
  id: int("id").primaryKey().autoincrement(),
  name: varchar("name", { length: 255 }).notNull(),
  slug: varchar("slug", { length: 255 }).notNull().unique(),
  description: text("description"),
  image: varchar("image", { length: 500 }),
  banner: varchar("banner", { length: 500 }),
  featured: boolean("featured").default(false).notNull(),
  homepageVisibility: boolean("homepage_visibility").default(true).notNull(),
  sortOrder: int("sort_order").default(0).notNull(),
  startDate: datetime("start_date"),
  endDate: datetime("end_date"),
  status: mysqlEnum("status", ["active", "inactive", "archived"]).default("active").notNull(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

export const collectionsRelations = relations(collections, ({ many }) => ({
  products: many(products),
}));

// ==================== CATALOG: VENDORS ====================
export const vendors = mysqlTable("vendors", {
  id: int("id").primaryKey().autoincrement(),
  name: varchar("name", { length: 255 }).notNull(),
  slug: varchar("slug", { length: 255 }).notNull().unique(),
  logo: varchar("logo", { length: 500 }),
  description: text("description"),
  contact: varchar("contact", { length: 100 }),
  phone: varchar("phone", { length: 30 }),
  email: varchar("email", { length: 255 }),
  address: varchar("address", { length: 500 }),
  notes: text("notes"),
  status: mysqlEnum("status", ["active", "inactive", "archived"]).default("active").notNull(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

export const vendorsRelations = relations(vendors, ({ many }) => ({
  products: many(products),
}));

// ==================== CATALOG: SUPPLIERS ====================
export const suppliers = mysqlTable("suppliers", {
  id: int("id").primaryKey().autoincrement(),
  name: varchar("name", { length: 255 }).notNull(),
  slug: varchar("slug", { length: 255 }).notNull().unique(),
  logo: varchar("logo", { length: 500 }),
  description: text("description"),
  contact: varchar("contact", { length: 100 }),
  phone: varchar("phone", { length: 30 }),
  email: varchar("email", { length: 255 }),
  address: varchar("address", { length: 500 }),
  notes: text("notes"),
  status: mysqlEnum("status", ["active", "inactive", "archived"]).default("active").notNull(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

export const suppliersRelations = relations(suppliers, ({ many }) => ({
  products: many(products),
}));

// ==================== PRODUCT VARIANTS ====================
export const productVariants = mysqlTable("product_variants", {
  id: int("id").primaryKey().autoincrement(),
  productId: int("product_id")
    .references(() => products.id)
    .notNull(),
  name: varchar("name", { length: 500 }).notNull(),
  options: json("options").$type<Record<string, string>>().notNull(),
  price: decimal("price", { precision: 10, scale: 2 }),
  discountPrice: decimal("discount_price", { precision: 10, scale: 2 }),
  sku: varchar("sku", { length: 100 }),
  barcode: varchar("barcode", { length: 100 }),
  stock: int("stock").default(0).notNull(),
  weight: varchar("weight", { length: 50 }),
  dimensions: varchar("dimensions", { length: 100 }),
  images: json("images").$type<string[]>(),
  thumbnail: varchar("thumbnail", { length: 500 }),
  status: mysqlEnum("status", ["active", "inactive"]).default("active").notNull(),
  shippingCost: decimal("shipping_cost", { precision: 10, scale: 2 }),
  warranty: varchar("warranty", { length: 100 }),
  availability: boolean("availability").default(true).notNull(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

export const productVariantsRelations = relations(productVariants, ({ one }) => ({
  product: one(products, {
    fields: [productVariants.productId],
    references: [products.id],
  }),
}));

// ==================== PRODUCT SPECS ====================
export const productSpecs = mysqlTable("product_specs", {
  id: int("id").primaryKey().autoincrement(),
  productId: int("product_id")
    .references(() => products.id)
    .notNull(),
  label: varchar("label", { length: 255 }).notNull(),
  value: text("value").notNull(),
  sortOrder: int("sort_order").default(0).notNull(),
});

export const productSpecsRelations = relations(productSpecs, ({ one }) => ({
  product: one(products, {
    fields: [productSpecs.productId],
    references: [products.id],
  }),
}));

// ==================== PRODUCT RELATIONS ====================
export const productRelations = mysqlTable("product_relations", {
  id: int("id").primaryKey().autoincrement(),
  productId: int("product_id")
    .references(() => products.id)
    .notNull(),
  relatedProductId: int("related_product_id")
    .references(() => products.id)
    .notNull(),
  type: mysqlEnum("type", [
    "frequently_bought_together",
    "cross_sell",
    "up_sell",
    "accessories",
    "similar",
  ]).notNull(),
});

export const productRelationsRelations = relations(productRelations, ({ one }) => ({
  product: one(products, {
    fields: [productRelations.productId],
    references: [products.id],
  }),
  relatedProduct: one(products, {
    fields: [productRelations.relatedProductId],
    references: [products.id],
  }),
}));

// ==================== BRANDS ====================
export const brands = mysqlTable("brands", {
  id: int("id").primaryKey().autoincrement(),
  name: varchar("name", { length: 255 }).notNull(),
  slug: varchar("slug", { length: 255 }).notNull().unique(),
  logo: varchar("logo", { length: 500 }),
  bannerImage: varchar("banner_image", { length: 500 }),
  description: text("description"),
  website: varchar("website", { length: 500 }),
  countryOfOrigin: varchar("country_of_origin", { length: 100 }),
  featured: boolean("featured").default(false).notNull(),
  homepageVisibility: boolean("homepage_visibility").default(true).notNull(),
  sortOrder: int("sort_order").default(0).notNull(),
  seoTitle: varchar("seo_title", { length: 255 }),
  seoDescription: text("seo_description"),
  seoKeywords: varchar("seo_keywords", { length: 500 }),
  status: mysqlEnum("status", ["active", "inactive", "archived"]).default("active").notNull(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

export const brandsRelations = relations(brands, ({ many }) => ({
  products: many(products),
}));

// ==================== BANNERS ====================
export const banners = mysqlTable("banners", {
  id: int("id").primaryKey().autoincrement(),
  title: varchar("title", { length: 255 }),
  subtitle: varchar("subtitle", { length: 255 }),
  image: varchar("image", { length: 500 }).notNull(),
  imageMobile: varchar("image_mobile", { length: 500 }),
  imageTablet: varchar("image_tablet", { length: 500 }),
  link: varchar("link", { length: 500 }),
  position: mysqlEnum("position", ["hero", "banner", "promo", "sidebar"]).default("hero").notNull(),
  buttonText: varchar("button_text", { length: 100 }),
  priority: int("priority").default(0).notNull(),
  status: mysqlEnum("status", ["active", "inactive"]).default("active").notNull(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
  updatedAt: timestamp("updated_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

// ==================== MEDIA ASSETS ====================
export const mediaAssets = mysqlTable("media_assets", {
  id: int("id").primaryKey().autoincrement(),
  url: varchar("url", { length: 1000 }).notNull(),
  publicId: varchar("public_id", { length: 500 }),
  filename: varchar("filename", { length: 500 }).notNull(),
  mimeType: varchar("mime_type", { length: 100 }).notNull(),
  size: int("size").default(0).notNull(),
  width: int("width"),
  height: int("height"),
  provider: mysqlEnum("provider", ["cloudinary", "local"]).default("local").notNull(),
  folder: varchar("folder", { length: 200 }).default("general").notNull(),
  alt: varchar("alt", { length: 255 }),
  uploaderId: int("uploader_id").references(() => users.id),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

// ==================== ORDERS ====================
export const orders = mysqlTable("orders", {
  id: int("id").primaryKey().autoincrement(),
  orderId: varchar("order_id", { length: 20 }).notNull().unique(),
  userId: int("user_id").references(() => users.id),
  customerName: varchar("customer_name", { length: 255 }).notNull(),
  phone: varchar("phone", { length: 20 }).notNull(),
  alternativePhone: varchar("alternative_phone", { length: 20 }),
  email: varchar("email", { length: 255 }),
  country: varchar("country", { length: 100 }),
  division: varchar("division", { length: 100 }),
  district: varchar("district", { length: 100 }),
  upazila: varchar("upazila", { length: 100 }),
  area: varchar("area", { length: 150 }),
  address: text("address").notNull(),
  apartment: varchar("apartment", { length: 255 }),
  postalCode: varchar("postal_code", { length: 20 }),
  shippingMethodId: int("shipping_method_id"),
  shippingMethodName: varchar("shipping_method_name", { length: 255 }),
  shippingCost: decimal("shipping_cost", { precision: 10, scale: 2 }).notNull(),
  subtotal: decimal("subtotal", { precision: 10, scale: 2 }).default("0"),
  couponCode: varchar("coupon_code", { length: 50 }),
  discount: decimal("discount", { precision: 10, scale: 2 }).default("0"),
  tax: decimal("tax", { precision: 10, scale: 2 }).default("0"),
  orderNote: text("order_note"),
  checkoutNotes: text("checkout_notes"),
  adminNotes: text("admin_notes"),
  totalPrice: decimal("total_price", { precision: 10, scale: 2 }).notNull(),
  paymentMethod: mysqlEnum("payment_method", [
    "cod",
    "bkash",
    "nagad",
    "rocket",
    "bank",
    "stripe",
    "sslcommerz",
    "paypal",
  ]).default("cod").notNull(),
  transactionId: varchar("transaction_id", { length: 100 }),
  senderNumber: varchar("sender_number", { length: 30 }),
  paymentScreenshot: varchar("payment_screenshot", { length: 500 }),
  paymentDate: timestamp("payment_date"),
  amountSent: decimal("amount_sent", { precision: 10, scale: 2 }),
  paymentInstructions: text("payment_instructions"),
  courierTrackingNumber: varchar("courier_tracking_number", { length: 120 }),
  paymentStatus: mysqlEnum("payment_status", [
    "pending",
    "payment_pending",
    "payment_verification",
    "verified",
    "success",
    "failed",
    "rejected",
    "refunded",
  ]).default("pending").notNull(),
  status: mysqlEnum("status", [
    "pending",
    "payment_pending",
    "payment_verification",
    "confirmed",
    "processing",
    "packed",
    "shipped",
    "out_for_delivery",
    "delivered",
    "returned",
    "cancelled",
    "refunded",
  ])
    .default("pending")
    .notNull(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

export const ordersRelations = relations(orders, ({ many }) => ({
  items: many(orderItems),
  statusHistory: many(orderStatusHistory),
}));

export const orderStatusHistory = mysqlTable("order_status_history", {
  id: int("id").primaryKey().autoincrement(),
  orderId: int("order_id")
    .references(() => orders.id)
    .notNull(),
  status: mysqlEnum("status", [
    "pending",
    "payment_pending",
    "payment_verification",
    "confirmed",
    "processing",
    "packed",
    "shipped",
    "out_for_delivery",
    "delivered",
    "returned",
    "cancelled",
    "refunded",
  ]).notNull(),
  note: text("note"),
  createdByUserId: int("created_by_user_id").references(() => users.id),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

export const orderStatusHistoryRelations = relations(orderStatusHistory, ({ one }) => ({
  order: one(orders, {
    fields: [orderStatusHistory.orderId],
    references: [orders.id],
  }),
  createdByUser: one(users, {
    fields: [orderStatusHistory.createdByUserId],
    references: [users.id],
  }),
}));

// ==================== ORDER ITEMS ====================
export const orderItems = mysqlTable("order_items", {
  id: int("id").primaryKey().autoincrement(),
  orderId: int("order_id")
    .references(() => orders.id)
    .notNull(),
  productId: int("product_id")
    .references(() => products.id)
    .notNull(),
  variantId: int("variant_id").references(() => productVariants.id),
  size: varchar("size", { length: 30 }),
  color: varchar("color", { length: 50 }),
  quantity: int("quantity").notNull(),
  price: decimal("price", { precision: 10, scale: 2 }).notNull(),
});

export const orderItemsRelations = relations(orderItems, ({ one }) => ({
  order: one(orders, {
    fields: [orderItems.orderId],
    references: [orders.id],
  }),
  product: one(products, {
    fields: [orderItems.productId],
    references: [products.id],
  }),
}));

// ==================== COUPONS ====================
export const coupons = mysqlTable("coupons", {
  id: int("id").primaryKey().autoincrement(),
  code: varchar("code", { length: 50 }).notNull().unique(),
  discountType: mysqlEnum("discount_type", ["percentage", "fixed"]).notNull(),
  discountValue: decimal("discount_value", { precision: 10, scale: 2 }).notNull(),
  minOrderAmount: decimal("min_order_amount", { precision: 10, scale: 2 }).default("0"),
  expiryDate: timestamp("expiry_date"),
  status: mysqlEnum("status", ["active", "inactive"]).default("active").notNull(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

// ==================== USERS ====================
export const users = mysqlTable("users", {
  id: int("id").primaryKey().autoincrement(),
  name: varchar("name", { length: 255 }).notNull(),
  password: varchar("password", { length: 255 }).notNull(),
  phone: varchar("phone", { length: 20 }).notNull().unique(),
  shippingArea: varchar("shipping_area", { length: 100 }),
  shippingAddress: text("shipping_address"),
  role: mysqlEnum("role", ["admin", "manager", "user"]).default("user").notNull(),
  status: mysqlEnum("status", ["active", "inactive"]).default("active").notNull(),
  resetTokenHash: varchar("reset_token_hash", { length: 255 }),
  resetTokenExpiresAt: timestamp("reset_token_expires_at"),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

export const userAddresses = mysqlTable("user_addresses", {
  id: int("id").primaryKey().autoincrement(),
  userId: int("user_id")
    .references(() => users.id)
    .notNull(),
  recipientName: varchar("recipient_name", { length: 255 }).notNull(),
  phone: varchar("phone", { length: 20 }).notNull(),
  alternativePhone: varchar("alternative_phone", { length: 20 }),
  email: varchar("email", { length: 255 }),
  country: varchar("country", { length: 100 }),
  division: varchar("division", { length: 100 }),
  district: varchar("district", { length: 100 }),
  upazila: varchar("upazila", { length: 100 }),
  area: varchar("area", { length: 150 }),
  shippingArea: varchar("shipping_area", { length: 100 }).notNull(),
  address: text("address").notNull(),
  apartment: varchar("apartment", { length: 255 }),
  postalCode: varchar("postal_code", { length: 20 }),
  isDefault: boolean("is_default").default(false).notNull(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

export const userAddressesRelations = relations(userAddresses, ({ one }) => ({
  user: one(users, {
    fields: [userAddresses.userId],
    references: [users.id],
  }),
}));

export const usersRelations = relations(users, ({ many }) => ({
  orders: many(orders),
  addresses: many(userAddresses),
}));

// ==================== SITE SETTINGS ====================
export const siteSettings = mysqlTable("site_settings", {
  id: int("id").primaryKey().autoincrement(),
  key: varchar("key", { length: 100 }).notNull().unique(),
  value: text("value"),
});

// ==================== POLICY PAGES ====================
export const policyPages = mysqlTable("policy_pages", {
  id: int("id").primaryKey().autoincrement(),
  slug: varchar("slug", { length: 150 }).notNull().unique(),
  title: varchar("title", { length: 200 }).notNull(),
  content: text("content").notNull(),
  status: mysqlEnum("status", ["published", "draft"]).default("published").notNull(),
  lastUpdated: int("last_updated").notNull().default(sql`0`),
  updatedBy: int("updated_by"),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

// ==================== CONTACT MESSAGES ====================
export const contactMessages = mysqlTable("contact_messages", {
  id: int("id").primaryKey().autoincrement(),
  name: varchar("name", { length: 150 }).notNull(),
  phone: varchar("phone", { length: 20 }).notNull(),
  email: varchar("email", { length: 255 }),
  message: text("message").notNull(),
  status: mysqlEnum("status", ["new", "read", "archived"]).default("new").notNull(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

// ==================== TRACKING PIXELS ====================
export const marketingIntegrations = mysqlTable("marketing_integrations", {
  id: int("id").primaryKey().autoincrement(),
  name: varchar("name", { length: 255 }).notNull(),
  type: mysqlEnum("type", [
    "google_tag_manager",
    "google_analytics",
    "facebook_pixel",
    "facebook_conversion_api",
    "tiktok_pixel",
    "custom_script",
  ]).notNull(),
  pixelId: varchar("pixel_id", { length: 255 }),
  scriptCode: text("script_code"),
  accessToken: text("access_token"),
  testEventCode: varchar("test_event_code", { length: 100 }),
  status: mysqlEnum("status", ["active", "inactive"]).default("active").notNull(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
  updatedAt: timestamp("updated_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

// ==================== TRACKING LOGS ====================
export const trackingLogs = mysqlTable("tracking_logs", {
  id: int("id").primaryKey().autoincrement(),
  eventName: varchar("event_name", { length: 100 }).notNull(),
  platform: varchar("platform", { length: 50 }).notNull(),
  payload: json("payload").$type<Record<string, any>>(),
  status: mysqlEnum("status", ["success", "failed"]).default("success").notNull(),
  errorMessage: text("error_message"),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

// ==================== SHIPPING METHODS ====================
export const shippingMethods = mysqlTable("shipping_methods", {
  id: int("id").primaryKey().autoincrement(),
  name: varchar("name", { length: 255 }).notNull(),
  charge: decimal("charge", { precision: 10, scale: 2 }).notNull(),
  estimatedDelivery: varchar("estimated_delivery", { length: 100 }),
  description: text("description"),
  priority: int("priority").default(0).notNull(),
  freeShippingMinAmount: decimal("free_shipping_min_amount", { precision: 10, scale: 2 }),
  codAvailable: boolean("cod_available").default(true).notNull(),
  status: mysqlEnum("status", ["active", "inactive"]).default("active").notNull(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

// ==================== PAYMENT METHODS ====================
export const paymentMethods = mysqlTable("payment_methods", {
  id: int("id").primaryKey().autoincrement(),
  code: varchar("code", { length: 30 }).notNull().unique(),
  name: varchar("name", { length: 100 }).notNull(),
  type: mysqlEnum("type", [
    "cod",
    "mobile_banking",
    "bank",
    "online",
  ]).notNull(),
  enabled: boolean("enabled").default(true).notNull(),
  sortOrder: int("sort_order").default(0).notNull(),
  maintenanceMode: boolean("maintenance_mode").default(false).notNull(),
  config: json("config").$type<Record<string, unknown>>(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
  updatedAt: timestamp("updated_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

// ==================== CHECKOUT NOTICES ====================
export const checkoutNotices = mysqlTable("checkout_notices", {
  id: int("id").primaryKey().autoincrement(),
  text: text("text").notNull(),
  priority: int("priority").default(0).notNull(),
  backgroundColor: varchar("background_color", { length: 50 }).default("#FFF7ED"),
  textColor: varchar("text_color", { length: 50 }).default("#9A3412"),
  icon: varchar("icon", { length: 50 }).default("alert"),
  status: mysqlEnum("status", ["active", "inactive"]).default("active").notNull(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

// ==================== REVIEWS ====================
export const reviews = mysqlTable("reviews", {
  id: int("id").primaryKey().autoincrement(),
  productId: int("product_id").notNull().references(() => products.id),
  userId: int("user_id").references(() => users.id),
  customerName: varchar("customer_name", { length: 255 }),
  rating: int("rating").notNull(),
  title: varchar("title", { length: 255 }),
  comment: text("comment").notNull(),
  status: mysqlEnum("status", ["pending", "approved", "rejected"]).default("pending").notNull(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

export const reviewsRelations = relations(reviews, ({ one }) => ({
  product: one(products, {
    fields: [reviews.productId],
    references: [products.id],
  }),
  user: one(users, {
    fields: [reviews.userId],
    references: [users.id],
  }),
}));

// ==================== NEWSLETTERS ====================
export const newsletters = mysqlTable("newsletters", {
  id: int("id").primaryKey().autoincrement(),
  email: varchar("email", { length: 255 }).notNull().unique(),
  source: varchar("source", { length: 100 }).default("homepage"),
  status: mysqlEnum("status", ["subscribed", "unsubscribed"]).default("subscribed").notNull(),
  subscribedAt: timestamp("subscribed_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

// ==================== EXPENSE CATEGORIES ====================
export const expenseCategories = mysqlTable("expense_categories", {
  id: int("id").primaryKey().autoincrement(),
  name: varchar("name", { length: 255 }).notNull().unique(),
  description: text("description"),
  status: mysqlEnum("status", ["active", "inactive"]).default("active").notNull(),
  sortOrder: int("sort_order").default(0).notNull(),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

export const expenseCategoriesRelations = relations(expenseCategories, ({ many }) => ({
  expenses: many(expenses),
}));

// ==================== EXPENSES ====================
export const expenses = mysqlTable(
  "expenses",
  {
    id: int("id").primaryKey().autoincrement(),
    title: varchar("title", { length: 255 }).notNull(),
    description: text("description"),
    categoryId: int("category_id").references(() => expenseCategories.id),
    amount: decimal("amount", { precision: 12, scale: 2 }).notNull(),
    paymentMethod: varchar("payment_method", { length: 50 }).notNull().default("cash"),
    vendor: varchar("vendor", { length: 255 }),
    memberId: int("member_id").references(() => users.id),
    memberName: varchar("member_name", { length: 255 }),
    expenseDate: datetime("expense_date").notNull(),
    referenceNumber: varchar("reference_number", { length: 100 }),
    attachmentUrl: varchar("attachment_url", { length: 500 }),
    notes: text("notes"),
    status: mysqlEnum("status", ["pending", "approved", "rejected"]).default("approved").notNull(),
    createdById: int("created_by_id").references(() => users.id),
    createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
    updatedAt: timestamp("updated_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
  },
  (table) => ({
    expenseDateIdx: index("expenses_expense_date_idx").on(table.expenseDate),
    memberIdIdx: index("expenses_member_id_idx").on(table.memberId),
    categoryIdIdx: index("expenses_category_id_idx").on(table.categoryId),
    statusIdx: index("expenses_status_idx").on(table.status),
  }),
);

export const expensesRelations = relations(expenses, ({ one }) => ({
  category: one(expenseCategories, {
    fields: [expenses.categoryId],
    references: [expenseCategories.id],
  }),
  member: one(users, {
    fields: [expenses.memberId],
    references: [users.id],
  }),
  createdBy: one(users, {
    fields: [expenses.createdById],
    references: [users.id],
  }),
}));

// ==================== COSTS ====================
export const costs = mysqlTable("costs", {
  id: int("id").primaryKey().autoincrement(),
  title: varchar("title", { length: 255 }).notNull(),
  costType: varchar("cost_type", { length: 100 }).notNull().default("operational"),
  quantity: decimal("quantity", { precision: 12, scale: 2 }).notNull().default("1"),
  unitCost: decimal("unit_cost", { precision: 12, scale: 2 }).notNull().default("0"),
  totalCost: decimal("total_cost", { precision: 12, scale: 2 }).notNull(),
  supplierId: int("supplier_id").references(() => suppliers.id),
  productId: int("product_id").references(() => products.id),
  orderId: int("order_id").references(() => orders.id),
  bookingId: int("booking_id").references(() => bookings.id),
  costDate: datetime("cost_date").notNull(),
  paymentMethod: varchar("payment_method", { length: 50 }).notNull().default("cash"),
  notes: text("notes"),
  attachmentUrl: varchar("attachment_url", { length: 500 }),
  createdById: int("created_by_id").references(() => users.id),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
  updatedAt: timestamp("updated_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

export const costsRelations = relations(costs, ({ one }) => ({
  supplier: one(suppliers, {
    fields: [costs.supplierId],
    references: [suppliers.id],
  }),
  product: one(products, {
    fields: [costs.productId],
    references: [products.id],
  }),
  order: one(orders, {
    fields: [costs.orderId],
    references: [orders.id],
  }),
  booking: one(bookings, {
    fields: [costs.bookingId],
    references: [bookings.id],
  }),
  createdBy: one(users, {
    fields: [costs.createdById],
    references: [users.id],
  }),
}));

// ==================== BOOKINGS ====================
export const bookings = mysqlTable("bookings", {
  id: int("id").primaryKey().autoincrement(),
  customerName: varchar("customer_name", { length: 255 }).notNull(),
  phone: varchar("phone", { length: 20 }).notNull(),
  email: varchar("email", { length: 255 }),
  userId: int("user_id").references(() => users.id),
  bookingType: varchar("booking_type", { length: 100 }).notNull().default("service"),
  service: varchar("service", { length: 255 }),
  productId: int("product_id").references(() => products.id),
  startDate: datetime("start_date").notNull(),
  endDate: datetime("end_date").notNull(),
  quantity: int("quantity").notNull().default(1),
  price: decimal("price", { precision: 12, scale: 2 }).notNull().default("0"),
  discount: decimal("discount", { precision: 12, scale: 2 }).notNull().default("0"),
  additionalCost: decimal("additional_cost", { precision: 12, scale: 2 }).notNull().default("0"),
  totalAmount: decimal("total_amount", { precision: 12, scale: 2 }).notNull(),
  paymentStatus: mysqlEnum("payment_status", ["pending", "partial", "paid", "refunded"]).default("pending").notNull(),
  status: mysqlEnum("status", ["pending", "confirmed", "active", "completed", "cancelled"]).default("pending").notNull(),
  notes: text("notes"),
  attachmentUrl: varchar("attachment_url", { length: 500 }),
  createdById: int("created_by_id").references(() => users.id),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
  updatedAt: timestamp("updated_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

export const bookingsRelations = relations(bookings, ({ one }) => ({
  user: one(users, {
    fields: [bookings.userId],
    references: [users.id],
  }),
  product: one(products, {
    fields: [bookings.productId],
    references: [products.id],
  }),
  createdBy: one(users, {
    fields: [bookings.createdById],
    references: [users.id],
  }),
}));

// ==================== RENTALS ====================
export const rentals = mysqlTable("rentals", {
  id: int("id").primaryKey().autoincrement(),
  rentalItem: varchar("rental_item", { length: 255 }).notNull(),
  productId: int("product_id").references(() => products.id),
  customerName: varchar("customer_name", { length: 255 }).notNull(),
  phone: varchar("phone", { length: 20 }).notNull(),
  email: varchar("email", { length: 255 }),
  userId: int("user_id").references(() => users.id),
  quantity: int("quantity").notNull().default(1),
  startDate: datetime("start_date").notNull(),
  endDate: datetime("end_date").notNull(),
  returnDate: datetime("return_date"),
  rateType: mysqlEnum("rate_type", ["daily", "weekly", "monthly"]).default("daily").notNull(),
  dailyRate: decimal("daily_rate", { precision: 12, scale: 2 }).notNull().default("0"),
  weeklyRate: decimal("weekly_rate", { precision: 12, scale: 2 }).notNull().default("0"),
  monthlyRate: decimal("monthly_rate", { precision: 12, scale: 2 }).notNull().default("0"),
  rate: decimal("rate", { precision: 12, scale: 2 }).notNull().default("0"),
  durationUnits: int("duration_units").notNull().default(0),
  securityDeposit: decimal("security_deposit", { precision: 12, scale: 2 }).notNull().default("0"),
  discount: decimal("discount", { precision: 12, scale: 2 }).notNull().default("0"),
  additionalCharge: decimal("additional_charge", { precision: 12, scale: 2 }).notNull().default("0"),
  totalAmount: decimal("total_amount", { precision: 12, scale: 2 }).notNull(),
  paymentStatus: mysqlEnum("payment_status", ["pending", "partial", "paid", "refunded"]).default("pending").notNull(),
  status: mysqlEnum("status", ["reserved", "rented", "returned", "overdue", "cancelled"]).default("reserved").notNull(),
  notes: text("notes"),
  attachmentUrl: varchar("attachment_url", { length: 500 }),
  createdById: int("created_by_id").references(() => users.id),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
  updatedAt: timestamp("updated_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

export const rentalsRelations = relations(rentals, ({ one }) => ({
  product: one(products, {
    fields: [rentals.productId],
    references: [products.id],
  }),
  user: one(users, {
    fields: [rentals.userId],
    references: [users.id],
  }),
  createdBy: one(users, {
    fields: [rentals.createdById],
    references: [users.id],
  }),
}));

// ==================== MEMOS ====================
export const memos = mysqlTable("memos", {
  id: int("id").primaryKey().autoincrement(),
  title: varchar("title", { length: 255 }),
  entityType: varchar("entity_type", { length: 30 }).notNull(),
  entityId: int("entity_id"),
  url: varchar("url", { length: 1000 }).notNull(),
  publicId: varchar("public_id", { length: 500 }),
  filename: varchar("filename", { length: 500 }).notNull(),
  mimeType: varchar("mime_type", { length: 100 }).notNull(),
  size: int("size").default(0).notNull(),
  folder: varchar("folder", { length: 200 }).default("memos").notNull(),
  notes: text("notes"),
  uploadedById: int("uploaded_by_id").references(() => users.id),
  createdAt: timestamp("created_at").default(sql`CURRENT_TIMESTAMP`).notNull(),
});

export const memosRelations = relations(memos, ({ one }) => ({
  uploadedBy: one(users, {
    fields: [memos.uploadedById],
    references: [users.id],
  }),
}));
