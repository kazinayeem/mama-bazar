import { Router } from "express";
import { validateCoupon, getAll, getById, create, update, remove } from "./coupon.controller";
import { authMiddleware } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { createCouponSchema, updateCouponSchema, validateCouponSchema, couponIdSchema } from "./coupon.schema";

const router = Router();

// Public
router.post("/validate", validate(validateCouponSchema), asyncHandler(validateCoupon));

// Admin
router.get("/", authMiddleware, asyncHandler(getAll));
router.get("/:id", authMiddleware, validate(couponIdSchema), asyncHandler(getById));
router.post("/", authMiddleware, validate(createCouponSchema), asyncHandler(create));
router.put("/:id", authMiddleware, validate(updateCouponSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, validate(couponIdSchema), asyncHandler(remove));

export default router;
