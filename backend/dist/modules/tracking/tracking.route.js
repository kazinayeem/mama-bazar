"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const tracking_controller_1 = require("./tracking.controller");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const tracking_schema_1 = require("./tracking.schema");
const router = (0, express_1.Router)();
// Public - frontend config (no tokens exposed)
router.get("/config", (0, asyncHandler_1.asyncHandler)(tracking_controller_1.getConfig));
// Admin only
router.get("/", auth_1.authMiddleware, auth_1.adminOnly, (0, asyncHandler_1.asyncHandler)(tracking_controller_1.getAll));
router.get("/logs", auth_1.authMiddleware, auth_1.adminOnly, (0, asyncHandler_1.asyncHandler)(tracking_controller_1.getLogs));
router.get("/:id", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(tracking_schema_1.trackingIdSchema), (0, asyncHandler_1.asyncHandler)(tracking_controller_1.getById));
router.post("/", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(tracking_schema_1.createTrackingSchema), (0, asyncHandler_1.asyncHandler)(tracking_controller_1.create));
router.put("/:id", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(tracking_schema_1.updateTrackingSchema), (0, asyncHandler_1.asyncHandler)(tracking_controller_1.update));
router.delete("/:id", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(tracking_schema_1.trackingIdSchema), (0, asyncHandler_1.asyncHandler)(tracking_controller_1.remove));
exports.default = router;
//# sourceMappingURL=tracking.route.js.map