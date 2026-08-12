"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || (function () {
    var ownKeys = function(o) {
        ownKeys = Object.getOwnPropertyNames || function (o) {
            var ar = [];
            for (var k in o) if (Object.prototype.hasOwnProperty.call(o, k)) ar[ar.length] = k;
            return ar;
        };
        return ownKeys(o);
    };
    return function (mod) {
        if (mod && mod.__esModule) return mod;
        var result = {};
        if (mod != null) for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
        __setModuleDefault(result, mod);
        return result;
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
exports.getSubscribers = exports.subscribeNewsletter = exports.getHomepage = exports.resetConfig = exports.saveConfig = exports.getConfig = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const settingsService = __importStar(require("../settings/settings.service"));
const product_service_1 = require("../product/product.service");
const AppError_1 = require("../../utils/AppError");
const CONFIG_KEY = "homepage_config";
const LEGACY_SLIDES_KEY = "hero_slides";
// ==================== DEFAULT CONFIG ====================
const DEFAULT_SECTIONS = [
    { id: "hero", type: "hero", enabled: true },
    {
        id: "trust_strip",
        type: "trust_strip",
        enabled: true,
        title: "Why shop with us",
    },
    {
        id: "categories",
        type: "categories",
        enabled: true,
        title: "Explore Categories",
        subtitle: "Discover our wide range of products across all categories.",
        limit: 12,
    },
    {
        id: "new_arrivals",
        type: "new_arrivals",
        enabled: true,
        title: "New arrivals",
        subtitle: "Fresh products just added to the store.",
        limit: 12,
    },
    {
        id: "promo_banner",
        type: "promo_banner",
        enabled: true,
    },
    {
        id: "featured",
        type: "featured",
        enabled: true,
        title: "Featured products",
        subtitle: "Handpicked favourites from our catalogue.",
        limit: 12,
    },
    {
        id: "brands",
        type: "brands",
        enabled: true,
        title: "Trusted brands",
        subtitle: "100% authentic products from official distributors.",
        limit: 10,
    },
    {
        id: "promo_banner_2",
        type: "promo_banner",
        enabled: true,
    },
    {
        id: "collections",
        type: "collections",
        enabled: true,
        title: "Featured collections",
        subtitle: "Complete setups built for every lifestyle.",
        limit: 6,
    },
    {
        id: "flash_deals",
        type: "flash_deals",
        enabled: true,
        title: "Flash Deals",
        subtitle: "Limited-time prices. When they're gone, they're gone.",
        limit: 12,
        background: "muted",
    },
    {
        id: "best_sellers",
        type: "best_sellers",
        enabled: true,
        title: "Best sellers",
        subtitle: "The most-ordered products right now.",
        limit: 12,
    },
    {
        id: "trending",
        type: "trending",
        enabled: true,
        title: "Trending right now",
        subtitle: "The products everyone is talking about.",
        limit: 10,
        background: "muted",
    },
    {
        id: "recommendations",
        type: "recommendations",
        enabled: true,
        title: "Recommended for you",
        subtitle: "Picked based on what you've browsed and bought.",
        limit: 10,
    },
    {
        id: "why_choose_us",
        type: "why_choose_us",
        enabled: true,
        title: "Why choose Mama Bazar",
    },
    {
        id: "reviews",
        type: "reviews",
        enabled: true,
        title: "What customers say",
        subtitle: "Real feedback from verified buyers.",
        limit: 8,
    },
    {
        id: "newsletter",
        type: "newsletter",
        enabled: true,
        title: "Never miss a deal",
    },
];
const DEFAULT_CONFIG = {
    announcement: {
        enabled: true,
        text: "Free delivery on orders over ৳2,000 — shop today!",
        backgroundColor: "#1e293b",
        textColor: "#ffffff",
    },
    heroSlides: [],
    sections: DEFAULT_SECTIONS,
    trustStrip: [
        { icon: "Truck", title: "Fast nationwide delivery", text: "2-5 days anywhere in Bangladesh" },
        { icon: "ShieldCheck", title: "Official warranty", text: "100% authentic, manufacturer-backed" },
        { icon: "RefreshCcw", title: "Easy returns", text: "7-day hassle-free returns" },
        { icon: "Headphones", title: "24/7 support", text: "Real humans, always here to help" },
    ],
    whyChooseUs: [
        { icon: "BadgeCheck", title: "Authentic products", text: "Sourced directly from official distributors with full warranty coverage." },
        { icon: "Truck", title: "Cash on delivery", text: "Pay when your order arrives at your doorstep — across all districts." },
        { icon: "ShieldCheck", title: "Secure payments", text: "bKash, Nagad, card and bank transfers with verified transactions." },
        { icon: "Headphones", title: "Dedicated support", text: "Chat, call or message us — our team responds within minutes." },
    ],
    newsletter: {
        enabled: true,
        title: "Never miss a deal",
        subtitle: "Subscribe for exclusive deals, early access to new arrivals and smart buying tips.",
        buttonText: "Subscribe",
    },
    flashSaleWindow: {
        enabled: false,
        start: null,
        end: null,
    },
    popularSearches: ["Blender", "Electric kettle", "LED TV", "Ceiling fan", "Microwave oven"],
};
// ==================== CONFIG LOAD / SAVE ====================
const mergeDeep = (base, saved) => {
    if (!saved || typeof saved !== "object")
        return base;
    const config = {
        announcement: { ...base.announcement, ...(saved.announcement || {}) },
        heroSlides: Array.isArray(saved.heroSlides) ? saved.heroSlides : base.heroSlides,
        sections: Array.isArray(saved.sections) ? saved.sections : base.sections,
        trustStrip: Array.isArray(saved.trustStrip) ? saved.trustStrip : base.trustStrip,
        whyChooseUs: Array.isArray(saved.whyChooseUs) ? saved.whyChooseUs : base.whyChooseUs,
        newsletter: { ...base.newsletter, ...(saved.newsletter || {}) },
        flashSaleWindow: { ...base.flashSaleWindow, ...(saved.flashSaleWindow || {}) },
        popularSearches: Array.isArray(saved.popularSearches) ? saved.popularSearches : base.popularSearches,
    };
    // Ensure every default section exists (new defaults appear even on old configs)
    const byId = new Map(config.sections.map((s) => [s.id, s]));
    base.sections.forEach((def) => {
        if (!byId.has(def.id)) {
            byId.set(def.id, def);
            config.sections.push(def);
        }
    });
    return config;
};
const getConfig = async () => {
    const saved = await settingsService.getJSON(CONFIG_KEY, null);
    return mergeDeep(DEFAULT_CONFIG, saved);
};
exports.getConfig = getConfig;
const saveConfig = async (config) => {
    const merged = mergeDeep(DEFAULT_CONFIG, config);
    await settingsService.setJSON(CONFIG_KEY, merged);
    return merged;
};
exports.saveConfig = saveConfig;
const resetConfig = async () => {
    await settingsService.setJSON(CONFIG_KEY, DEFAULT_CONFIG);
    return DEFAULT_CONFIG;
};
exports.resetConfig = resetConfig;
// ==================== DATA RESOLVERS ====================
const sanitizeLimit = (value, fallback) => {
    const n = Number(value);
    if (!Number.isFinite(n) || n <= 0)
        return fallback;
    return Math.min(Math.floor(n), 24);
};
const fetchProducts = async (where, limit, orderBy) => {
    const rows = await (0, product_service_1.fullQuery)().where(where).orderBy(orderBy ?? (0, drizzle_orm_1.desc)(schema_1.products.createdAt)).limit(limit);
    const ratingMap = await (0, product_service_1.fetchRatingMap)(rows.map((row) => row.id));
    return rows.map((row) => (0, product_service_1.formatProductRow)(row, ratingMap.get(row.id)));
};
const resolveLabelProducts = async (label, limit) => {
    const colMap = {
        featured: schema_1.products.isFeatured,
        trending: schema_1.products.isTrending,
        new_arrival: schema_1.products.isNewArrival,
        flash_sale: schema_1.products.isFlashSale,
    };
    const orderBy = label === "flash_sale" ? (0, drizzle_orm_1.desc)((0, drizzle_orm_1.sql) `COALESCE(${schema_1.products.discount}, 0)`) : (0, drizzle_orm_1.desc)(schema_1.products.createdAt);
    if (label === "new_arrival") {
        // Real "new arrivals": recent products (created within 30 days) or
        // admin-boosted via is_new_arrival, always sorted by createdAt (newest first).
        // If nothing is recent (e.g. all stock is older), fall back to newest products.
        const where = (0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.products.status, "active"), (0, drizzle_orm_1.or)((0, drizzle_orm_1.eq)(schema_1.products.isNewArrival, true), (0, drizzle_orm_1.sql) `${schema_1.products.createdAt} >= (NOW() - INTERVAL 30 DAY)`));
        const recent = await fetchProducts(where, limit, (0, drizzle_orm_1.desc)(schema_1.products.createdAt));
        if (recent.length > 0)
            return recent;
        return fetchProducts((0, drizzle_orm_1.eq)(schema_1.products.status, "active"), limit, (0, drizzle_orm_1.desc)(schema_1.products.createdAt));
    }
    return fetchProducts((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.products.status, "active"), (0, drizzle_orm_1.eq)(colMap[label], true)), limit, orderBy);
};
/**
 * Trending = real activity: products ranked by how many distinct orders they
 * appear in (most frequently purchased), excluding cancelled/returned/refunded.
 * Falls back to the admin `is_trending` flag, then newest.
 */
