"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const express_rate_limit_1 = __importDefault(require("express-rate-limit"));
const order_controller_1 = require("./order.controller");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const order_schema_1 = require("./order.schema");
const router = (0, express_1.Router)();
const orderCreateLimiter = (0, express_rate_limit_1.default)({
    windowMs: 15 * 60 * 1000,
    max: 30,
    standardHeaders: true,
    legacyHeaders: false,
    message: { success: false, message: "Too many order attempts, please try again later." },
});
// PUBLIC - Guest checkout (NO AUTH, optional Bearer token = signed-in checkout)
router.post("/create", orderCreateLimiter, (0, validate_1.validate)(order_schema_1.createOrderSchema), (0, asyncHandler_1.asyncHandler)(order_controller_1.create));
// PUBLIC - Track order by order ID + phone
router.post("/track", (0, validate_1.validate)(order_schema_1.trackOrderSchema), (0, asyncHandler_1.asyncHandler)(order_controller_1.trackOrder));
// CUSTOMER - Signed-in user's own orders
router.get("/my-orders", auth_1.authMiddleware, (0, asyncHandler_1.asyncHandler)(order_controller_1.getMyOrders));
// CUSTOMER - Get invoice for own order (authenticated, checks ownership)
router.get("/:id/my-invoice", auth_1.authMiddleware, (0, validate_1.validate)(order_schema_1.orderIdSchema), (0, asyncHandler_1.asyncHandler)(order_controller_1.getCustomerInvoice));
// Admin routes
router.get("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("orders.view"), (0, validate_1.validate)(order_schema_1.orderListSchema), (0, asyncHandler_1.asyncHandler)(order_controller_1.getAll));
router.get("/stats", auth_1.authMiddleware, (0, auth_1.requirePermission)("orders.view"), (0, asyncHandler_1.asyncHandler)(order_controller_1.getStats));
router.get("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("orders.view"), (0, validate_1.validate)(order_schema_1.orderIdSchema), (0, asyncHandler_1.asyncHandler)(order_controller_1.getById));
router.get("/:id/invoice", auth_1.authMiddleware, (0, auth_1.requirePermission)("orders.view"), (0, validate_1.validate)(order_schema_1.orderIdSchema), (0, asyncHandler_1.asyncHandler)(order_controller_1.getInvoice));
router.patch("/:id/status", auth_1.authMiddleware, (0, auth_1.requirePermission)("orders.update"), (0, validate_1.validate)(order_schema_1.updateOrderStatusSchema), (0, asyncHandler_1.asyncHandler)(order_controller_1.updateStatus));
router.patch("/:id/payment/verify", auth_1.authMiddleware, (0, auth_1.requirePermission)("orders.update"), (0, validate_1.validate)(order_schema_1.verifyPaymentSchema), (0, asyncHandler_1.asyncHandler)(order_controller_1.verifyPayment));
router.patch("/:id/admin-note", auth_1.authMiddleware, (0, auth_1.requirePermission)("orders.update"), (0, validate_1.validate)(order_schema_1.adminNoteSchema), (0, asyncHandler_1.asyncHandler)(order_controller_1.addAdminNote));
router.delete("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("orders.delete"), (0, validate_1.validate)(order_schema_1.orderIdSchema), (0, asyncHandler_1.asyncHandler)(order_controller_1.remove));
exports.default = router;
//# sourceMappingURL=order.route.js.map