"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.importCsv = exports.exportCsv = exports.autoSaveDraft = exports.duplicate = exports.bulkAction = exports.remove = exports.update = exports.create = exports.getRelated = exports.getBySlug = exports.getById = exports.getAll = exports.fullQuery = exports.fetchProductRowsOnly = exports.formatHomepageProduct = exports.fetchRatingMap = exports.formatProductRow = exports.deriveStockStatus = exports.deriveProfitMargin = exports.ensureUniqueSlug = void 0;
const db_1 = require("../../config/db");
const AppError_1 = require("../../utils/AppError");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const cache_1 = require("../../utils/cache");
const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 12;
const DEFAULT_STATUS = "active";
const RELATED_LIMIT = 8;
/**
 * Ensure a slug is unique before inserting/updating.
 * - `autoSuffix: true`  — generated slugs: append `-2`, `-3`, … until free.
 * - `autoSuffix: false` — user-provided slug: throw a 409 with a clear message.
 */
const ensureUniqueSlug = async (slug, opts = {}) => {
    let candidate = slug;
    let suffix = 2;
    for (;;) {
        const existing = await db_1.db
            .select({ id: schema_1.products.id })
            .from(schema_1.products)
            .where((0, drizzle_orm_1.eq)(schema_1.products.slug, candidate))
            .limit(1);
        const isFree = !existing.length || existing[0].id === opts.excludeId;
        if (isFree)
            return candidate;
        if (!opts.autoSuffix) {
            throw new AppError_1.AppError(409, `Slug "${slug}" is already in use by another product. Please choose a different slug.`);
        }
        candidate = `${slug}-${suffix++}`;
        if (suffix > 1000)
            return candidate;
    }
};
exports.ensureUniqueSlug = ensureUniqueSlug;
const toNum = (v) => {
    if (v === undefined || v === null || v === "")
        return null;
    const n = Number(v);
    return Number.isNaN(n) ? null : n;
};
const toStr = (v) => {
    if (v === undefined || v === null || v === "")
        return null;
    return String(v);
};
const deriveProfitMargin = (selling, cost) => {
    const s = toNum(selling) ?? 0;
    const c = toNum(cost) ?? 0;
    if (!s || s <= 0)
        return "0";
    return String(Math.round(((s - c) / s) * 100 * 100) / 100);
};
exports.deriveProfitMargin = deriveProfitMargin;
const deriveStockStatus = (stock, lowStockAlert, unlimited, explicit) => {
    if (explicit)
        return explicit;
    if (unlimited)
        return "in_stock";
    if (stock <= 0)
        return "out_of_stock";
    if (lowStockAlert !== null && stock <= lowStockAlert)
        return "low_stock";
    return "in_stock";
};
exports.deriveStockStatus = deriveStockStatus;
const deriveStatusFromProductStatus = (productStatus, status) => {
    if (productStatus === "published")
        return "active";
    if (productStatus && productStatus !== "published")
        return "inactive";
    return status || DEFAULT_STATUS;
};
const productColumns = {
    id: schema_1.products.id,
    title: schema_1.products.title,
    slug: schema_1.products.slug,
    description: schema_1.products.description,
    shortDescription: schema_1.products.shortDescription,
    price: schema_1.products.price,
    salePrice: schema_1.products.salePrice,
    discount: schema_1.products.discount,
    costPrice: schema_1.products.costPrice,
    profitMargin: schema_1.products.profitMargin,
    tax: schema_1.products.tax,
    vat: schema_1.products.vat,
    shippingCharge: schema_1.products.shippingCharge,
    codFee: schema_1.products.codFee,
    flashSalePrice: schema_1.products.flashSalePrice,
    wholesalePrice: schema_1.products.wholesalePrice,
    dealerPrice: schema_1.products.dealerPrice,
    categoryId: schema_1.products.categoryId,
    subCategoryId: schema_1.products.subCategoryId,
    childCategoryId: schema_1.products.childCategoryId,
    collectionId: schema_1.products.collectionId,
    brandId: schema_1.products.brandId,
    brand: schema_1.products.brand,
    vendorId: schema_1.products.vendorId,
    supplierId: schema_1.products.supplierId,
    supplier: schema_1.products.supplier,
    countryOfOrigin: schema_1.products.countryOfOrigin,
    sku: schema_1.products.sku,
    barcode: schema_1.products.barcode,
    tags: schema_1.products.tags,
    warranty: schema_1.products.warranty,
    weight: schema_1.products.weight,
    dimensions: schema_1.products.dimensions,
    features: schema_1.products.features,
    returnPolicy: schema_1.products.returnPolicy,
    warehouse: schema_1.products.warehouse,
    videoUrl: schema_1.products.videoUrl,
    seoTitle: schema_1.products.seoTitle,
    seoDescription: schema_1.products.seoDescription,
    seoKeywords: schema_1.products.seoKeywords,
    canonicalUrl: schema_1.products.canonicalUrl,
    ogImage: schema_1.products.ogImage,
    twitterImage: schema_1.products.twitterImage,
    structuredData: schema_1.products.structuredData,
    emiAvailable: schema_1.products.emiAvailable,
    isFeatured: schema_1.products.isFeatured,
    isTrending: schema_1.products.isTrending,
    isFlashSale: schema_1.products.isFlashSale,
    isNewArrival: schema_1.products.isNewArrival,
    isBestSeller: schema_1.products.isBestSeller,
    isLimitedEdition: schema_1.products.isLimitedEdition,
    isOfficial: schema_1.products.isOfficial,
    isHotDeal: schema_1.products.isHotDeal,
    isArchived: schema_1.products.isArchived,
    meta: schema_1.products.meta,
    stock: schema_1.products.stock,
    lowStockAlert: schema_1.products.lowStockAlert,
    minOrder: schema_1.products.minOrder,
    maxOrder: schema_1.products.maxOrder,
    unlimitedStock: schema_1.products.unlimitedStock,
    backorder: schema_1.products.backorder,
    trackInventory: schema_1.products.trackInventory,
    stockStatus: schema_1.products.stockStatus,
    productStatus: schema_1.products.productStatus,
    images: schema_1.products.images,
    sizeOptions: schema_1.products.sizeOptions,
    colorOptions: schema_1.products.colorOptions,
    paymentMethods: schema_1.products.paymentMethods,
    paymentPhoneNumber: schema_1.products.paymentPhoneNumber,
    status: schema_1.products.status,
    createdAt: schema_1.products.createdAt,
    categoryName: schema_1.categories.name,
    categorySlug: schema_1.categories.slug,
    categoryParentId: schema_1.categories.parentId,
    subCategoryName: (0, drizzle_orm_1.sql) `sc.name`,
    subCategorySlug: (0, drizzle_orm_1.sql) `sc.slug`,
    childCategoryName: (0, drizzle_orm_1.sql) `cc.name`,
    childCategorySlug: (0, drizzle_orm_1.sql) `cc.slug`,
    collectionName: schema_1.collections.name,
    collectionSlug: schema_1.collections.slug,
    collectionImage: schema_1.collections.image,
    vendorName: schema_1.vendors.name,
    vendorSlug: schema_1.vendors.slug,
    vendorLogo: schema_1.vendors.logo,
    supplierName: schema_1.suppliers.name,
    supplierSlug: schema_1.suppliers.slug,
    brandName: schema_1.brands.name,
    brandLogo: schema_1.brands.logo,
    brandSlug: schema_1.brands.slug,
};
const formatProductRow = (row, ratingInfo) => {
    const isTrue = (v) => v === true || v === 1 || v === "1";
    return {
        id: row.id,
        title: row.title,
        slug: row.slug,
        description: row.description,
        shortDescription: row.shortDescription,
        price: row.price,
        salePrice: row.salePrice,
        discount: row.discount,
        costPrice: row.costPrice,
        profitMargin: row.profitMargin,
        tax: row.tax,
        vat: row.vat,
        shippingCharge: row.shippingCharge,
        codFee: row.codFee,
        flashSalePrice: row.flashSalePrice,
        wholesalePrice: row.wholesalePrice,
        dealerPrice: row.dealerPrice,
        categoryId: row.categoryId,
        subCategoryId: row.subCategoryId,
        childCategoryId: row.childCategoryId,
        collectionId: row.collectionId,
        brandId: row.brandId,
        brand: row.brand,
        vendorId: row.vendorId,
        supplierId: row.supplierId,
        supplier: row.supplier,
        countryOfOrigin: row.countryOfOrigin,
        sku: row.sku,
        barcode: row.barcode,
        tags: row.tags,
        warranty: row.warranty,
        weight: row.weight,
        dimensions: row.dimensions,
        features: row.features,
        returnPolicy: row.returnPolicy,
        warehouse: row.warehouse,
        videoUrl: row.videoUrl,
        seoTitle: row.seoTitle,
        seoDescription: row.seoDescription,
        seoKeywords: row.seoKeywords,
        canonicalUrl: row.canonicalUrl,
        ogImage: row.ogImage,
        twitterImage: row.twitterImage,
        structuredData: row.structuredData,
        emiAvailable: isTrue(row.emiAvailable),
        isFeatured: isTrue(row.isFeatured),
        isTrending: isTrue(row.isTrending),
        isFlashSale: isTrue(row.isFlashSale),
        isNewArrival: isTrue(row.isNewArrival),
        isBestSeller: isTrue(row.isBestSeller),
        isLimitedEdition: isTrue(row.isLimitedEdition),
        isOfficial: isTrue(row.isOfficial),
        isHotDeal: isTrue(row.isHotDeal),
        isArchived: isTrue(row.isArchived),
        meta: row.meta,
        stock: row.stock,
        lowStockAlert: row.lowStockAlert,
        minOrder: row.minOrder,
        maxOrder: row.maxOrder,
        unlimitedStock: isTrue(row.unlimitedStock),
        backorder: isTrue(row.backorder),
        trackInventory: isTrue(row.trackInventory),
        stockStatus: row.stockStatus,
        productStatus: row.productStatus,
        images: row.images || [],
        sizeOptions: row.sizeOptions,
        colorOptions: row.colorOptions,
        paymentMethods: row.paymentMethods,
        paymentPhoneNumber: row.paymentPhoneNumber,
        status: row.status,
        createdAt: row.createdAt,
        category: row.categoryName
            ? { id: row.categoryId, name: row.categoryName, slug: row.categorySlug, parentId: row.categoryParentId }
            : null,
        subCategory: row.subCategoryName ? { id: row.subCategoryId, name: row.subCategoryName, slug: row.subCategorySlug } : null,
        childCategory: row.childCategoryName ? { id: row.childCategoryId, name: row.childCategoryName, slug: row.childCategorySlug } : null,
        collection: row.collectionName
            ? { id: row.collectionId, name: row.collectionName, slug: row.collectionSlug, image: row.collectionImage }
            : null,
        vendor: row.vendorName ? { id: row.vendorId, name: row.vendorName, slug: row.vendorSlug, logo: row.vendorLogo } : null,
        supplierInfo: row.supplierName ? { id: row.supplierId, name: row.supplierName, slug: row.supplierSlug } : null,
        brandInfo: row.brandName
            ? { id: row.brandId, name: row.brandName, logo: row.brandLogo, slug: row.brandSlug }
            : null,
        rating: ratingInfo ? ratingInfo.rating : null,
        reviewCount: ratingInfo ? ratingInfo.reviewCount : 0,
    };
};
exports.formatProductRow = formatProductRow;
const fetchRatingMap = async (productIds) => {
    if (productIds && productIds.length === 0) {
        return new Map();
    }
    const map = new Map();
    const missingIds = [];
    if (productIds) {
        for (const id of productIds) {
            const cached = cache_1.memoryCache.get(`rating:${id}`);
            if (cached !== undefined) {
                map.set(id, cached);
            }
            else {
                missingIds.push(id);
            }
        }
        if (missingIds.length === 0) {
            return map;
        }
    }
    const where = missingIds.length > 0
        ? (0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.reviews.status, "approved"), (0, drizzle_orm_1.inArray)(schema_1.reviews.productId, missingIds))
        : (0, drizzle_orm_1.eq)(schema_1.reviews.status, "approved");
    const rows = await db_1.db
        .select({
        productId: schema_1.reviews.productId,
        rating: (0, drizzle_orm_1.sql) `ROUND(AVG(${schema_1.reviews.rating}), 1)`,
        reviewCount: (0, drizzle_orm_1.sql) `count(*)`,
    })
        .from(schema_1.reviews)
        .where(where)
        .groupBy(schema_1.reviews.productId);
    rows.forEach((r) => {
        const val = { rating: Number(r.rating), reviewCount: Number(r.reviewCount) };
        map.set(r.productId, val);
        cache_1.memoryCache.set(`rating:${r.productId}`, val, 300);
    });
    if (missingIds.length > 0) {
        for (const id of missingIds) {
            if (!map.has(id)) {
                const val = { rating: null, reviewCount: 0 };
                map.set(id, val);
                cache_1.memoryCache.set(`rating:${id}`, val, 300);
            }
        }
    }
    return map;
};
exports.fetchRatingMap = fetchRatingMap;
/**
 * Lightweight product shape for homepage product cards.
 * Only includes the fields that the ProductCard component actually renders.
 * ~60% smaller than the full formatProductRow payload.
 */
