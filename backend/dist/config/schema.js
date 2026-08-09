"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.newsletters = exports.reviewsRelations = exports.reviews = exports.checkoutNotices = exports.paymentMethods = exports.shippingMethods = exports.trackingLogs = exports.marketingIntegrations = exports.siteSettings = exports.usersRelations = exports.userAddressesRelations = exports.userAddresses = exports.users = exports.coupons = exports.orderItemsRelations = exports.orderItems = exports.orderStatusHistoryRelations = exports.orderStatusHistory = exports.ordersRelations = exports.orders = exports.mediaAssets = exports.banners = exports.brandsRelations = exports.brands = exports.productRelationsRelations = exports.productRelations = exports.productSpecsRelations = exports.productSpecs = exports.productVariantsRelations = exports.productVariants = exports.suppliersRelations = exports.suppliers = exports.vendorsRelations = exports.vendors = exports.collectionsRelations = exports.collections = exports.sizes = exports.colors = exports.productsRelations = exports.products = exports.categoriesRelations = exports.categories = void 0;
const mysql_core_1 = require("drizzle-orm/mysql-core");
const drizzle_orm_1 = require("drizzle-orm");
// ==================== CATEGORIES ====================
exports.categories = (0, mysql_core_1.mysqlTable)("categories", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    name: (0, mysql_core_1.varchar)("name", { length: 255 }).notNull(),
    slug: (0, mysql_core_1.varchar)("slug", { length: 255 }).notNull().unique(),
    parentId: (0, mysql_core_1.int)("parent_id").references(() => exports.categories.id),
    image: (0, mysql_core_1.varchar)("image", { length: 500 }),
    icon: (0, mysql_core_1.varchar)("icon", { length: 500 }),
    banner: (0, mysql_core_1.varchar)("banner", { length: 500 }),
    thumbnail: (0, mysql_core_1.varchar)("thumbnail", { length: 500 }),
    description: (0, mysql_core_1.text)("description"),
    featured: (0, mysql_core_1.boolean)("featured").default(false).notNull(),
    sortOrder: (0, mysql_core_1.int)("sort_order").default(0).notNull(),
    homepageVisibility: (0, mysql_core_1.boolean)("homepage_visibility").default(true).notNull(),
    seoTitle: (0, mysql_core_1.varchar)("seo_title", { length: 255 }),
    seoDescription: (0, mysql_core_1.text)("seo_description"),
    seoKeywords: (0, mysql_core_1.varchar)("seo_keywords", { length: 500 }),
    status: (0, mysql_core_1.mysqlEnum)("status", ["active", "inactive", "archived"]).default("active").notNull(),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
});
exports.categoriesRelations = (0, drizzle_orm_1.relations)(exports.categories, ({ many }) => ({
    products: many(exports.products),
}));
// ==================== PRODUCTS ====================
exports.products = (0, mysql_core_1.mysqlTable)("products", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    title: (0, mysql_core_1.varchar)("title", { length: 255 }).notNull(),
    slug: (0, mysql_core_1.varchar)("slug", { length: 255 }).notNull().unique(),
    description: (0, mysql_core_1.text)("description"),
    shortDescription: (0, mysql_core_1.text)("short_description"),
    price: (0, mysql_core_1.decimal)("price", { precision: 10, scale: 2 }).notNull(),
    salePrice: (0, mysql_core_1.decimal)("sale_price", { precision: 10, scale: 2 }),
    discount: (0, mysql_core_1.decimal)("discount", { precision: 10, scale: 2 }).default("0"),
    costPrice: (0, mysql_core_1.decimal)("cost_price", { precision: 10, scale: 2 }).default("0"),
    profitMargin: (0, mysql_core_1.decimal)("profit_margin", { precision: 10, scale: 2 }).default("0"),
    tax: (0, mysql_core_1.decimal)("tax", { precision: 10, scale: 2 }).default("0"),
    vat: (0, mysql_core_1.decimal)("vat", { precision: 10, scale: 2 }).default("0"),
    shippingCharge: (0, mysql_core_1.decimal)("shipping_charge", { precision: 10, scale: 2 }).default("0"),
    codFee: (0, mysql_core_1.decimal)("cod_fee", { precision: 10, scale: 2 }).default("0"),
    flashSalePrice: (0, mysql_core_1.decimal)("flash_sale_price", { precision: 10, scale: 2 }),
    wholesalePrice: (0, mysql_core_1.decimal)("wholesale_price", { precision: 10, scale: 2 }),
    dealerPrice: (0, mysql_core_1.decimal)("dealer_price", { precision: 10, scale: 2 }),
    categoryId: (0, mysql_core_1.int)("category_id").references(() => exports.categories.id),
    subCategoryId: (0, mysql_core_1.int)("sub_category_id"),
    childCategoryId: (0, mysql_core_1.int)("child_category_id"),
    collectionId: (0, mysql_core_1.int)("collection_id").references(() => exports.collections.id),
    brandId: (0, mysql_core_1.int)("brand_id"),
    brand: (0, mysql_core_1.varchar)("brand", { length: 100 }),
    vendorId: (0, mysql_core_1.int)("vendor_id").references(() => exports.vendors.id),
    supplierId: (0, mysql_core_1.int)("supplier_id").references(() => exports.suppliers.id),
    supplier: (0, mysql_core_1.varchar)("supplier", { length: 255 }),
    countryOfOrigin: (0, mysql_core_1.varchar)("country_of_origin", { length: 100 }),
    sku: (0, mysql_core_1.varchar)("sku", { length: 100 }),
    barcode: (0, mysql_core_1.varchar)("barcode", { length: 100 }),
    tags: (0, mysql_core_1.json)("tags").$type(),
    warranty: (0, mysql_core_1.varchar)("warranty", { length: 100 }),
    weight: (0, mysql_core_1.varchar)("weight", { length: 50 }),
    dimensions: (0, mysql_core_1.varchar)("dimensions", { length: 100 }),
    features: (0, mysql_core_1.json)("features").$type(),
    returnPolicy: (0, mysql_core_1.text)("return_policy"),
    warehouse: (0, mysql_core_1.varchar)("warehouse", { length: 255 }),
    videoUrl: (0, mysql_core_1.varchar)("video_url", { length: 500 }),
    seoTitle: (0, mysql_core_1.varchar)("seo_title", { length: 255 }),
    seoDescription: (0, mysql_core_1.text)("seo_description"),
    seoKeywords: (0, mysql_core_1.varchar)("seo_keywords", { length: 500 }),
    canonicalUrl: (0, mysql_core_1.varchar)("canonical_url", { length: 500 }),
    ogImage: (0, mysql_core_1.varchar)("og_image", { length: 500 }),
    twitterImage: (0, mysql_core_1.varchar)("twitter_image", { length: 500 }),
    structuredData: (0, mysql_core_1.json)("structured_data").$type(),
    draft: (0, mysql_core_1.json)("draft").$type(),
    emiAvailable: (0, mysql_core_1.boolean)("emi_available").default(false).notNull(),
    isFeatured: (0, mysql_core_1.boolean)("is_featured").default(false).notNull(),
    isTrending: (0, mysql_core_1.boolean)("is_trending").default(false).notNull(),
    isFlashSale: (0, mysql_core_1.boolean)("is_flash_sale").default(false).notNull(),
    isNewArrival: (0, mysql_core_1.boolean)("is_new_arrival").default(false).notNull(),
    isBestSeller: (0, mysql_core_1.boolean)("is_best_seller").default(false).notNull(),
    isLimitedEdition: (0, mysql_core_1.boolean)("is_limited_edition").default(false).notNull(),
    isOfficial: (0, mysql_core_1.boolean)("is_official").default(false).notNull(),
    isHotDeal: (0, mysql_core_1.boolean)("is_hot_deal").default(false).notNull(),
    isArchived: (0, mysql_core_1.boolean)("is_archived").default(false).notNull(),
    meta: (0, mysql_core_1.json)("meta").$type(),
    stock: (0, mysql_core_1.int)("stock").default(0).notNull(),
    lowStockAlert: (0, mysql_core_1.int)("low_stock_alert").default(10).notNull(),
    minOrder: (0, mysql_core_1.int)("min_order").default(1).notNull(),
    maxOrder: (0, mysql_core_1.int)("max_order"),
    unlimitedStock: (0, mysql_core_1.boolean)("unlimited_stock").default(false).notNull(),
    backorder: (0, mysql_core_1.boolean)("backorder").default(false).notNull(),
    trackInventory: (0, mysql_core_1.boolean)("track_inventory").default(true).notNull(),
    stockStatus: (0, mysql_core_1.varchar)("stock_status", { length: 20 }).default("in_stock"),
    productStatus: (0, mysql_core_1.varchar)("product_status", { length: 30 }).default("published"),
    images: (0, mysql_core_1.json)("images").$type(),
    sizeOptions: (0, mysql_core_1.json)("size_options").$type(),
    colorOptions: (0, mysql_core_1.json)("color_options").$type(),
    paymentMethods: (0, mysql_core_1.json)("payment_methods").$type().default(["cod"]),
    paymentPhoneNumber: (0, mysql_core_1.varchar)("payment_phone_number", { length: 20 }),
    status: (0, mysql_core_1.mysqlEnum)("status", ["active", "inactive"]).default("active").notNull(),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
});
exports.productsRelations = (0, drizzle_orm_1.relations)(exports.products, ({ one, many }) => ({
    category: one(exports.categories, {
        fields: [exports.products.categoryId],
        references: [exports.categories.id],
    }),
    subCategory: one(exports.categories, {
        fields: [exports.products.subCategoryId],
        references: [exports.categories.id],
    }),
    childCategory: one(exports.categories, {
        fields: [exports.products.childCategoryId],
        references: [exports.categories.id],
    }),
    collection: one(exports.collections, {
        fields: [exports.products.collectionId],
        references: [exports.collections.id],
    }),
    vendor: one(exports.vendors, {
        fields: [exports.products.vendorId],
        references: [exports.vendors.id],
    }),
    supplier: one(exports.suppliers, {
        fields: [exports.products.supplierId],
        references: [exports.suppliers.id],
    }),
    brandRel: one(exports.brands, {
        fields: [exports.products.brandId],
        references: [exports.brands.id],
    }),
    variants: many(exports.productVariants),
    specs: many(exports.productSpecs),
    relations: many(exports.productRelations),
    orderItems: many(exports.orderItems),
}));
// ==================== CATALOG: COLORS ====================
exports.colors = (0, mysql_core_1.mysqlTable)("colors", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    name: (0, mysql_core_1.varchar)("name", { length: 100 }).notNull().unique(),
    displayName: (0, mysql_core_1.varchar)("display_name", { length: 100 }),
    hex: (0, mysql_core_1.varchar)("hex", { length: 7 }).notNull(),
    status: (0, mysql_core_1.mysqlEnum)("status", ["active", "inactive", "archived"]).default("active").notNull(),
    sortOrder: (0, mysql_core_1.int)("sort_order").default(0).notNull(),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
});
// ==================== CATALOG: SIZES ====================
exports.sizes = (0, mysql_core_1.mysqlTable)("sizes", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    name: (0, mysql_core_1.varchar)("name", { length: 100 }).notNull().unique(),
    type: (0, mysql_core_1.mysqlEnum)("type", ["clothing", "shoes", "general", "custom"]).default("general").notNull(),
    status: (0, mysql_core_1.mysqlEnum)("status", ["active", "inactive", "archived"]).default("active").notNull(),
    sortOrder: (0, mysql_core_1.int)("sort_order").default(0).notNull(),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
});
// ==================== CATALOG: COLLECTIONS ====================
exports.collections = (0, mysql_core_1.mysqlTable)("collections", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    name: (0, mysql_core_1.varchar)("name", { length: 255 }).notNull(),
    slug: (0, mysql_core_1.varchar)("slug", { length: 255 }).notNull().unique(),
    description: (0, mysql_core_1.text)("description"),
    image: (0, mysql_core_1.varchar)("image", { length: 500 }),
    banner: (0, mysql_core_1.varchar)("banner", { length: 500 }),
    featured: (0, mysql_core_1.boolean)("featured").default(false).notNull(),
    homepageVisibility: (0, mysql_core_1.boolean)("homepage_visibility").default(true).notNull(),
    sortOrder: (0, mysql_core_1.int)("sort_order").default(0).notNull(),
    startDate: (0, mysql_core_1.datetime)("start_date"),
    endDate: (0, mysql_core_1.datetime)("end_date"),
    status: (0, mysql_core_1.mysqlEnum)("status", ["active", "inactive", "archived"]).default("active").notNull(),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
});
exports.collectionsRelations = (0, drizzle_orm_1.relations)(exports.collections, ({ many }) => ({
    products: many(exports.products),
}));
// ==================== CATALOG: VENDORS ====================
exports.vendors = (0, mysql_core_1.mysqlTable)("vendors", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    name: (0, mysql_core_1.varchar)("name", { length: 255 }).notNull(),
    slug: (0, mysql_core_1.varchar)("slug", { length: 255 }).notNull().unique(),
    logo: (0, mysql_core_1.varchar)("logo", { length: 500 }),
    description: (0, mysql_core_1.text)("description"),
    contact: (0, mysql_core_1.varchar)("contact", { length: 100 }),
    phone: (0, mysql_core_1.varchar)("phone", { length: 30 }),
    email: (0, mysql_core_1.varchar)("email", { length: 255 }),
    address: (0, mysql_core_1.varchar)("address", { length: 500 }),
    notes: (0, mysql_core_1.text)("notes"),
    status: (0, mysql_core_1.mysqlEnum)("status", ["active", "inactive", "archived"]).default("active").notNull(),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
});
exports.vendorsRelations = (0, drizzle_orm_1.relations)(exports.vendors, ({ many }) => ({
    products: many(exports.products),
}));
// ==================== CATALOG: SUPPLIERS ====================
exports.suppliers = (0, mysql_core_1.mysqlTable)("suppliers", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    name: (0, mysql_core_1.varchar)("name", { length: 255 }).notNull(),
    slug: (0, mysql_core_1.varchar)("slug", { length: 255 }).notNull().unique(),
    logo: (0, mysql_core_1.varchar)("logo", { length: 500 }),
    description: (0, mysql_core_1.text)("description"),
    contact: (0, mysql_core_1.varchar)("contact", { length: 100 }),
    phone: (0, mysql_core_1.varchar)("phone", { length: 30 }),
    email: (0, mysql_core_1.varchar)("email", { length: 255 }),
    address: (0, mysql_core_1.varchar)("address", { length: 500 }),
    notes: (0, mysql_core_1.text)("notes"),
    status: (0, mysql_core_1.mysqlEnum)("status", ["active", "inactive", "archived"]).default("active").notNull(),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
});
exports.suppliersRelations = (0, drizzle_orm_1.relations)(exports.suppliers, ({ many }) => ({
    products: many(exports.products),
}));
// ==================== PRODUCT VARIANTS ====================
exports.productVariants = (0, mysql_core_1.mysqlTable)("product_variants", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    productId: (0, mysql_core_1.int)("product_id")
        .references(() => exports.products.id)
        .notNull(),
    name: (0, mysql_core_1.varchar)("name", { length: 500 }).notNull(),
    options: (0, mysql_core_1.json)("options").$type().notNull(),
    price: (0, mysql_core_1.decimal)("price", { precision: 10, scale: 2 }),
    discountPrice: (0, mysql_core_1.decimal)("discount_price", { precision: 10, scale: 2 }),
    sku: (0, mysql_core_1.varchar)("sku", { length: 100 }),
    barcode: (0, mysql_core_1.varchar)("barcode", { length: 100 }),
    stock: (0, mysql_core_1.int)("stock").default(0).notNull(),
    weight: (0, mysql_core_1.varchar)("weight", { length: 50 }),
    dimensions: (0, mysql_core_1.varchar)("dimensions", { length: 100 }),
    images: (0, mysql_core_1.json)("images").$type(),
    thumbnail: (0, mysql_core_1.varchar)("thumbnail", { length: 500 }),
    status: (0, mysql_core_1.mysqlEnum)("status", ["active", "inactive"]).default("active").notNull(),
    shippingCost: (0, mysql_core_1.decimal)("shipping_cost", { precision: 10, scale: 2 }),
    warranty: (0, mysql_core_1.varchar)("warranty", { length: 100 }),
    availability: (0, mysql_core_1.boolean)("availability").default(true).notNull(),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
});
exports.productVariantsRelations = (0, drizzle_orm_1.relations)(exports.productVariants, ({ one }) => ({
    product: one(exports.products, {
        fields: [exports.productVariants.productId],
        references: [exports.products.id],
    }),
}));
// ==================== PRODUCT SPECS ====================
exports.productSpecs = (0, mysql_core_1.mysqlTable)("product_specs", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    productId: (0, mysql_core_1.int)("product_id")
        .references(() => exports.products.id)
        .notNull(),
    label: (0, mysql_core_1.varchar)("label", { length: 255 }).notNull(),
    value: (0, mysql_core_1.text)("value").notNull(),
    sortOrder: (0, mysql_core_1.int)("sort_order").default(0).notNull(),
});
exports.productSpecsRelations = (0, drizzle_orm_1.relations)(exports.productSpecs, ({ one }) => ({
    product: one(exports.products, {
        fields: [exports.productSpecs.productId],
        references: [exports.products.id],
    }),
}));
// ==================== PRODUCT RELATIONS ====================
exports.productRelations = (0, mysql_core_1.mysqlTable)("product_relations", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    productId: (0, mysql_core_1.int)("product_id")
        .references(() => exports.products.id)
        .notNull(),
    relatedProductId: (0, mysql_core_1.int)("related_product_id")
        .references(() => exports.products.id)
        .notNull(),
    type: (0, mysql_core_1.mysqlEnum)("type", [
        "frequently_bought_together",
        "cross_sell",
        "up_sell",
        "accessories",
        "similar",
    ]).notNull(),
});
exports.productRelationsRelations = (0, drizzle_orm_1.relations)(exports.productRelations, ({ one }) => ({
    product: one(exports.products, {
        fields: [exports.productRelations.productId],
        references: [exports.products.id],
    }),
    relatedProduct: one(exports.products, {
        fields: [exports.productRelations.relatedProductId],
        references: [exports.products.id],
    }),
}));
// ==================== BRANDS ====================
exports.brands = (0, mysql_core_1.mysqlTable)("brands", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    name: (0, mysql_core_1.varchar)("name", { length: 255 }).notNull(),
    slug: (0, mysql_core_1.varchar)("slug", { length: 255 }).notNull().unique(),
    logo: (0, mysql_core_1.varchar)("logo", { length: 500 }),
    bannerImage: (0, mysql_core_1.varchar)("banner_image", { length: 500 }),
    description: (0, mysql_core_1.text)("description"),
    website: (0, mysql_core_1.varchar)("website", { length: 500 }),
    countryOfOrigin: (0, mysql_core_1.varchar)("country_of_origin", { length: 100 }),
    featured: (0, mysql_core_1.boolean)("featured").default(false).notNull(),
    homepageVisibility: (0, mysql_core_1.boolean)("homepage_visibility").default(true).notNull(),
    sortOrder: (0, mysql_core_1.int)("sort_order").default(0).notNull(),
    seoTitle: (0, mysql_core_1.varchar)("seo_title", { length: 255 }),
    seoDescription: (0, mysql_core_1.text)("seo_description"),
    seoKeywords: (0, mysql_core_1.varchar)("seo_keywords", { length: 500 }),
    status: (0, mysql_core_1.mysqlEnum)("status", ["active", "inactive", "archived"]).default("active").notNull(),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
});
exports.brandsRelations = (0, drizzle_orm_1.relations)(exports.brands, ({ many }) => ({
    products: many(exports.products),
}));
// ==================== BANNERS ====================
exports.banners = (0, mysql_core_1.mysqlTable)("banners", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    title: (0, mysql_core_1.varchar)("title", { length: 255 }),
    subtitle: (0, mysql_core_1.varchar)("subtitle", { length: 255 }),
    image: (0, mysql_core_1.varchar)("image", { length: 500 }).notNull(),
    imageMobile: (0, mysql_core_1.varchar)("image_mobile", { length: 500 }),
    imageTablet: (0, mysql_core_1.varchar)("image_tablet", { length: 500 }),
    link: (0, mysql_core_1.varchar)("link", { length: 500 }),
    position: (0, mysql_core_1.mysqlEnum)("position", ["hero", "banner", "promo", "sidebar"]).default("hero").notNull(),
    buttonText: (0, mysql_core_1.varchar)("button_text", { length: 100 }),
    priority: (0, mysql_core_1.int)("priority").default(0).notNull(),
    status: (0, mysql_core_1.mysqlEnum)("status", ["active", "inactive"]).default("active").notNull(),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
    updatedAt: (0, mysql_core_1.timestamp)("updated_at").defaultNow().notNull(),
});
// ==================== MEDIA ASSETS ====================
exports.mediaAssets = (0, mysql_core_1.mysqlTable)("media_assets", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    url: (0, mysql_core_1.varchar)("url", { length: 1000 }).notNull(),
    publicId: (0, mysql_core_1.varchar)("public_id", { length: 500 }),
    filename: (0, mysql_core_1.varchar)("filename", { length: 500 }).notNull(),
    mimeType: (0, mysql_core_1.varchar)("mime_type", { length: 100 }).notNull(),
    size: (0, mysql_core_1.int)("size").default(0).notNull(),
    width: (0, mysql_core_1.int)("width"),
    height: (0, mysql_core_1.int)("height"),
    provider: (0, mysql_core_1.mysqlEnum)("provider", ["cloudinary", "local"]).default("local").notNull(),
    folder: (0, mysql_core_1.varchar)("folder", { length: 200 }).default("general").notNull(),
    alt: (0, mysql_core_1.varchar)("alt", { length: 255 }),
    uploaderId: (0, mysql_core_1.int)("uploader_id").references(() => exports.users.id),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
});
// ==================== ORDERS ====================
exports.orders = (0, mysql_core_1.mysqlTable)("orders", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    orderId: (0, mysql_core_1.varchar)("order_id", { length: 20 }).notNull().unique(),
    userId: (0, mysql_core_1.int)("user_id").references(() => exports.users.id),
    customerName: (0, mysql_core_1.varchar)("customer_name", { length: 255 }).notNull(),
    phone: (0, mysql_core_1.varchar)("phone", { length: 20 }).notNull(),
    alternativePhone: (0, mysql_core_1.varchar)("alternative_phone", { length: 20 }),
    email: (0, mysql_core_1.varchar)("email", { length: 255 }),
    country: (0, mysql_core_1.varchar)("country", { length: 100 }),
    division: (0, mysql_core_1.varchar)("division", { length: 100 }),
    district: (0, mysql_core_1.varchar)("district", { length: 100 }),
    upazila: (0, mysql_core_1.varchar)("upazila", { length: 100 }),
    area: (0, mysql_core_1.varchar)("area", { length: 150 }),
    address: (0, mysql_core_1.text)("address").notNull(),
    apartment: (0, mysql_core_1.varchar)("apartment", { length: 255 }),
    postalCode: (0, mysql_core_1.varchar)("postal_code", { length: 20 }),
    shippingMethodId: (0, mysql_core_1.int)("shipping_method_id"),
    shippingMethodName: (0, mysql_core_1.varchar)("shipping_method_name", { length: 255 }),
    shippingCost: (0, mysql_core_1.decimal)("shipping_cost", { precision: 10, scale: 2 }).notNull(),
    subtotal: (0, mysql_core_1.decimal)("subtotal", { precision: 10, scale: 2 }).default("0"),
    couponCode: (0, mysql_core_1.varchar)("coupon_code", { length: 50 }),
    discount: (0, mysql_core_1.decimal)("discount", { precision: 10, scale: 2 }).default("0"),
    tax: (0, mysql_core_1.decimal)("tax", { precision: 10, scale: 2 }).default("0"),
    orderNote: (0, mysql_core_1.text)("order_note"),
    checkoutNotes: (0, mysql_core_1.text)("checkout_notes"),
    adminNotes: (0, mysql_core_1.text)("admin_notes"),
    totalPrice: (0, mysql_core_1.decimal)("total_price", { precision: 10, scale: 2 }).notNull(),
    paymentMethod: (0, mysql_core_1.mysqlEnum)("payment_method", [
        "cod",
        "bkash",
        "nagad",
        "rocket",
        "bank",
        "stripe",
        "sslcommerz",
        "paypal",
    ]).default("cod").notNull(),
    transactionId: (0, mysql_core_1.varchar)("transaction_id", { length: 100 }),
    senderNumber: (0, mysql_core_1.varchar)("sender_number", { length: 30 }),
    paymentScreenshot: (0, mysql_core_1.varchar)("payment_screenshot", { length: 500 }),
    paymentDate: (0, mysql_core_1.timestamp)("payment_date"),
    amountSent: (0, mysql_core_1.decimal)("amount_sent", { precision: 10, scale: 2 }),
    paymentInstructions: (0, mysql_core_1.text)("payment_instructions"),
    courierTrackingNumber: (0, mysql_core_1.varchar)("courier_tracking_number", { length: 120 }),
    paymentStatus: (0, mysql_core_1.mysqlEnum)("payment_status", [
        "pending",
        "payment_pending",
        "payment_verification",
        "verified",
        "success",
        "failed",
        "rejected",
        "refunded",
    ]).default("pending").notNull(),
    status: (0, mysql_core_1.mysqlEnum)("status", [
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
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
});
exports.ordersRelations = (0, drizzle_orm_1.relations)(exports.orders, ({ many }) => ({
    items: many(exports.orderItems),
    statusHistory: many(exports.orderStatusHistory),
}));
exports.orderStatusHistory = (0, mysql_core_1.mysqlTable)("order_status_history", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    orderId: (0, mysql_core_1.int)("order_id")
        .references(() => exports.orders.id)
        .notNull(),
    status: (0, mysql_core_1.mysqlEnum)("status", [
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
    note: (0, mysql_core_1.text)("note"),
    createdByUserId: (0, mysql_core_1.int)("created_by_user_id").references(() => exports.users.id),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
});
exports.orderStatusHistoryRelations = (0, drizzle_orm_1.relations)(exports.orderStatusHistory, ({ one }) => ({
    order: one(exports.orders, {
        fields: [exports.orderStatusHistory.orderId],
        references: [exports.orders.id],
    }),
    createdByUser: one(exports.users, {
        fields: [exports.orderStatusHistory.createdByUserId],
        references: [exports.users.id],
    }),
}));
// ==================== ORDER ITEMS ====================
exports.orderItems = (0, mysql_core_1.mysqlTable)("order_items", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    orderId: (0, mysql_core_1.int)("order_id")
        .references(() => exports.orders.id)
        .notNull(),
    productId: (0, mysql_core_1.int)("product_id")
        .references(() => exports.products.id)
        .notNull(),
    size: (0, mysql_core_1.varchar)("size", { length: 30 }),
    color: (0, mysql_core_1.varchar)("color", { length: 50 }),
    quantity: (0, mysql_core_1.int)("quantity").notNull(),
    price: (0, mysql_core_1.decimal)("price", { precision: 10, scale: 2 }).notNull(),
});
exports.orderItemsRelations = (0, drizzle_orm_1.relations)(exports.orderItems, ({ one }) => ({
    order: one(exports.orders, {
        fields: [exports.orderItems.orderId],
        references: [exports.orders.id],
    }),
    product: one(exports.products, {
        fields: [exports.orderItems.productId],
        references: [exports.products.id],
    }),
}));
// ==================== COUPONS ====================
exports.coupons = (0, mysql_core_1.mysqlTable)("coupons", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    code: (0, mysql_core_1.varchar)("code", { length: 50 }).notNull().unique(),
    discountType: (0, mysql_core_1.mysqlEnum)("discount_type", ["percentage", "fixed"]).notNull(),
    discountValue: (0, mysql_core_1.decimal)("discount_value", { precision: 10, scale: 2 }).notNull(),
    minOrderAmount: (0, mysql_core_1.decimal)("min_order_amount", { precision: 10, scale: 2 }).default("0"),
    expiryDate: (0, mysql_core_1.timestamp)("expiry_date"),
    status: (0, mysql_core_1.mysqlEnum)("status", ["active", "inactive"]).default("active").notNull(),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
});
// ==================== USERS ====================
exports.users = (0, mysql_core_1.mysqlTable)("users", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    name: (0, mysql_core_1.varchar)("name", { length: 255 }).notNull(),
    password: (0, mysql_core_1.varchar)("password", { length: 255 }).notNull(),
    phone: (0, mysql_core_1.varchar)("phone", { length: 20 }).notNull().unique(),
    shippingArea: (0, mysql_core_1.varchar)("shipping_area", { length: 100 }),
    shippingAddress: (0, mysql_core_1.text)("shipping_address"),
    role: (0, mysql_core_1.mysqlEnum)("role", ["admin", "manager", "user"]).default("user").notNull(),
    resetTokenHash: (0, mysql_core_1.varchar)("reset_token_hash", { length: 255 }),
    resetTokenExpiresAt: (0, mysql_core_1.timestamp)("reset_token_expires_at"),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
});
exports.userAddresses = (0, mysql_core_1.mysqlTable)("user_addresses", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    userId: (0, mysql_core_1.int)("user_id")
        .references(() => exports.users.id)
        .notNull(),
    recipientName: (0, mysql_core_1.varchar)("recipient_name", { length: 255 }).notNull(),
    phone: (0, mysql_core_1.varchar)("phone", { length: 20 }).notNull(),
    alternativePhone: (0, mysql_core_1.varchar)("alternative_phone", { length: 20 }),
    email: (0, mysql_core_1.varchar)("email", { length: 255 }),
    country: (0, mysql_core_1.varchar)("country", { length: 100 }),
    division: (0, mysql_core_1.varchar)("division", { length: 100 }),
    district: (0, mysql_core_1.varchar)("district", { length: 100 }),
    upazila: (0, mysql_core_1.varchar)("upazila", { length: 100 }),
    area: (0, mysql_core_1.varchar)("area", { length: 150 }),
    shippingArea: (0, mysql_core_1.varchar)("shipping_area", { length: 100 }).notNull(),
    address: (0, mysql_core_1.text)("address").notNull(),
    apartment: (0, mysql_core_1.varchar)("apartment", { length: 255 }),
    postalCode: (0, mysql_core_1.varchar)("postal_code", { length: 20 }),
    isDefault: (0, mysql_core_1.boolean)("is_default").default(false).notNull(),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
});
exports.userAddressesRelations = (0, drizzle_orm_1.relations)(exports.userAddresses, ({ one }) => ({
    user: one(exports.users, {
        fields: [exports.userAddresses.userId],
        references: [exports.users.id],
    }),
}));
exports.usersRelations = (0, drizzle_orm_1.relations)(exports.users, ({ many }) => ({
    orders: many(exports.orders),
    addresses: many(exports.userAddresses),
}));
// ==================== SITE SETTINGS ====================
exports.siteSettings = (0, mysql_core_1.mysqlTable)("site_settings", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    key: (0, mysql_core_1.varchar)("key", { length: 100 }).notNull().unique(),
    value: (0, mysql_core_1.text)("value"),
});
// ==================== TRACKING PIXELS ====================
exports.marketingIntegrations = (0, mysql_core_1.mysqlTable)("marketing_integrations", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    name: (0, mysql_core_1.varchar)("name", { length: 255 }).notNull(),
    type: (0, mysql_core_1.mysqlEnum)("type", [
        "google_tag_manager",
        "google_analytics",
        "facebook_pixel",
        "facebook_conversion_api",
        "tiktok_pixel",
        "custom_script",
    ]).notNull(),
    pixelId: (0, mysql_core_1.varchar)("pixel_id", { length: 255 }),
    scriptCode: (0, mysql_core_1.text)("script_code"),
    accessToken: (0, mysql_core_1.text)("access_token"),
    testEventCode: (0, mysql_core_1.varchar)("test_event_code", { length: 100 }),
    status: (0, mysql_core_1.mysqlEnum)("status", ["active", "inactive"]).default("active").notNull(),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
    updatedAt: (0, mysql_core_1.timestamp)("updated_at").defaultNow().notNull(),
});
// ==================== TRACKING LOGS ====================
exports.trackingLogs = (0, mysql_core_1.mysqlTable)("tracking_logs", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    eventName: (0, mysql_core_1.varchar)("event_name", { length: 100 }).notNull(),
    platform: (0, mysql_core_1.varchar)("platform", { length: 50 }).notNull(),
    payload: (0, mysql_core_1.json)("payload").$type(),
    status: (0, mysql_core_1.mysqlEnum)("status", ["success", "failed"]).default("success").notNull(),
    errorMessage: (0, mysql_core_1.text)("error_message"),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
});
// ==================== SHIPPING METHODS ====================
exports.shippingMethods = (0, mysql_core_1.mysqlTable)("shipping_methods", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    name: (0, mysql_core_1.varchar)("name", { length: 255 }).notNull(),
    charge: (0, mysql_core_1.decimal)("charge", { precision: 10, scale: 2 }).notNull(),
    estimatedDelivery: (0, mysql_core_1.varchar)("estimated_delivery", { length: 100 }),
    description: (0, mysql_core_1.text)("description"),
    priority: (0, mysql_core_1.int)("priority").default(0).notNull(),
    freeShippingMinAmount: (0, mysql_core_1.decimal)("free_shipping_min_amount", { precision: 10, scale: 2 }),
    codAvailable: (0, mysql_core_1.boolean)("cod_available").default(true).notNull(),
    status: (0, mysql_core_1.mysqlEnum)("status", ["active", "inactive"]).default("active").notNull(),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
});
// ==================== PAYMENT METHODS ====================
exports.paymentMethods = (0, mysql_core_1.mysqlTable)("payment_methods", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    code: (0, mysql_core_1.varchar)("code", { length: 30 }).notNull().unique(),
    name: (0, mysql_core_1.varchar)("name", { length: 100 }).notNull(),
    type: (0, mysql_core_1.mysqlEnum)("type", [
        "cod",
        "mobile_banking",
        "bank",
        "online",
    ]).notNull(),
    enabled: (0, mysql_core_1.boolean)("enabled").default(true).notNull(),
    sortOrder: (0, mysql_core_1.int)("sort_order").default(0).notNull(),
    maintenanceMode: (0, mysql_core_1.boolean)("maintenance_mode").default(false).notNull(),
    config: (0, mysql_core_1.json)("config").$type(),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
    updatedAt: (0, mysql_core_1.timestamp)("updated_at").defaultNow().notNull(),
});
// ==================== CHECKOUT NOTICES ====================
exports.checkoutNotices = (0, mysql_core_1.mysqlTable)("checkout_notices", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    text: (0, mysql_core_1.text)("text").notNull(),
    priority: (0, mysql_core_1.int)("priority").default(0).notNull(),
    backgroundColor: (0, mysql_core_1.varchar)("background_color", { length: 50 }).default("#FFF7ED"),
    textColor: (0, mysql_core_1.varchar)("text_color", { length: 50 }).default("#9A3412"),
    icon: (0, mysql_core_1.varchar)("icon", { length: 50 }).default("alert"),
    status: (0, mysql_core_1.mysqlEnum)("status", ["active", "inactive"]).default("active").notNull(),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
});
// ==================== REVIEWS ====================
exports.reviews = (0, mysql_core_1.mysqlTable)("reviews", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    productId: (0, mysql_core_1.int)("product_id").notNull().references(() => exports.products.id),
    userId: (0, mysql_core_1.int)("user_id").references(() => exports.users.id),
    customerName: (0, mysql_core_1.varchar)("customer_name", { length: 255 }),
    rating: (0, mysql_core_1.int)("rating").notNull(),
    title: (0, mysql_core_1.varchar)("title", { length: 255 }),
    comment: (0, mysql_core_1.text)("comment").notNull(),
    status: (0, mysql_core_1.mysqlEnum)("status", ["pending", "approved", "rejected"]).default("pending").notNull(),
    createdAt: (0, mysql_core_1.timestamp)("created_at").defaultNow().notNull(),
});
exports.reviewsRelations = (0, drizzle_orm_1.relations)(exports.reviews, ({ one }) => ({
    product: one(exports.products, {
        fields: [exports.reviews.productId],
        references: [exports.products.id],
    }),
    user: one(exports.users, {
        fields: [exports.reviews.userId],
        references: [exports.users.id],
    }),
}));
// ==================== NEWSLETTERS ====================
exports.newsletters = (0, mysql_core_1.mysqlTable)("newsletters", {
    id: (0, mysql_core_1.int)("id").primaryKey().autoincrement(),
    email: (0, mysql_core_1.varchar)("email", { length: 255 }).notNull().unique(),
    source: (0, mysql_core_1.varchar)("source", { length: 100 }).default("homepage"),
    status: (0, mysql_core_1.mysqlEnum)("status", ["subscribed", "unsubscribed"]).default("subscribed").notNull(),
    subscribedAt: (0, mysql_core_1.timestamp)("subscribed_at").defaultNow().notNull(),
});
//# sourceMappingURL=schema.js.map