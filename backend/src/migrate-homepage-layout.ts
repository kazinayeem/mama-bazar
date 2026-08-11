/**
 * One-off migration: reorder the stored homepage config sections to the new
 * Top-Tech-Zone-inspired layout (new arrivals before promo, second promo
 * banner, brands/collections repositioned) without touching hero slides,
 * content or any other settings.
 */
import { db } from "./config/db";
import { siteSettings } from "./config/schema";
import { eq, desc } from "drizzle-orm";

const CONFIG_KEY = "homepage_config";

const CANONICAL_IDS = [
  "hero",
  "trust_strip",
  "categories",
  "new_arrivals",
  "promo_banner",
  "featured",
  "brands",
  "promo_banner_2",
  "collections",
  "flash_deals",
  "best_sellers",
  "trending",
  "recommendations",
  "why_choose_us",
  "reviews",
  "newsletter",
];

const DEFAULTS: Record<string, Record<string, unknown>> = {
  hero: {},
  trust_strip: { type: "trust_strip", enabled: true, title: "Why shop with us" },
  categories: {
    type: "categories",
    enabled: true,
    title: "Explore Categories",
    subtitle: "Discover our wide range of products across all categories.",
    limit: 12,
  },
  new_arrivals: {
    type: "new_arrivals",
    enabled: true,
    title: "New arrivals",
    subtitle: "Fresh products just added to the store.",
    limit: 12,
  },
  promo_banner: { type: "promo_banner", enabled: true },
  featured: {
    type: "featured",
    enabled: true,
    title: "Featured products",
    subtitle: "Handpicked favourites from our catalogue.",
    limit: 12,
  },
  brands: {
    type: "brands",
    enabled: true,
    title: "Trusted brands",
    subtitle: "100% authentic products from official distributors.",
    limit: 10,
  },
  promo_banner_2: { id: "promo_banner_2", type: "promo_banner", enabled: true },
  collections: {
    type: "collections",
    enabled: true,
    title: "Featured collections",
    subtitle: "Complete setups built for every lifestyle.",
    limit: 6,
  },
  flash_deals: {
    type: "flash_deals",
    enabled: true,
    title: "Flash Deals",
    subtitle: "Limited-time prices. When they're gone, they're gone.",
    limit: 12,
    background: "muted",
  },
  best_sellers: {
    type: "best_sellers",
    enabled: true,
    title: "Best sellers",
    subtitle: "The most-ordered products right now.",
    limit: 12,
  },
  trending: {
    type: "trending",
    enabled: true,
    title: "Trending right now",
    subtitle: "The products everyone is talking about.",
    limit: 10,
    background: "muted",
  },
  recommendations: {
    type: "recommendations",
    enabled: true,
    title: "Recommended for you",
    subtitle: "Picked based on what you've browsed and bought.",
    limit: 10,
  },
  why_choose_us: { type: "why_choose_us", enabled: true, title: "Why choose Mama Bazar" },
  reviews: {
    type: "reviews",
    enabled: true,
    title: "What customers say",
    subtitle: "Real feedback from verified buyers.",
    limit: 8,
  },
  newsletter: { type: "newsletter", enabled: true, title: "Never miss a deal" },
};

const run = async () => {
  const rows = await db.select().from(siteSettings).where(eq(siteSettings.key, CONFIG_KEY)).orderBy(desc(siteSettings.id)).limit(1);
  const row = rows[0];
  if (!row?.value) {
    console.log("No saved homepage config found — defaults will be used.");
    return;
  }

  const config = JSON.parse(row.value);
  if (!config || typeof config !== "object" || !Array.isArray(config.sections)) {
    console.log("Saved homepage config has no sections array — skipping.");
    return;
  }

  const byId = new Map<string, any>();
  for (const section of config.sections as any[]) {
    if (section?.id) byId.set(section.id, section);
  }

  // Ensure every canonical section exists, merged with defaults.
  for (const id of CANONICAL_IDS) {
    const existing = byId.get(id);
    const def = DEFAULTS[id] || {};
    if (existing) {
      byId.set(id, { id, ...def, ...existing, type: def.type || existing.type });
    } else {
      byId.set(id, { id, ...def });
    }
  }

  const reordered = CANONICAL_IDS.map((id) => byId.get(id));
  const known = new Set(CANONICAL_IDS);
  const extras = config.sections
    .filter((s: any) => s?.id && !known.has(s.id))
    .map((s: any) => (byId.get(s.id) ? byId.get(s.id) : s));
  config.sections = [...reordered, ...extras];

  await db.update(siteSettings).set({ value: JSON.stringify(config) }).where(eq(siteSettings.id, row.id));

  console.log(
    "Homepage config reordered:",
    config.sections.map((s: any) => `${s.id}(${s.enabled ? "on" : "off"})`).join(" -> ")
  );
};

run()
  .then(() => process.exit(0))
  .catch((err) => {
    console.error(err);
    process.exit(1);
  });