const formatHomepageProduct = (row, ratingInfo) => {
    const isTrue = (v) => v === true || v === 1 || v === "1";
    return {
        id: row.id,
        title: row.title,
        slug: row.slug,
        price: row.price,
        salePrice: row.salePrice,
        discount: row.discount,
        stock: row.stock,
        images: row.images || [],
        colorOptions: row.colorOptions,
        sizeOptions: row.sizeOptions,
        isBestSeller: isTrue(row.isBestSeller),
        isNewArrival: isTrue(row.isNewArrival),
        isFlashSale: isTrue(row.isFlashSale),
        isHotDeal: isTrue(row.isHotDeal),
        isFeatured: isTrue(row.isFeatured),
        brand: row.brand,
        brandInfo: row.brandName
            ? { id: row.brandId, name: row.brandName, logo: row.brandLogo, slug: row.brandSlug }
            : null,
        rating: ratingInfo ? ratingInfo.rating : null,
        reviewCount: ratingInfo ? ratingInfo.reviewCount : 0,
        variants: undefined,
    };
};
exports.formatHomepageProduct = formatHomepageProduct;
/**
 * Fetch product rows WITHOUT triggering a rating lookup.
 * Used by the homepage service which batches all rating queries
 * into a single fetchRatingMap call across all sections.
 */
