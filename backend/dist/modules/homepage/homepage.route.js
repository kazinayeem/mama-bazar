"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const homepage_controller_1 = require("./homepage.controller");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const homepage_schema_1 = require("./homepage.schema");
const router = (0, express_1.Router)();
// Public
router.get("/", (0, asyncHandler_1.asyncHandler)(homepage_controller_1.getHomepage));
router.post("/newsletter/subscribe", (0, validate_1.validate)(homepage_schema_1.subscribeNewsletterSchema), (0, asyncHandler_1.asyncHandler)(homepage_controller_1.subscribeNewsletter));
// Admin only
router.get("/admin/config", auth_1.authMiddleware, auth_1.adminOnly, (0, asyncHandler_1.asyncHandler)(homepage_controller_1.getConfig));
router.put("/admin/config", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(homepage_schema_1.saveConfigSchema), (0, asyncHandler_1.asyncHandler)(homepage_controller_1.saveConfig));
router.post("/admin/reset-defaults", auth_1.authMiddleware, auth_1.adminOnly, (0, asyncHandler_1.asyncHandler)(homepage_controller_1.resetConfig));
router.get("/admin/subscribers", auth_1.authMiddleware, auth_1.adminOnly, (0, asyncHandler_1.asyncHandler)(homepage_controller_1.getSubscribers));
exports.default = router;
//# sourceMappingURL=homepage.route.js.map