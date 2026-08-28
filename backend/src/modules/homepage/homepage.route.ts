import { Router } from "express";
import { getHomepage, getConfig, saveConfig, resetConfig, subscribeNewsletter, getSubscribers } from "./homepage.controller";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { saveConfigSchema, subscribeNewsletterSchema } from "./homepage.schema";

const router = Router();

/**
 * Cache-Control for the public homepage aggregate.
 * - s-maxage=60: Vercel/CDN edge caches for 60 seconds (acts like ISR revalidate=60)
 * - stale-while-revalidate=300: serve stale while revalidating for up to 5 minutes
 * - public: safe to cache at the CDN layer (no auth required for this route)
 * Browser clients with RTK Query already cache for keepUnusedDataFor=900 after
 * the first fetch, so repeat SPA navigations never hit the network.
 */
const homepageCacheMiddleware = (_req: any, res: any, next: any) => {
  res.setHeader("Cache-Control", "public, s-maxage=60, stale-while-revalidate=300");
  next();
};

// Public
router.get("/", homepageCacheMiddleware, asyncHandler(getHomepage));
router.get("/config", homepageCacheMiddleware, asyncHandler(getConfig));
router.post("/newsletter/subscribe", validate(subscribeNewsletterSchema), asyncHandler(subscribeNewsletter));

// Admin only
router.get("/admin/config", authMiddleware, requirePermission("homepage.view"), asyncHandler(getConfig));
router.put("/admin/config", authMiddleware, requirePermission("homepage.manage"), validate(saveConfigSchema), asyncHandler(saveConfig));
router.post("/admin/reset-defaults", authMiddleware, requirePermission("homepage.manage"), asyncHandler(resetConfig));
router.get("/admin/subscribers", authMiddleware, requirePermission("marketing.view"), asyncHandler(getSubscribers));

export default router;