const fetchProductRowsOnly = async (where, limit, orderBy) => {
    return (0, exports.fullQuery)().where(where).orderBy(orderBy ?? (0, drizzle_orm_1.desc)(schema_1.products.createdAt)).limit(limit);
};
exports.fetchProductRowsOnly = fetchProductRowsOnly;
const buildWhere = async (query) => {
    const conditions = [];
    if (query.status && query.status !== "all") {
        conditions.push((0, drizzle_orm_1.eq)(schema_1.products.status, query.status));
    }
    else if (!query.status && !query.productStatus) {
        conditions.push((0, drizzle_orm_1.eq)(schema_1.products.status, DEFAULT_STATUS));
    }
    if (query.productStatus) {
        conditions.push((0, drizzle_orm_1.eq)(schema_1.products.productStatus, query.productStatus));
    }
    if (query.search) {
        const term = `%${query.search}%`;
        conditions.push((0, drizzle_orm_1.or)((0, drizzle_orm_1.like)(schema_1.products.title, term), (0, drizzle_orm_1.like)(schema_1.products.sku, term), (0, drizzle_orm_1.like)(schema_1.products.barcode, term), (0, drizzle_orm_1.like)(schema_1.products.brand, term), (0, drizzle_orm_1.sql) `${schema_1.products.tags} LIKE ${term}`));
    }
    if (query.sku)
        conditions.push((0, drizzle_orm_1.like)(schema_1.products.sku, `%${query.sku}%`));
    if (query.barcode)
        conditions.push((0, drizzle_orm_1.like)(schema_1.products.barcode, `%${query.barcode}%`));
    if (query.tags)
        conditions.push((0, drizzle_orm_1.sql) `${schema_1.products.tags} LIKE ${`%${query.tags}%`}`);
    if (query.category) {
        const catRows = await db_1.db.select().from(schema_1.categories).where((0, drizzle_orm_1.eq)(schema_1.categories.slug, query.category)).limit(1);
        if (catRows[0]) {
            conditions.push((0, drizzle_orm_1.or)((0, drizzle_orm_1.eq)(schema_1.products.categoryId, catRows[0].id), (0, drizzle_orm_1.eq)(schema_1.products.subCategoryId, catRows[0].id), (0, drizzle_orm_1.eq)(schema_1.products.childCategoryId, catRows[0].id)));
        }
    }
    if (query.brand) {
        const brandRows = await db_1.db.select().from(schema_1.brands).where((0, drizzle_orm_1.eq)(schema_1.brands.slug, query.brand)).limit(1);
        if (brandRows[0])
            conditions.push((0, drizzle_orm_1.eq)(schema_1.products.brandId, brandRows[0].id));
    }
    if (query.supplier) {
        const supplierRows = await db_1.db.select().from(schema_1.suppliers).where((0, drizzle_orm_1.eq)(schema_1.suppliers.slug, query.supplier)).limit(1);
        if (supplierRows[0])
            conditions.push((0, drizzle_orm_1.eq)(schema_1.products.supplierId, supplierRows[0].id));
    }
    if (query.vendor) {
        const vendorRows = await db_1.db.select().from(schema_1.vendors).where((0, drizzle_orm_1.eq)(schema_1.vendors.slug, query.vendor)).limit(1);
        if (vendorRows[0])
            conditions.push((0, drizzle_orm_1.eq)(schema_1.products.vendorId, vendorRows[0].id));
    }
    if (query.collection) {
        const collectionRows = await db_1.db.select().from(schema_1.collections).where((0, drizzle_orm_1.eq)(schema_1.collections.slug, query.collection)).limit(1);
        if (collectionRows[0])
            conditions.push((0, drizzle_orm_1.eq)(schema_1.products.collectionId, collectionRows[0].id));
    }
    if (query.stock) {
        if (query.stock === "in_stock") {
            conditions.push((0, drizzle_orm_1.or)((0, drizzle_orm_1.sql) `${schema_1.products.stock} > 0`, (0, drizzle_orm_1.eq)(schema_1.products.unlimitedStock, true)));
        }
        else if (query.stock === "low_stock") {
            conditions.push((0, drizzle_orm_1.and)((0, drizzle_orm_1.sql) `${schema_1.products.stock} > 0`, (0, drizzle_orm_1.sql) `${schema_1.products.stock} <= COALESCE(${schema_1.products.lowStockAlert}, 5)`));
        }
        else if (query.stock === "out_of_stock") {
            conditions.push((0, drizzle_orm_1.or)((0, drizzle_orm_1.sql) `${schema_1.products.stock} <= 0`, (0, drizzle_orm_1.eq)(schema_1.products.stock, 0)));
        }
    }
    if (query.minPrice) {
        conditions.push((0, drizzle_orm_1.gte)(schema_1.products.price, String(query.minPrice)));
    }
    if (query.maxPrice) {
        conditions.push((0, drizzle_orm_1.lte)(schema_1.products.price, String(query.maxPrice)));
    }
    if (query.dateFrom) {
        conditions.push((0, drizzle_orm_1.gte)(schema_1.products.createdAt, new Date(query.dateFrom)));
    }
    if (query.dateTo) {
        conditions.push((0, drizzle_orm_1.lte)(schema_1.products.createdAt, new Date(query.dateTo)));
    }
    if (query.inStock) {
        conditions.push((0, drizzle_orm_1.or)((0, drizzle_orm_1.sql) `${schema_1.products.stock} > 0`, (0, drizzle_orm_1.eq)(schema_1.products.unlimitedStock, true)));
    }
    if (query.sale) {
        conditions.push((0, drizzle_orm_1.or)((0, drizzle_orm_1.sql) `${schema_1.products.discount} > 0`, (0, drizzle_orm_1.sql) `${schema_1.products.salePrice} IS NOT NULL`));
    }
    if (query.label) {
        const labelMap = {
            featured: schema_1.products.isFeatured,
            trending: schema_1.products.isTrending,
            flash_sale: schema_1.products.isFlashSale,
            new_arrival: schema_1.products.isNewArrival,
            best_seller: schema_1.products.isBestSeller,
            limited_edition: schema_1.products.isLimitedEdition,
            official: schema_1.products.isOfficial,
            hot_deal: schema_1.products.isHotDeal,
        };
        if (labelMap[query.label])
            conditions.push((0, drizzle_orm_1.eq)(labelMap[query.label], true));
    }
    const minRating = toNum(query.minRating);
    if (minRating) {
        const rated = await db_1.db
            .select({ productId: schema_1.reviews.productId })
            .from(schema_1.reviews)
            .where((0, drizzle_orm_1.eq)(schema_1.reviews.status, "approved"))
            .groupBy(schema_1.reviews.productId)
            .having((0, drizzle_orm_1.sql) `AVG(${schema_1.reviews.rating}) >= ${minRating}`);
        const ids = rated.map((r) => r.productId);
        if (ids.length === 0) {
            conditions.push((0, drizzle_orm_1.sql) `0 = 1`);
        }
        else {
            conditions.push((0, drizzle_orm_1.inArray)(schema_1.products.id, ids));
        }
    }
    return conditions.length > 0 ? (0, drizzle_orm_1.and)(...conditions) : undefined;
};
const fullQuery = () => db_1.db
    .select(productColumns)
    .from(schema_1.products)
    .leftJoin(schema_1.categories, (0, drizzle_orm_1.eq)(schema_1.products.categoryId, schema_1.categories.id))
    .leftJoin((0, drizzle_orm_1.sql) `categories AS sc`, (0, drizzle_orm_1.eq)(schema_1.products.subCategoryId, (0, drizzle_orm_1.sql) `sc.id`))
    .leftJoin((0, drizzle_orm_1.sql) `categories AS cc`, (0, drizzle_orm_1.eq)(schema_1.products.childCategoryId, (0, drizzle_orm_1.sql) `cc.id`))
    .leftJoin(schema_1.collections, (0, drizzle_orm_1.eq)(schema_1.products.collectionId, schema_1.collections.id))
    .leftJoin(schema_1.vendors, (0, drizzle_orm_1.eq)(schema_1.products.vendorId, schema_1.vendors.id))
    .leftJoin(schema_1.suppliers, (0, drizzle_orm_1.eq)(schema_1.products.supplierId, schema_1.suppliers.id))
    .leftJoin(schema_1.brands, (0, drizzle_orm_1.eq)(schema_1.products.brandId, schema_1.brands.id));