const resolveTrending = async (limit) => {
    const excluded = ["cancelled", "returned", "refunded"];
    const aggRows = await db_1.db
        .select({
        productId: schema_1.orderItems.productId,
        orderCount: (0, drizzle_orm_1.sql) `COUNT(DISTINCT ${schema_1.orderItems.orderId})`,
    })
        .from(schema_1.orderItems)
        .leftJoin(schema_1.orders, (0, drizzle_orm_1.eq)(schema_1.orderItems.orderId, schema_1.orders.id))
        .where((0, drizzle_orm_1.notInArray)(schema_1.orders.status, excluded))
        .groupBy(schema_1.orderItems.productId)
        .orderBy((0, drizzle_orm_1.desc)((0, drizzle_orm_1.sql) `COUNT(DISTINCT ${schema_1.orderItems.orderId})`), (0, drizzle_orm_1.desc)((0, drizzle_orm_1.sql) `SUM(${schema_1.orderItems.quantity})`))
        .limit(limit);
    const ids = aggRows.map((r) => r.productId);
    if (ids.length > 0) {
        const rows = await (0, product_service_1.fullQuery)()
            .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.products.status, "active"), (0, drizzle_orm_1.inArray)(schema_1.products.id, ids)))
            .limit(limit);
        const orderMap = new Map(aggRows.map((r, i) => [r.productId, i]));
        rows.sort((a, b) => (orderMap.get(a.id) ?? Number.MAX_SAFE_INTEGER) - (orderMap.get(b.id) ?? Number.MAX_SAFE_INTEGER));
        const ratingMap = await (0, product_service_1.fetchRatingMap)(rows.map((row) => row.id));
        return rows.map((row) => (0, product_service_1.formatProductRow)(row, ratingMap.get(row.id)));
    }
    const flagged = await fetchProducts((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.products.status, "active"), (0, drizzle_orm_1.eq)(schema_1.products.isTrending, true)), limit, (0, drizzle_orm_1.desc)(schema_1.products.createdAt));
    if (flagged.length > 0)
        return flagged;
    return fetchProducts((0, drizzle_orm_1.eq)(schema_1.products.status, "active"), limit, (0, drizzle_orm_1.desc)(schema_1.products.createdAt));
};
const resolveBestSellers = async (limit) => {
    const excluded = ["cancelled", "returned", "refunded"];
    const aggRows = await db_1.db
        .select({
        productId: schema_1.orderItems.productId,
        total: (0, drizzle_orm_1.sql) `SUM(${schema_1.orderItems.quantity})`,
    })
        .from(schema_1.orderItems)
        .leftJoin(schema_1.orders, (0, drizzle_orm_1.eq)(schema_1.orderItems.orderId, schema_1.orders.id))
        .where((0, drizzle_orm_1.notInArray)(schema_1.orders.status, excluded))
        .groupBy(schema_1.orderItems.productId)
        .orderBy((0, drizzle_orm_1.desc)((0, drizzle_orm_1.sql) `SUM(${schema_1.orderItems.quantity})`))
        .limit(limit);
    const ids = aggRows.map((r) => r.productId);
    if (ids.length > 0) {
        const rows = await (0, product_service_1.fullQuery)()
            .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.products.status, "active"), (0, drizzle_orm_1.inArray)(schema_1.products.id, ids)))
            .limit(limit);
        const orderMap = new Map(aggRows.map((r, i) => [r.productId, i]));
        rows.sort((a, b) => (orderMap.get(a.id) ?? Number.MAX_SAFE_INTEGER) - (orderMap.get(b.id) ?? Number.MAX_SAFE_INTEGER));
        const ratingMap = await (0, product_service_1.fetchRatingMap)(rows.map((row) => row.id));
        return rows.map((row) => (0, product_service_1.formatProductRow)(row, ratingMap.get(row.id)));
    }
    // No sales yet — fall back to the best-seller flag, then featured, then newest
    const fallback = await fetchProducts((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.products.status, "active"), (0, drizzle_orm_1.eq)(schema_1.products.isBestSeller, true)), limit, (0, drizzle_orm_1.desc)(schema_1.products.createdAt));
    if (fallback.length > 0)
        return fallback;
    return fetchProducts((0, drizzle_orm_1.eq)(schema_1.products.status, "active"), limit, (0, drizzle_orm_1.desc)(schema_1.products.createdAt));
};
const resolveRecommendations = async (userId, limit) => {
    if (userId) {
        const catRows = await db_1.db
            .select({ categoryId: (0, drizzle_orm_1.sql) `COALESCE(${schema_1.products.categoryId}, ${schema_1.products.subCategoryId})` })
            .from(schema_1.orderItems)
            .leftJoin(schema_1.orders, (0, drizzle_orm_1.eq)(schema_1.orderItems.orderId, schema_1.orders.id))
            .leftJoin(schema_1.products, (0, drizzle_orm_1.eq)(schema_1.orderItems.productId, schema_1.products.id))
            .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.orders.userId, userId), (0, drizzle_orm_1.notInArray)(schema_1.orders.status, ["cancelled", "returned", "refunded"])))
            .groupBy((0, drizzle_orm_1.sql) `COALESCE(${schema_1.products.categoryId}, ${schema_1.products.subCategoryId})`)
            .limit(6);
        const categoryIds = catRows.map((r) => r.categoryId).filter((id) => id !== null);
        if (categoryIds.length > 0) {
            const rows = await fetchProducts((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.products.status, "active"), (0, drizzle_orm_1.inArray)(schema_1.products.categoryId, categoryIds)), limit);
            if (rows.length >= 4)
                return rows;
        }
    }
    return resolveBestSellers(limit);
};
const resolveCategories = async (limit) => {
    const rows = await db_1.db
        .select({
        id: schema_1.categories.id,
        name: schema_1.categories.name,
        slug: schema_1.categories.slug,
        image: schema_1.categories.image,
        icon: schema_1.categories.icon,
        thumbnail: schema_1.categories.thumbnail,
        parentId: schema_1.categories.parentId,
    })
        .from(schema_1.categories)
        .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.categories.status, "active"), (0, drizzle_orm_1.isNull)(schema_1.categories.parentId)))
        .orderBy((0, drizzle_orm_1.asc)(schema_1.categories.sortOrder))
        .limit(limit);
    if (rows.length === 0)
        return rows;
    // Active product count per parent category (across category/sub/child links)
    const ids = rows.map((r) => r.id);
    const countRows = await db_1.db
        .select({
        categoryId: schema_1.categories.id,
        productCount: (0, drizzle_orm_1.sql) `COUNT(DISTINCT ${schema_1.products.id})`,
    })
        .from(schema_1.categories)
        .innerJoin(schema_1.products, (0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.products.status, "active"), (0, drizzle_orm_1.or)((0, drizzle_orm_1.eq)(schema_1.products.categoryId, schema_1.categories.id), (0, drizzle_orm_1.eq)(schema_1.products.subCategoryId, schema_1.categories.id), (0, drizzle_orm_1.eq)(schema_1.products.childCategoryId, schema_1.categories.id))))
        .where((0, drizzle_orm_1.inArray)(schema_1.categories.id, ids))
        .groupBy(schema_1.categories.id);
    const countMap = new Map(countRows.map((r) => [r.categoryId, Number(r.productCount)]));
    return rows.map((row) => ({ ...row, productCount: countMap.get(row.id) ?? 0 }));
};
const resolveBrands = async (limit) => {
    const rows = await db_1.db
        .select({
        id: schema_1.brands.id,
        name: schema_1.brands.name,
        slug: schema_1.brands.slug,
        logo: schema_1.brands.logo,
        featured: schema_1.brands.featured,
        status: schema_1.brands.status,
        createdAt: schema_1.brands.createdAt,
    })
        .from(schema_1.brands)
        .where((0, drizzle_orm_1.eq)(schema_1.brands.status, "active"))
        .orderBy((0, drizzle_orm_1.desc)(schema_1.brands.featured), (0, drizzle_orm_1.asc)(schema_1.brands.id))
        .limit(limit);
    return rows;
};
const resolveCollections = async (limit) => {
    const rows = await db_1.db
        .select({
        id: schema_1.collections.id,
        name: schema_1.collections.name,
        slug: schema_1.collections.slug,
        description: schema_1.collections.description,
        image: schema_1.collections.image,
        featured: schema_1.collections.featured,
        sortOrder: schema_1.collections.sortOrder,
        status: schema_1.collections.status,
        createdAt: schema_1.collections.createdAt,
    })
        .from(schema_1.collections)
        .where((0, drizzle_orm_1.eq)(schema_1.collections.status, "active"))
        .orderBy((0, drizzle_orm_1.desc)(schema_1.collections.featured), (0, drizzle_orm_1.asc)(schema_1.collections.sortOrder))
        .limit(limit);
    return rows;
};
const resolveBanners = async () => {
    const rows = await db_1.db
        .select()
        .from(schema_1.banners)
        .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.banners.status, "active"), (0, drizzle_orm_1.inArray)(schema_1.banners.position, ["banner", "promo"])))
        .orderBy((0, drizzle_orm_1.desc)(schema_1.banners.priority));
    return rows;
};
const resolveReviews = async (limit) => {
    const rows = await db_1.db
        .select({
        id: schema_1.reviews.id,
        productId: schema_1.reviews.productId,
        rating: schema_1.reviews.rating,
        title: schema_1.reviews.title,
        comment: schema_1.reviews.comment,
        customerName: schema_1.reviews.customerName,
        status: schema_1.reviews.status,
        createdAt: schema_1.reviews.createdAt,
        productTitle: schema_1.products.title,
        productSlug: schema_1.products.slug,
        productImage: (0, drizzle_orm_1.sql) `JSON_UNQUOTE(JSON_EXTRACT(${schema_1.products.images}, '$[0]'))`,
    })
        .from(schema_1.reviews)
        .leftJoin(schema_1.products, (0, drizzle_orm_1.eq)(schema_1.reviews.productId, schema_1.products.id))
        .where((0, drizzle_orm_1.eq)(schema_1.reviews.status, "approved"))
        .orderBy((0, drizzle_orm_1.desc)(schema_1.reviews.createdAt))
        .limit(limit);
    return rows;
};
// ==================== PUBLIC HOMEPAGE AGGREGATE ====================
const getHomepage = async (userId) => {
    const config = await (0, exports.getConfig)();
    const slides = config.heroSlides
        .filter((s) => s.status === "active")
        .sort((a, b) => b.priority - a.priority);
    // Legacy support: hero_slides was previously a plain string[] of image URLs
    if (slides.length === 0) {
        const legacy = await settingsService.getJSON(LEGACY_SLIDES_KEY, []);
        if (Array.isArray(legacy) && legacy.length > 0) {
            legacy.forEach((url, index) => {
                if (typeof url !== "string" || !url)
                    return;
                slides.push({
                    id: `legacy-${index}`,
                    desktopImage: url,
                    status: "active",
                    priority: legacy.length - index,
                });
            });
        }
    }
    const enabled = config.sections.filter((s) => s.enabled);
    const byType = (type) => enabled.find((s) => s.type === type);
    const limits = {};
    enabled.forEach((s) => {
        limits[s.type] = sanitizeLimit(s.limit, 12);
    });
    const [categoryRows, brandRows, collectionRows, bannerRows, reviewRows] = await Promise.all([
        resolveCategories(limits.categories || 12),
        resolveBrands(limits.brands || 10),
        resolveCollections(limits.collections || 6),
        resolveBanners(),
        byType("reviews") ? resolveReviews(limits.reviews || 8) : Promise.resolve([]),
    ]);
    const promoBannerSections = enabled.filter((s) => s.type === "promo_banner");
    const needsProducts = (t) => ["flash_deals", "featured", "best_sellers", "trending", "new_arrivals", "recommendations"].includes(t);
    // Flash sale window: the backend decides whether a sale is active.
    // If the window is enabled with dates but already expired, hide the section.
    const flashWindow = config.flashSaleWindow;
    let flashSaleActive = true; // enabled-without-dates => active (no time bound)
    let flashSaleEndsAt = null;
    if (flashWindow?.enabled && flashWindow.start && flashWindow.end) {
        const now = Date.now();
        const start = new Date(flashWindow.start).getTime();
        const end = new Date(flashWindow.end).getTime();
        flashSaleActive = now >= start && now < end;
        flashSaleEndsAt = flashWindow.end;
    }
    const flashExpired = flashWindow?.enabled && flashWindow.start && flashWindow.end && !flashSaleActive;
    const productResults = {};
    await Promise.all(Object.keys(limits).map(async (type) => {
        if (!needsProducts(type))
            return;
        const limit = limits[type];
        switch (type) {
            case "flash_deals":
                // Expired window → no flash deals; otherwise flag-based list.
                productResults[type] = flashExpired ? [] : await resolveLabelProducts("flash_sale", limit);
                break;
            case "featured":
                productResults[type] = await resolveLabelProducts("featured", limit);
                break;
            case "trending":
                productResults[type] = await resolveTrending(limit);
                break;
            case "new_arrivals":
                productResults[type] = await resolveLabelProducts("new_arrival", limit);
                break;
            case "best_sellers":
                productResults[type] = await resolveBestSellers(limit);
                break;
            case "recommendations":
                productResults[type] = await resolveRecommendations(userId, limit);
                break;
        }
    }));
    const sections = enabled.map((section) => {
        const data = {};
        switch (section.type) {
            case "hero":
                data.slides = slides;
                break;
            case "trust_strip":
                data.items = config.trustStrip;
                break;
            case "categories":
                data.items = categoryRows;
                break;
            case "brands":
                data.items = brandRows;
                break;
            case "collections":
                data.items = collectionRows;
                break;
            case "promo_banner": {
                // Multiple promo_banner sections share the banner pool round-robin so
                // each banner is shown exactly once across the homepage.
                const promoIdx = promoBannerSections.indexOf(section);
                const promoCount = promoBannerSections.length;
                data.items = promoCount > 1 ? bannerRows.filter((_, i) => i % promoCount === promoIdx) : bannerRows;
                break;
            }
            case "why_choose_us":
                data.items = config.whyChooseUs;
                break;
            case "newsletter":
                data.settings = config.newsletter;
                break;
            case "reviews":
                data.items = reviewRows;
                break;
            default:
                if (needsProducts(section.type))
                    data.items = productResults[section.type] || [];
                break;
        }
        return { ...section, data };
    });
    return {
        announcement: config.announcement,
        heroSlides: slides,
        flashSaleWindow: {
            ...config.flashSaleWindow,
            isActive: flashSaleActive,
            endsAt: flashSaleEndsAt,
        },
        popularSearches: config.popularSearches,
        sections,
    };
};
exports.getHomepage = getHomepage;
// ==================== NEWSLETTER ====================
const subscribeNewsletter = async (email, source) => {
    const normalized = String(email).trim().toLowerCase();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalized)) {
        throw new AppError_1.AppError(400, "A valid email address is required");
    }
    const existing = await db_1.db.select({ id: schema_1.newsletters.id }).from(schema_1.newsletters).where((0, drizzle_orm_1.eq)(schema_1.newsletters.email, normalized)).limit(1);
    if (existing[0]) {
        return { email: normalized, alreadySubscribed: true };
    }
    await db_1.db.insert(schema_1.newsletters).values({ email: normalized, source: source || "homepage" });
    return { email: normalized, alreadySubscribed: false };
};
exports.subscribeNewsletter = subscribeNewsletter;
const getSubscribers = async () => {
    return db_1.db.select().from(schema_1.newsletters).orderBy((0, drizzle_orm_1.desc)(schema_1.newsletters.subscribedAt));
};
exports.getSubscribers = getSubscribers;
//# sourceMappingURL=homepage.service.js.map