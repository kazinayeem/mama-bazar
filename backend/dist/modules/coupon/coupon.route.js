"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const coupon_controller_1 = require("./coupon.controller");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const coupon_schema_1 = require("./coupon.schema");
const router = (0, express_1.Router)();
// Public
router.post("/validate", (0, validate_1.validate)(coupon_schema_1.validateCouponSchema), (0, asyncHandler_1.asyncHandler)(coupon_controller_1.validateCoupon));
// Admin
router.get("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("coupons.view"), (0, asyncHandler_1.asyncHandler)(coupon_controller_1.getAll));
router.get("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("coupons.view"), (0, validate_1.validate)(coupon_schema_1.couponIdSchema), (0, asyncHandler_1.asyncHandler)(coupon_controller_1.getById));
router.post("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("coupons.create"), (0, validate_1.validate)(coupon_schema_1.createCouponSchema), (0, asyncHandler_1.asyncHandler)(coupon_controller_1.create));
router.put("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("coupons.update"), (0, validate_1.validate)(coupon_schema_1.updateCouponSchema), (0, asyncHandler_1.asyncHandler)(coupon_controller_1.update));
router.delete("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("coupons.delete"), (0, validate_1.validate)(coupon_schema_1.couponIdSchema), (0, asyncHandler_1.asyncHandler)(coupon_controller_1.remove));
exports.default = router;
//# sourceMappingURL=coupon.route.js.map