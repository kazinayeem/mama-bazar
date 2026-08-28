"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const shipping_controller_1 = require("./shipping.controller");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const shipping_schema_1 = require("./shipping.schema");
const router = (0, express_1.Router)();
// Public
router.get("/public", (0, asyncHandler_1.asyncHandler)(shipping_controller_1.getActiveMethods));
router.post("/estimate", (0, validate_1.validate)(shipping_schema_1.estimateShippingSchema), (0, asyncHandler_1.asyncHandler)(shipping_controller_1.estimateShipping));
// Admin
router.get("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("shipping.view"), (0, asyncHandler_1.asyncHandler)(shipping_controller_1.getAll));
router.get("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("shipping.view"), (0, validate_1.validate)(shipping_schema_1.shippingMethodIdSchema), (0, asyncHandler_1.asyncHandler)(shipping_controller_1.getById));
router.post("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("shipping.manage"), (0, validate_1.validate)(shipping_schema_1.createShippingMethodSchema), (0, asyncHandler_1.asyncHandler)(shipping_controller_1.create));
router.put("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("shipping.manage"), (0, validate_1.validate)(shipping_schema_1.updateShippingMethodSchema), (0, asyncHandler_1.asyncHandler)(shipping_controller_1.update));
router.delete("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("shipping.manage"), (0, validate_1.validate)(shipping_schema_1.shippingMethodIdSchema), (0, asyncHandler_1.asyncHandler)(shipping_controller_1.remove));
exports.default = router;
//# sourceMappingURL=shipping.route.js.map