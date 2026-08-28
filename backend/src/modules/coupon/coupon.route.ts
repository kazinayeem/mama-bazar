import { Router } from "express";
import { validateCoupon, getAll, getById, create, update, remove } from "./coupon.controller";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { createCouponSchema, updateCouponSchema, validateCouponSchema, couponIdSchema } from "./coupon.schema";

const router = Router();

// Public
router.post("/validate", validate(validateCouponSchema), asyncHandler(validateCoupon));

// Admin
router.get("/", authMiddleware, requirePermission("coupons.view"), asyncHandler(getAll));
router.get("/:id", authMiddleware, requirePermission("coupons.view"), validate(couponIdSchema), asyncHandler(getById));
router.post("/", authMiddleware, requirePermission("coupons.create"), validate(createCouponSchema), asyncHandler(create));
router.put("/:id", authMiddleware, requirePermission("coupons.update"), validate(updateCouponSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, requirePermission("coupons.delete"), validate(couponIdSchema), asyncHandler(remove));

export default router;
