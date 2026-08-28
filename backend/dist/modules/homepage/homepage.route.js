"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const homepage_controller_1 = require("./homepage.controller");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const homepage_schema_1 = require("./homepage.schema");
const router = (0, express_1.Router)();
/**
 * Cache-Control for the public homepage aggregate.
 * - s-maxage=60: Vercel/CDN edge caches for 60 seconds (acts like ISR revalidate=60)
 * - stale-while-revalidate=300: serve stale while revalidating for up to 5 minutes
 * - public: safe to cache at the CDN layer (no auth required for this route)
 * Browser clients with RTK Query already cache for keepUnusedDataFor=900 after
 * the first fetch, so repeat SPA navigations never hit the network.
 */
const homepageCacheMiddleware = (_req, res, next) => {
    res.setHeader("Cache-Control", "public, s-maxage=60, stale-while-revalidate=300");
    next();
};
// Public
router.get("/", homepageCacheMiddleware, (0, asyncHandler_1.asyncHandler)(homepage_controller_1.getHomepage));
router.get("/config", homepageCacheMiddleware, (0, asyncHandler_1.asyncHandler)(homepage_controller_1.getConfig));
router.post("/newsletter/subscribe", (0, validate_1.validate)(homepage_schema_1.subscribeNewsletterSchema), (0, asyncHandler_1.asyncHandler)(homepage_controller_1.subscribeNewsletter));
// Admin only
router.get("/admin/config", auth_1.authMiddleware, auth_1.adminOnly, (0, asyncHandler_1.asyncHandler)(homepage_controller_1.getConfig));
router.put("/admin/config", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(homepage_schema_1.saveConfigSchema), (0, asyncHandler_1.asyncHandler)(homepage_controller_1.saveConfig));
router.post("/admin/reset-defaults", auth_1.authMiddleware, auth_1.adminOnly, (0, asyncHandler_1.asyncHandler)(homepage_controller_1.resetConfig));
router.get("/admin/subscribers", auth_1.authMiddleware, auth_1.adminOnly, (0, asyncHandler_1.asyncHandler)(homepage_controller_1.getSubscribers));
exports.default = router;
//# sourceMappingURL=homepage.route.js.map