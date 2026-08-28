import { Router } from "express";
import { getConfig, getAll, getLogs, getById, create, update, remove } from "./tracking.controller";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { createTrackingSchema, updateTrackingSchema, trackingIdSchema } from "./tracking.schema";

const router = Router();

// Public - frontend config (no tokens exposed)
router.get("/config", asyncHandler(getConfig));

// Admin only
router.get("/", authMiddleware, requirePermission("marketing.view"), asyncHandler(getAll));
router.get("/logs", authMiddleware, requirePermission("marketing.view"), asyncHandler(getLogs));
router.get("/:id", authMiddleware, requirePermission("marketing.view"), validate(trackingIdSchema), asyncHandler(getById));
router.post("/", authMiddleware, requirePermission("marketing.manage"), validate(createTrackingSchema), asyncHandler(create));
router.put("/:id", authMiddleware, requirePermission("marketing.manage"), validate(updateTrackingSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, requirePermission("marketing.manage"), validate(trackingIdSchema), asyncHandler(remove));

export default router;
