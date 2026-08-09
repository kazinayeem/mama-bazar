import { Router } from "express";
import { getHomepage, getConfig, saveConfig, resetConfig, subscribeNewsletter, getSubscribers } from "./homepage.controller";
import { authMiddleware, adminOnly } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { saveConfigSchema, subscribeNewsletterSchema } from "./homepage.schema";

const router = Router();

// Public
router.get("/", asyncHandler(getHomepage));
router.post("/newsletter/subscribe", validate(subscribeNewsletterSchema), asyncHandler(subscribeNewsletter));

// Admin only
router.get("/admin/config", authMiddleware, adminOnly, asyncHandler(getConfig));
router.put("/admin/config", authMiddleware, adminOnly, validate(saveConfigSchema), asyncHandler(saveConfig));
router.post("/admin/reset-defaults", authMiddleware, adminOnly, asyncHandler(resetConfig));
router.get("/admin/subscribers", authMiddleware, adminOnly, asyncHandler(getSubscribers));

export default router;
