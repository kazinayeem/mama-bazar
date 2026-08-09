"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const checkout_notice_controller_1 = require("./checkout-notice.controller");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const checkout_notice_schema_1 = require("./checkout-notice.schema");
const router = (0, express_1.Router)();
// Public
router.get("/public", (0, asyncHandler_1.asyncHandler)(checkout_notice_controller_1.getActiveNotices));
// Admin
router.get("/", auth_1.authMiddleware, (0, asyncHandler_1.asyncHandler)(checkout_notice_controller_1.getAll));
router.get("/:id", auth_1.authMiddleware, (0, validate_1.validate)(checkout_notice_schema_1.checkoutNoticeIdSchema), (0, asyncHandler_1.asyncHandler)(checkout_notice_controller_1.getById));
router.post("/", auth_1.authMiddleware, (0, validate_1.validate)(checkout_notice_schema_1.createCheckoutNoticeSchema), (0, asyncHandler_1.asyncHandler)(checkout_notice_controller_1.create));
router.put("/:id", auth_1.authMiddleware, (0, validate_1.validate)(checkout_notice_schema_1.updateCheckoutNoticeSchema), (0, asyncHandler_1.asyncHandler)(checkout_notice_controller_1.update));
router.delete("/:id", auth_1.authMiddleware, (0, validate_1.validate)(checkout_notice_schema_1.checkoutNoticeIdSchema), (0, asyncHandler_1.asyncHandler)(checkout_notice_controller_1.remove));
exports.default = router;
//# sourceMappingURL=checkout-notice.route.js.map