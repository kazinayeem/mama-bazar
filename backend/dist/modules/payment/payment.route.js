"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const payment_controller_1 = require("./payment.controller");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const payment_schema_1 = require("./payment.schema");
const router = (0, express_1.Router)();
// Public
router.get("/public", (0, asyncHandler_1.asyncHandler)(payment_controller_1.getActiveMethods));
// Admin
router.get("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("payment_methods.view"), (0, asyncHandler_1.asyncHandler)(payment_controller_1.getAll));
router.get("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("payment_methods.view"), (0, validate_1.validate)(payment_schema_1.paymentMethodIdSchema), (0, asyncHandler_1.asyncHandler)(payment_controller_1.getById));
router.post("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("payment_methods.manage"), (0, validate_1.validate)(payment_schema_1.createPaymentMethodSchema), (0, asyncHandler_1.asyncHandler)(payment_controller_1.create));
router.put("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("payment_methods.manage"), (0, validate_1.validate)(payment_schema_1.updatePaymentMethodSchema), (0, asyncHandler_1.asyncHandler)(payment_controller_1.update));
router.put("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("payment_methods.manage"), (0, validate_1.validate)(payment_schema_1.paymentMethodsStatusSchema), (0, asyncHandler_1.asyncHandler)(payment_controller_1.updateStatuses));
router.delete("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("payment_methods.manage"), (0, validate_1.validate)(payment_schema_1.paymentMethodIdSchema), (0, asyncHandler_1.asyncHandler)(payment_controller_1.remove));
exports.default = router;
//# sourceMappingURL=payment.route.js.map