exports.fullQuery = fullQuery;
const fetchChildren = async (productId) => {
    const [variantRows, specRows, relationRows] = await Promise.all([
        db_1.db.select().from(schema_1.productVariants).where((0, drizzle_orm_1.eq)(schema_1.productVariants.productId, productId)).orderBy((0, drizzle_orm_1.asc)(schema_1.productVariants.id)),
        db_1.db.select().from(schema_1.productSpecs).where((0, drizzle_orm_1.eq)(schema_1.productSpecs.productId, productId)).orderBy((0, drizzle_orm_1.asc)(schema_1.productSpecs.sortOrder)),
        db_1.db.select().from(schema_1.productRelations).where((0, drizzle_orm_1.eq)(schema_1.productRelations.productId, productId)),
    ]);
    const relatedIds = relationRows.map((r) => r.relatedProductId);
    const relatedProducts = relatedIds.length
        ? await db_1.db
            .select({ id: schema_1.products.id, title: schema_1.products.title, slug: schema_1.products.slug, price: schema_1.products.price, discount: schema_1.products.discount, images: schema_1.products.images })
            .from(schema_1.products)
            .where((0, drizzle_orm_1.inArray)(schema_1.products.id, relatedIds))
        : [];
    const relatedMap = new Map(relatedProducts.map((p) => [p.id, p]));
    return {
        variants: variantRows.map((v) => ({
            id: v.id,
            name: v.name,
            options: v.options,
            price: v.price,
            discountPrice: v.discountPrice,
            sku: v.sku,
            barcode: v.barcode,
            stock: v.stock,
            weight: v.weight,
            dimensions: v.dimensions,
            images: v.images,
            thumbnail: v.thumbnail,
            status: v.status,
            shippingCost: v.shippingCost,
            warranty: v.warranty,
            availability: Boolean(v.availability),
        })),
        specs: specRows.map((s) => ({ id: s.id, label: s.label, value: s.value, sortOrder: s.sortOrder })),
        relations: relationRows.map((r) => ({
            id: r.id,
            type: r.type,
            relatedProduct: relatedMap.get(r.relatedProductId) || null,
        })),
    };
};
const toFullProduct = async (row, ratingInfo) => {
    if (!row)
        return null;
    const base = (0, exports.formatProductRow)(row, ratingInfo);
    const children = await fetchChildren(base.id);
    return { ...base, ...children };
};
const getAll = async (query) => {
    const page = query.page || DEFAULT_PAGE;
    const limit = query.limit || DEFAULT_LIMIT;
    const offset = (page - 1) * limit;
    const where = await buildWhere(query);
    const orderByClause = query.sort === "oldest"
        ? (0, drizzle_orm_1.asc)(schema_1.products.createdAt)
        : query.sort === "price_asc"
            ? (0, drizzle_orm_1.asc)(schema_1.products.price)
            : query.sort === "price_desc"
                ? (0, drizzle_orm_1.desc)(schema_1.products.price)
                : query.sort === "stock_asc"
                    ? (0, drizzle_orm_1.asc)(schema_1.products.stock)
                    : query.sort === "stock_desc"
                        ? (0, drizzle_orm_1.desc)(schema_1.products.stock)
                        : query.sort === "title_asc"
                            ? (0, drizzle_orm_1.asc)(schema_1.products.title)
                            : query.sort === "title_desc"
                                ? (0, drizzle_orm_1.desc)(schema_1.products.title)
                                : query.sort === "rating_desc"
                                    ? (0, drizzle_orm_1.desc)((0, drizzle_orm_1.sql) `(SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = ${schema_1.products.id} AND r.status = 'approved')`)
                                    : (0, drizzle_orm_1.desc)(schema_1.products.createdAt);
    const data = await (0, exports.fullQuery)().where(where).orderBy(orderByClause).limit(limit).offset(offset);
    const countResult = await db_1.db.select({ count: (0, drizzle_orm_1.sql) `count(*)` }).from(schema_1.products).where(where);
    const total = Number(countResult[0].count);
    const ratingMap = await (0, exports.fetchRatingMap)(data.map((row) => row.id));
    return {
        data: data.map((row) => (0, exports.formatProductRow)(row, ratingMap.get(row.id))),
        pagination: {
            page,
            limit,
            total,
            totalPages: Math.ceil(total / limit),
        },
    };
};
exports.getAll = getAll;
const getById = async (id) => {
    const rows = await (0, exports.fullQuery)().where((0, drizzle_orm_1.eq)(schema_1.products.id, id)).limit(1);
    if (!rows[0])
        return null;
    const ratingMap = await (0, exports.fetchRatingMap)([id]);
    return toFullProduct(rows[0], ratingMap.get(id));
};
exports.getById = getById;
const getBySlug = async (slug) => {
    const cacheKey = `product_slug:${slug}`;
    const cached = cache_1.memoryCache.get(cacheKey);
    if (cached)
        return cached;
    const rows = await (0, exports.fullQuery)().where((0, drizzle_orm_1.eq)(schema_1.products.slug, slug)).limit(1);
    if (!rows[0])
        return null;
    const [ratingMap, children] = await Promise.all([
        (0, exports.fetchRatingMap)([rows[0].id]),
        fetchChildren(rows[0].id),
    ]);
    const base = (0, exports.formatProductRow)(rows[0], ratingMap.get(rows[0].id));
    const result = { ...base, ...children };
    cache_1.memoryCache.set(cacheKey, result, 180);
    return result;
};
exports.getBySlug = getBySlug;
const getRelated = async (categoryId, excludeId) => {
    const rows = await (0, exports.fullQuery)()
        .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.products.categoryId, categoryId), (0, drizzle_orm_1.eq)(schema_1.products.status, DEFAULT_STATUS), (0, drizzle_orm_1.sql) `${schema_1.products.id} != ${excludeId}`))
        .limit(RELATED_LIMIT);
    const ratingMap = await (0, exports.fetchRatingMap)(rows.map((row) => row.id));
    return rows.map((row) => (0, exports.formatProductRow)(row, ratingMap.get(row.id)));
};
exports.getRelated = getRelated;
const hasId = (v) => {
    if (v === undefined || v === null || v === "")
        return false;
    const n = Number(v);
    return Number.isInteger(n) && n > 0;
};
const validateProductRelations = async (data) => {
    const errors = {};
    if (hasId(data.categoryId)) {
        const rows = await db_1.db
            .select({ id: schema_1.categories.id })
            .from(schema_1.categories)
            .where((0, drizzle_orm_1.eq)(schema_1.categories.id, Number(data.categoryId)))
            .limit(1);
        if (!rows.length)
            errors.categoryId = "Category not found";
    }
    if (hasId(data.subCategoryId)) {
        const rows = await db_1.db
            .select({ id: schema_1.categories.id, parentId: schema_1.categories.parentId })
            .from(schema_1.categories)
            .where((0, drizzle_orm_1.eq)(schema_1.categories.id, Number(data.subCategoryId)))
            .limit(1);
        if (!rows.length) {
            errors.subCategoryId = "Sub-category not found";
        }
        else if (hasId(data.categoryId) && rows[0].parentId !== Number(data.categoryId)) {
            errors.subCategoryId = "Sub-category does not belong to selected category";
        }
    }
    if (hasId(data.childCategoryId)) {
        const rows = await db_1.db
            .select({ id: schema_1.categories.id, parentId: schema_1.categories.parentId })
            .from(schema_1.categories)
            .where((0, drizzle_orm_1.eq)(schema_1.categories.id, Number(data.childCategoryId)))
            .limit(1);
        if (!rows.length) {
            errors.childCategoryId = "Child category not found";
        }
        else if (hasId(data.subCategoryId) && rows[0].parentId !== Number(data.subCategoryId)) {
            errors.childCategoryId = "Child category does not belong to selected sub-category";
        }
    }
    const refChecks = [
        ["brandId", data.brandId, schema_1.brands],
        ["collectionId", data.collectionId, schema_1.collections],
        ["vendorId", data.vendorId, schema_1.vendors],
        ["supplierId", data.supplierId, schema_1.suppliers],
    ];
    for (const [key, value, table] of refChecks) {
        if (!hasId(value))
            continue;
        const rows = await db_1.db.select({ id: table.id }).from(table).where((0, drizzle_orm_1.eq)(table.id, Number(value))).limit(1);
        if (!rows.length) {
            errors[key] = "Reference not found";
        }
    }
    if (Object.keys(errors).length) {
        throw new AppError_1.AppError(400, "Validation failed", { errors });
    }
};
const syncVariants = async (tx, productId, variants) => {
    if (!variants.length) {
        await tx.delete(schema_1.productVariants).where((0, drizzle_orm_1.eq)(schema_1.productVariants.productId, productId));
        return;
    }
    const existing = await tx
        .select({ id: schema_1.productVariants.id })
        .from(schema_1.productVariants)
        .where((0, drizzle_orm_1.eq)(schema_1.productVariants.productId, productId));
    const existingIds = new Set(existing.map((e) => Number(e.id)));
    const keptIds = new Set();
    const seenNames = new Set();
    const seenSkus = new Set();
    for (const v of variants) {
        const nameLower = v.name.toLowerCase();
        if (seenNames.has(nameLower)) {
            throw new AppError_1.AppError(400, `Duplicate variant name: "${v.name}"`);
        }
        seenNames.add(nameLower);
        const priceNum = toNum(v.price);
        if (priceNum !== null && priceNum < 0) {
            throw new AppError_1.AppError(400, `Price cannot be negative for variant "${v.name}"`);
        }
        const salePriceNum = toNum(v.discountPrice ?? v.salePrice);
        if (salePriceNum !== null && salePriceNum < 0) {
            throw new AppError_1.AppError(400, `Sale price cannot be negative for variant "${v.name}"`);
        }
        const stockNum = toNum(v.stock) ?? 0;
        if (stockNum < 0) {
            throw new AppError_1.AppError(400, `Stock cannot be negative for variant "${v.name}"`);
        }
        const sku = toStr(v.sku);
        if (sku) {
            const skuLower = sku.toLowerCase();
            if (seenSkus.has(skuLower)) {
                throw new AppError_1.AppError(400, `Duplicate SKU: "${sku}"`);
            }
            seenSkus.add(skuLower);
            const duplicateSku = await tx
                .select({ id: schema_1.productVariants.id })
                .from(schema_1.productVariants)
                .where((0, drizzle_orm_1.eq)(schema_1.productVariants.sku, sku))
                .limit(1);
            if (duplicateSku.length > 0 && !existingIds.has(Number(duplicateSku[0].id))) {
                throw new AppError_1.AppError(400, `SKU "${sku}" already exists on another variant`);
            }
        }
        const data = {
            name: v.name,
            options: v.options || {},
            price: toStr(v.price),
            discountPrice: toStr(v.discountPrice ?? v.salePrice),
            sku: sku,
            barcode: toStr(v.barcode),
            stock: stockNum,
            weight: toStr(v.weight),
            dimensions: toStr(v.dimensions),
            images: v.images || [],
            thumbnail: v.thumbnail || null,
            status: v.status || "active",
            shippingCost: toStr(v.shippingCost),
            warranty: toStr(v.warranty),
            availability: v.availability === undefined ? true : v.availability,
        };
        const id = v.id !== undefined && v.id !== null ? Number(v.id) : null;
        if (id !== null && existingIds.has(id)) {
            await tx
                .update(schema_1.productVariants)
                .set(data)
                .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.productVariants.id, id), (0, drizzle_orm_1.eq)(schema_1.productVariants.productId, productId)));
            keptIds.add(id);
        }
        else {
            await tx.insert(schema_1.productVariants).values({ ...data, productId });
        }
    }
    const removedIds = [...existingIds].filter((id) => !keptIds.has(id));
    if (removedIds.length) {
        await tx
            .delete(schema_1.productVariants)
            .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.inArray)(schema_1.productVariants.id, removedIds), (0, drizzle_orm_1.eq)(schema_1.productVariants.productId, productId)));
    }
};
const insertChildren = async (tx, productId, data) => {
    const variants = (data.variants || []).filter(Boolean);
    if (variants.length) {
        await tx.insert(schema_1.productVariants).values(variants.map((v) => ({
            productId,
            name: v.name,
            options: v.options || {},
            price: toStr(v.price),
            discountPrice: toStr(v.discountPrice ?? v.salePrice),
            sku: toStr(v.sku),
            barcode: toStr(v.barcode),
            stock: toNum(v.stock) ?? 0,
            weight: toStr(v.weight),
            dimensions: toStr(v.dimensions),
            images: v.images || [],
            thumbnail: v.thumbnail || null,
            status: v.status || "active",
            shippingCost: toStr(v.shippingCost),
            warranty: toStr(v.warranty),
            availability: v.availability === undefined ? true : v.availability,
        })));
    }
    const specs = (data.specs || []).filter((s) => s.label && s.value);
    if (specs.length) {
        await tx.insert(schema_1.productSpecs).values(specs.map((s, index) => ({
            productId,
            label: s.label,
            value: s.value,
            sortOrder: s.sortOrder ?? index,
        })));
    }
    const relations = (data.relations || []).filter((r) => r.relatedProductId);
    if (relations.length) {
        await tx.insert(schema_1.productRelations).values(relations.map((r) => ({
            productId,
            relatedProductId: Number(r.relatedProductId),
            type: r.type,
        })));
    }
};
const create = async (data) => {
    await validateProductRelations(data);
    const variantStock = (data.variants || []).reduce((sum, v) => sum + (toNum(v.stock) ?? 0), 0);
    const stock = data.stock ?? (data.variants && data.variants.length ? variantStock : 0);
    const insertData = {
        ...data,
        stock,
        profitMargin: (0, exports.deriveProfitMargin)(data.salePrice ?? data.price, data.costPrice),
        stockStatus: (0, exports.deriveStockStatus)(stock, toNum(data.lowStockAlert), data.unlimitedStock ?? false, data.stockStatus || null),
        status: deriveStatusFromProductStatus(data.productStatus, data.status),
    };
    delete insertData.variants;
    delete insertData.specs;
    delete insertData.relations;
    const productId = await db_1.db.transaction(async (tx) => {
        const result = await tx.insert(schema_1.products).values(insertData);
        const id = result[0].insertId;
        await insertChildren(tx, id, data);
        return id;
    });
    return (0, exports.getById)(productId);
};
exports.create = create;
const update = async (id, data) => {
    await validateProductRelations(data);
    const updateData = { ...data };
    delete updateData.variants;
    delete updateData.specs;
    delete updateData.relations;
    const existing = await db_1.db
        .select({
        stock: schema_1.products.stock,
        lowStockAlert: schema_1.products.lowStockAlert,
        unlimitedStock: schema_1.products.unlimitedStock,
        price: schema_1.products.price,
        salePrice: schema_1.products.salePrice,
        costPrice: schema_1.products.costPrice,
    })
        .from(schema_1.products)
        .where((0, drizzle_orm_1.eq)(schema_1.products.id, id))
        .limit(1);
    const current = existing[0];
    const stock = data.stock ?? current?.stock ?? 0;
    if (data.stock !== undefined || data.variants !== undefined) {
        const variantStock = (data.variants || []).reduce((sum, v) => sum + (toNum(v.stock) ?? 0), 0);
        if (data.variants && data.variants.length)
            updateData.stock = variantStock;
        else
            updateData.stock = data.stock ?? stock;
    }
    if (data.salePrice !== undefined || data.price !== undefined || data.costPrice !== undefined) {
        updateData.profitMargin = (0, exports.deriveProfitMargin)(data.salePrice ?? data.price ?? current?.salePrice ?? current?.price, data.costPrice ?? current?.costPrice);
    }
    if (data.stockStatus !== undefined || data.stock !== undefined || data.variants !== undefined) {
        updateData.stockStatus = (0, exports.deriveStockStatus)(updateData.stock ?? stock, toNum(data.lowStockAlert ?? current?.lowStockAlert), data.unlimitedStock ?? current?.unlimitedStock ?? false, data.stockStatus || null);
    }
    if (data.productStatus !== undefined || data.status !== undefined) {
        updateData.status = deriveStatusFromProductStatus(data.productStatus, data.status);
    }
    if (data.variants !== undefined || data.specs !== undefined || data.relations !== undefined) {
        await db_1.db.transaction(async (tx) => {
            await tx.update(schema_1.products).set(updateData).where((0, drizzle_orm_1.eq)(schema_1.products.id, id));
            if (data.variants !== undefined) {
                await syncVariants(tx, id, (data.variants || []).filter(Boolean));
            }
            if (data.specs !== undefined) {
                await tx.delete(schema_1.productSpecs).where((0, drizzle_orm_1.eq)(schema_1.productSpecs.productId, id));
            }
            if (data.relations !== undefined) {
                await tx.delete(schema_1.productRelations).where((0, drizzle_orm_1.eq)(schema_1.productRelations.productId, id));
            }
            if (data.specs !== undefined || data.relations !== undefined) {
                await insertChildren(tx, id, { ...data, variants: [], specs: data.specs || [], relations: data.relations || [] });
            }
        });
    }
    else {
        await db_1.db.update(schema_1.products).set(updateData).where((0, drizzle_orm_1.eq)(schema_1.products.id, id));
    }
    return (0, exports.getById)(id);
};
exports.update = update;
const remove = async (id) => {
    await db_1.db.transaction(async (tx) => {
        await tx.delete(schema_1.productVariants).where((0, drizzle_orm_1.eq)(schema_1.productVariants.productId, id));
        await tx.delete(schema_1.productSpecs).where((0, drizzle_orm_1.eq)(schema_1.productSpecs.productId, id));
        await tx.delete(schema_1.productRelations).where((0, drizzle_orm_1.eq)(schema_1.productRelations.productId, id));
        await tx.delete(schema_1.products).where((0, drizzle_orm_1.eq)(schema_1.products.id, id));
    });
    return { success: true };
};
exports.remove = remove;
const bulkAction = async (ids, action) => {
    if (action === "delete") {
        await db_1.db.transaction(async (tx) => {
            await tx.delete(schema_1.productVariants).where((0, drizzle_orm_1.inArray)(schema_1.productVariants.productId, ids));
            await tx.delete(schema_1.productSpecs).where((0, drizzle_orm_1.inArray)(schema_1.productSpecs.productId, ids));
            await tx.delete(schema_1.productRelations).where((0, drizzle_orm_1.inArray)(schema_1.productRelations.productId, ids));
            await tx.delete(schema_1.products).where((0, drizzle_orm_1.inArray)(schema_1.products.id, ids));
        });
        return { affected: ids.length };
    }
    if (action === "feature" || action === "unfeature") {
        const result = await db_1.db
            .update(schema_1.products)
            .set({ isFeatured: action === "feature" })
            .where((0, drizzle_orm_1.inArray)(schema_1.products.id, ids));
        return { affected: Number(result[0].affectedRows) };
    }
    const map = {
        publish: { productStatus: "published", status: "active" },
        archive: { productStatus: "archived", status: "inactive" },
        hide: { productStatus: "hidden", status: "inactive" },
        draft: { productStatus: "draft", status: "inactive" },
    };
    const target = map[action];
    if (!target)
        throw new Error(`Unknown bulk action: ${action}`);
    const result = await db_1.db.update(schema_1.products).set({ productStatus: target.productStatus, status: target.status }).where((0, drizzle_orm_1.inArray)(schema_1.products.id, ids));
    return { affected: Number(result[0].affectedRows) };
};
exports.bulkAction = bulkAction;
const duplicate = async (id) => {
    const existing = await (0, exports.getById)(id);
    if (!existing)
        throw new Error("Product not found");
    const slug = `${existing.slug}-copy-${Date.now().toString(36)}`;
    const title = `${existing.title} (Copy)`;
    const insertData = { ...existing };
    delete insertData.id;
    delete insertData.createdAt;
    delete insertData.category;
    delete insertData.subCategory;
    delete insertData.childCategory;
    delete insertData.collection;
    delete insertData.vendor;
    delete insertData.supplierInfo;
    delete insertData.brandInfo;
    insertData.title = title;
    insertData.slug = slug;
    insertData.sku = existing.sku ? `${existing.sku}-C` : undefined;
    insertData.images = existing.images || [];
    insertData.variants = (existing.variants || []).map((v) => ({ ...v }));
    insertData.specs = (existing.specs || []).map((s) => ({ ...s }));
    insertData.relations = (existing.relations || []).filter((r) => r.relatedProduct).map((r) => ({ relatedProductId: r.relatedProduct.id, type: r.type }));
    return (0, exports.create)(insertData);
};
exports.duplicate = duplicate;
const autoSaveDraft = async (id, draft) => {
    await db_1.db.update(schema_1.products).set({ draft }).where((0, drizzle_orm_1.eq)(schema_1.products.id, id));
    return { success: true };
};
exports.autoSaveDraft = autoSaveDraft;
const CSV_COLUMNS = [
    "title",
    "price",
    "salePrice",
    "discount",
    "costPrice",
    "sku",
    "barcode",
    "brand",
    "category",
    "stock",
    "productStatus",
    "status",
    "shortDescription",
    "description",
    "tags",
    "features",
];
const csvEscape = (value) => {
    const s = value === null || value === undefined ? "" : String(value);
    if (/[",\n]/.test(s))
        return `"${s.replace(/"/g, '""')}"`;
    return s;
};
const exportCsv = async (query) => {
    const where = await buildWhere({ ...query, limit: undefined });
    const rows = await db_1.db
        .select({
        ...productColumns,
        categoryName: schema_1.categories.name,
    })
        .from(schema_1.products)
        .leftJoin(schema_1.categories, (0, drizzle_orm_1.eq)(schema_1.products.categoryId, schema_1.categories.id))
        .leftJoin((0, drizzle_orm_1.sql) `categories AS sc`, (0, drizzle_orm_1.eq)(schema_1.products.subCategoryId, (0, drizzle_orm_1.sql) `sc.id`))
        .leftJoin((0, drizzle_orm_1.sql) `categories AS cc`, (0, drizzle_orm_1.eq)(schema_1.products.childCategoryId, (0, drizzle_orm_1.sql) `cc.id`))
        .leftJoin(schema_1.collections, (0, drizzle_orm_1.eq)(schema_1.products.collectionId, schema_1.collections.id))
        .leftJoin(schema_1.vendors, (0, drizzle_orm_1.eq)(schema_1.products.vendorId, schema_1.vendors.id))
        .leftJoin(schema_1.suppliers, (0, drizzle_orm_1.eq)(schema_1.products.supplierId, schema_1.suppliers.id))
        .leftJoin(schema_1.brands, (0, drizzle_orm_1.eq)(schema_1.products.brandId, schema_1.brands.id))
        .where(where)
        .orderBy((0, drizzle_orm_1.desc)(schema_1.products.createdAt));
    const header = CSV_COLUMNS.join(",");
    const lines = rows.map((row) => CSV_COLUMNS.map((col) => {
        if (col === "category")
            return csvEscape(row.categoryName);
        if (col === "tags" || col === "features")
            return csvEscape(JSON.stringify(row[col] || []));
        return csvEscape(row[col]);
    }).join(","));
    return [header, ...lines].join("\n");
};
exports.exportCsv = exportCsv;
const importCsv = async (csv) => {
    const lines = csv.split(/\r?\n/).filter((l) => l.trim().length > 0);
    if (lines.length < 2)
        return { imported: 0 };
    const headers = lines[0].split(",").map((h) => h.trim().replace(/^"|"$/g, ""));
    const parseLine = (line) => {
        const values = [];
        let current = "";
        let inQuotes = false;
        for (let i = 0; i < line.length; i++) {
            const ch = line[i];
            if (inQuotes) {
                if (ch === '"' && line[i + 1] === '"') {
                    current += '"';
                    i++;
                }
                else if (ch === '"') {
                    inQuotes = false;
                }
                else {
                    current += ch;
                }
            }
            else if (ch === '"') {
                inQuotes = true;
            }
            else if (ch === ",") {
                values.push(current);
                current = "";
            }
            else {
                current += ch;
            }
        }
        values.push(current);
        return values;
    };
    let imported = 0;
    for (const line of lines.slice(1)) {
        const values = parseLine(line);
        const record = {};
        headers.forEach((h, i) => (record[h] = values[i]?.trim() ?? ""));
        if (!record.title || !record.price)
            continue;
        const slug = `${String(record.title).toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "")}-${Date.now().toString(36)}`;
        const tags = record.tags ? JSON.parse(record.tags || "[]") : undefined;
        const features = record.features ? JSON.parse(record.features || "[]") : undefined;
        await db_1.db.insert(schema_1.products).values({
            title: record.title,
            slug,
            price: record.price || "0",
            salePrice: record.salePrice || null,
            discount: record.discount || "0",
            costPrice: record.costPrice || "0",
            sku: record.sku || null,
            barcode: record.barcode || null,
            brand: record.brand || null,
            stock: Number(record.stock) || 0,
            productStatus: record.productStatus || "published",
            status: record.productStatus === "published" || !record.productStatus ? "active" : "inactive",
            shortDescription: record.shortDescription || null,
            description: record.description || null,
            tags,
            features,
            profitMargin: "0",
            stockStatus: (0, exports.deriveStockStatus)(Number(record.stock) || 0, 10, false, null),
        });
        imported++;
    }
    return { imported };
};
exports.importCsv = importCsv;
//# sourceMappingURL=product.service.js.map