import { Router } from "express";
import { getConfig, getAll, getLogs, getById, create, update, remove } from "./tracking.controller";
import { authMiddleware, adminOnly } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { createTrackingSchema, updateTrackingSchema, trackingIdSchema } from "./tracking.schema";

const router = Router();

// Public - frontend config (no tokens exposed)
router.get("/config", asyncHandler(getConfig));

// Admin only
router.get("/", authMiddleware, adminOnly, asyncHandler(getAll));
router.get("/logs", authMiddleware, adminOnly, asyncHandler(getLogs));
router.get("/:id", authMiddleware, adminOnly, validate(trackingIdSchema), asyncHandler(getById));
router.post("/", authMiddleware, adminOnly, validate(createTrackingSchema), asyncHandler(create));
router.put("/:id", authMiddleware, adminOnly, validate(updateTrackingSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, adminOnly, validate(trackingIdSchema), asyncHandler(remove));

export default router;
