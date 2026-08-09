import { db } from "../../config/db";
import {
  products,
  categories,
  brands,
  collections,
  banners,
  reviews,
  orders,
  orderItems,
  newsletters,
} from "../../config/schema";
import { eq, and, desc, asc, inArray, notInArray, isNull, or, sql } from "drizzle-orm";
import * as settingsService from "../settings/settings.service";
import { fullQuery, formatProductRow, fetchRatingMap } from "../product/product.service";
import { AppError } from "../../utils/AppError";
import type { HomepageConfig, HomepageSectionConfig } from "./homepage.interface";

const CONFIG_KEY = "homepage_config";
const LEGACY_SLIDES_KEY = "hero_slides";

// ==================== DEFAULT CONFIG ====================

const DEFAULT_SECTIONS: HomepageSectionConfig[] = [
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
    title: "Shop by category",
    subtitle: "Everything you need to upgrade your home.",
    limit: 12,
  },
  {
    id: "promo_banner",
    type: "promo_banner",
    enabled: true,
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
    id: "featured",
    type: "featured",
    enabled: true,
    title: "Featured products",
    subtitle: "Handpicked favourites from our catalogue.",
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
    id: "brands",
    type: "brands",
    enabled: true,
    title: "Trusted brands",
    subtitle: "100% authentic products from official distributors.",
    limit: 10,
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
    id: "trending",
    type: "trending",
    enabled: true,
    title: "Trending right now",
    subtitle: "The products everyone is talking about.",
    limit: 10,
    background: "muted",
  },
  {
    id: "new_arrivals",
    type: "new_arrivals",
    enabled: true,
    title: "New arrivals",
    subtitle: "Fresh products just added to the store.",
    limit: 10,
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
    title: "Why choose GhorerBazar",
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

const DEFAULT_CONFIG: HomepageConfig = {
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

const mergeDeep = (base: HomepageConfig, saved: any): HomepageConfig => {
  if (!saved || typeof saved !== "object") return base;
  const config: HomepageConfig = {
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

export const getConfig = async (): Promise<HomepageConfig> => {
  const saved = await settingsService.getJSON<any>(CONFIG_KEY, null);
  return mergeDeep(DEFAULT_CONFIG, saved);
};

export const saveConfig = async (config: HomepageConfig): Promise<HomepageConfig> => {
  const merged = mergeDeep(DEFAULT_CONFIG, config);
  await settingsService.setJSON(CONFIG_KEY, merged);
  return merged;
};

export const resetConfig = async (): Promise<HomepageConfig> => {
  await settingsService.setJSON(CONFIG_KEY, DEFAULT_CONFIG);
  return DEFAULT_CONFIG;
};

// ==================== DATA RESOLVERS ====================

const sanitizeLimit = (value: number | undefined, fallback: number) => {
  const n = Number(value);
  if (!Number.isFinite(n) || n <= 0) return fallback;
  return Math.min(Math.floor(n), 24);
};

const fetchProducts = async (where: any, limit: number, orderBy?: any) => {
  const rows = await fullQuery().where(where).orderBy(orderBy ?? desc(products.createdAt)).limit(limit);
  const ratingMap = await fetchRatingMap(rows.map((row) => row.id));
  return rows.map((row) => formatProductRow(row, ratingMap.get(row.id)));
};

const resolveLabelProducts = async (label: "featured" | "trending" | "new_arrival" | "flash_sale", limit: number) => {
  const colMap: Record<string, any> = {
    featured: products.isFeatured,
    trending: products.isTrending,
    new_arrival: products.isNewArrival,
    flash_sale: products.isFlashSale,
  };
  const orderBy = label === "flash_sale" ? desc(sql`COALESCE(${products.discount}, 0)`) : desc(products.createdAt);

  if (label === "new_arrival") {
    // Real "new arrivals": recent products (created within 30 days) or
    // admin-boosted via is_new_arrival, always sorted by createdAt (newest first).
    // If nothing is recent (e.g. all stock is older), fall back to newest products.
    const where = and(
      eq(products.status, "active"),
      or(eq(products.isNewArrival, true), sql`${products.createdAt} >= (NOW() - INTERVAL 30 DAY)`)
    );
    const recent = await fetchProducts(where, limit, desc(products.createdAt));
    if (recent.length > 0) return recent;
    return fetchProducts(eq(products.status, "active"), limit, desc(products.createdAt));
  }

  return fetchProducts(and(eq(products.status, "active"), eq(colMap[label], true)), limit, orderBy);
};

/**
 * Trending = real activity: products ranked by how many distinct orders they
 * appear in (most frequently purchased), excluding cancelled/returned/refunded.
 * Falls back to the admin `is_trending` flag, then newest.
 */
const resolveTrending = async (limit: number) => {
  const excluded = ["cancelled", "returned", "refunded"] as any[];
  const aggRows = await db
    .select({
      productId: orderItems.productId,
      orderCount: sql<number>`COUNT(DISTINCT ${orderItems.orderId})`,
    })
    .from(orderItems)
    .leftJoin(orders, eq(orderItems.orderId, orders.id))
    .where(notInArray(orders.status, excluded))
    .groupBy(orderItems.productId)
    .orderBy(desc(sql`COUNT(DISTINCT ${orderItems.orderId})`), desc(sql`SUM(${orderItems.quantity})`))
    .limit(limit);

  const ids = aggRows.map((r) => r.productId);
  if (ids.length > 0) {
    const rows = await fullQuery()
      .where(and(eq(products.status, "active"), inArray(products.id, ids)))
      .limit(limit);
    const orderMap = new Map(aggRows.map((r, i) => [r.productId, i]));
    rows.sort((a, b) => (orderMap.get(a.id) ?? Number.MAX_SAFE_INTEGER) - (orderMap.get(b.id) ?? Number.MAX_SAFE_INTEGER));
    const ratingMap = await fetchRatingMap(rows.map((row) => row.id));
    return rows.map((row) => formatProductRow(row, ratingMap.get(row.id)));
  }

  const flagged = await fetchProducts(
    and(eq(products.status, "active"), eq(products.isTrending, true)),
    limit,
    desc(products.createdAt)
  );
  if (flagged.length > 0) return flagged;
  return fetchProducts(eq(products.status, "active"), limit, desc(products.createdAt));
};

const resolveBestSellers = async (limit: number) => {
  const excluded = ["cancelled", "returned", "refunded"] as any[];
  const aggRows = await db
    .select({
      productId: orderItems.productId,
      total: sql<number>`SUM(${orderItems.quantity})`,
    })
    .from(orderItems)
    .leftJoin(orders, eq(orderItems.orderId, orders.id))
    .where(notInArray(orders.status, excluded))
    .groupBy(orderItems.productId)
    .orderBy(desc(sql`SUM(${orderItems.quantity})`))
    .limit(limit);

  const ids = aggRows.map((r) => r.productId);
  if (ids.length > 0) {
    const rows = await fullQuery()
      .where(and(eq(products.status, "active"), inArray(products.id, ids)))
      .limit(limit);
    const orderMap = new Map(aggRows.map((r, i) => [r.productId, i]));
    rows.sort((a, b) => (orderMap.get(a.id) ?? Number.MAX_SAFE_INTEGER) - (orderMap.get(b.id) ?? Number.MAX_SAFE_INTEGER));
    const ratingMap = await fetchRatingMap(rows.map((row) => row.id));
    return rows.map((row) => formatProductRow(row, ratingMap.get(row.id)));
  }

  // No sales yet — fall back to the best-seller flag, then featured, then newest
  const fallback = await fetchProducts(
    and(eq(products.status, "active"), eq(products.isBestSeller, true)),
    limit,
    desc(products.createdAt)
  );
  if (fallback.length > 0) return fallback;
  return fetchProducts(eq(products.status, "active"), limit, desc(products.createdAt));
};

const resolveRecommendations = async (userId: number | null, limit: number) => {
  if (userId) {
    const catRows = await db
      .select({ categoryId: sql<number | null>`COALESCE(${products.categoryId}, ${products.subCategoryId})` })
      .from(orderItems)
      .leftJoin(orders, eq(orderItems.orderId, orders.id))
      .leftJoin(products, eq(orderItems.productId, products.id))
      .where(and(eq(orders.userId, userId), notInArray(orders.status, ["cancelled", "returned", "refunded"] as any[])))
      .groupBy(sql`COALESCE(${products.categoryId}, ${products.subCategoryId})`)
      .limit(6);
    const categoryIds = catRows.map((r) => r.categoryId).filter((id): id is number => id !== null);
    if (categoryIds.length > 0) {
      const rows = await fetchProducts(
        and(eq(products.status, "active"), inArray(products.categoryId, categoryIds)),
        limit
      );
      if (rows.length >= 4) return rows;
    }
  }
  return resolveBestSellers(limit);
};

const resolveCategories = async (limit: number) => {
  const rows = await db
    .select({
      id: categories.id,
      name: categories.name,
      slug: categories.slug,
      image: categories.image,
      icon: categories.icon,
      thumbnail: categories.thumbnail,
      parentId: categories.parentId,
    })
    .from(categories)
    .where(and(eq(categories.status, "active"), isNull(categories.parentId)))
    .orderBy(asc(categories.sortOrder))
    .limit(limit);
  return rows;
};

const resolveBrands = async (limit: number) => {
  const rows = await db
    .select({
      id: brands.id,
      name: brands.name,
      slug: brands.slug,
      logo: brands.logo,
      featured: brands.featured,
      status: brands.status,
      createdAt: brands.createdAt,
    })
    .from(brands)
    .where(eq(brands.status, "active"))
    .orderBy(desc(brands.featured), asc(brands.id))
    .limit(limit);
  return rows;
};

const resolveCollections = async (limit: number) => {
  const rows = await db
    .select({
      id: collections.id,
      name: collections.name,
      slug: collections.slug,
      description: collections.description,
      image: collections.image,
      featured: collections.featured,
      sortOrder: collections.sortOrder,
      status: collections.status,
      createdAt: collections.createdAt,
    })
    .from(collections)
    .where(eq(collections.status, "active"))
    .orderBy(desc(collections.featured), asc(collections.sortOrder))
    .limit(limit);
  return rows;
};

const resolveBanners = async () => {
  const rows = await db
    .select()
    .from(banners)
    .where(and(eq(banners.status, "active"), inArray(banners.position, ["banner", "promo"])))
    .orderBy(desc(banners.priority));
  return rows;
};

const resolveReviews = async (limit: number) => {
  const rows = await db
    .select({
      id: reviews.id,
      productId: reviews.productId,
      rating: reviews.rating,
      title: reviews.title,
      comment: reviews.comment,
      customerName: reviews.customerName,
      status: reviews.status,
      createdAt: reviews.createdAt,
      productTitle: products.title,
      productSlug: products.slug,
      productImage: sql<string | null>`JSON_UNQUOTE(JSON_EXTRACT(${products.images}, '$[0]'))`,
    })
    .from(reviews)
    .leftJoin(products, eq(reviews.productId, products.id))
    .where(eq(reviews.status, "approved"))
    .orderBy(desc(reviews.createdAt))
    .limit(limit);
  return rows;
};

// ==================== PUBLIC HOMEPAGE AGGREGATE ====================

export const getHomepage = async (userId: number | null) => {
  const config = await getConfig();

  const slides = config.heroSlides
    .filter((s) => s.status === "active")
    .sort((a, b) => b.priority - a.priority);

  // Legacy support: hero_slides was previously a plain string[] of image URLs
  if (slides.length === 0) {
    const legacy = await settingsService.getJSON<string[]>(LEGACY_SLIDES_KEY, []);
    if (Array.isArray(legacy) && legacy.length > 0) {
      legacy.forEach((url, index) => {
        if (typeof url !== "string" || !url) return;
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
  const byType = (type: string) => enabled.find((s) => s.type === type);

  const limits: Record<string, number> = {};
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

  const needsProducts = (t: string) => ["flash_deals", "featured", "best_sellers", "trending", "new_arrivals", "recommendations"].includes(t);

  // Flash sale window: the backend decides whether a sale is active.
  // If the window is enabled with dates but already expired, hide the section.
  const flashWindow = config.flashSaleWindow;
  let flashSaleActive = true; // enabled-without-dates => active (no time bound)
  let flashSaleEndsAt: string | null = null;
  if (flashWindow?.enabled && flashWindow.start && flashWindow.end) {
    const now = Date.now();
    const start = new Date(flashWindow.start).getTime();
    const end = new Date(flashWindow.end).getTime();
    flashSaleActive = now >= start && now < end;
    flashSaleEndsAt = flashWindow.end;
  }
  const flashExpired = flashWindow?.enabled && flashWindow.start && flashWindow.end && !flashSaleActive;

  const productResults: Record<string, any[]> = {};
  await Promise.all(
    Object.keys(limits).map(async (type) => {
      if (!needsProducts(type)) return;
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
    })
  );

  const sections = enabled.map((section) => {
    const data: any = {};
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
      case "promo_banner":
        data.items = bannerRows;
        break;
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
        if (needsProducts(section.type)) data.items = productResults[section.type] || [];
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

// ==================== NEWSLETTER ====================

export const subscribeNewsletter = async (email: string, source?: string) => {
  const normalized = String(email).trim().toLowerCase();
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalized)) {
    throw new AppError(400, "A valid email address is required");
  }
  const existing = await db.select({ id: newsletters.id }).from(newsletters).where(eq(newsletters.email, normalized)).limit(1);
  if (existing[0]) {
    return { email: normalized, alreadySubscribed: true };
  }
  await db.insert(newsletters).values({ email: normalized, source: source || "homepage" });
  return { email: normalized, alreadySubscribed: false };
};

export const getSubscribers = async () => {
  return db.select().from(newsletters).orderBy(desc(newsletters.subscribedAt));
};
