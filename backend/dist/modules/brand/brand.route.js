"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const brand_controller_1 = require("./brand.controller");
const uploadMemory_1 = require("../../middleware/uploadMemory");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const brand_schema_1 = require("./brand.schema");
const router = (0, express_1.Router)();
// Public
router.get("/", (0, asyncHandler_1.asyncHandler)(brand_controller_1.getAll));
router.get("/slug/:slug", (0, asyncHandler_1.asyncHandler)(brand_controller_1.getBySlug));
// Admin (before /:id)
router.get("/admin", auth_1.authMiddleware, (0, auth_1.requirePermission)("brands.view"), (0, validate_1.validate)(brand_schema_1.brandListSchema), (0, asyncHandler_1.asyncHandler)(brand_controller_1.listAdmin));
router.get("/:id/usage", auth_1.authMiddleware, (0, auth_1.requirePermission)("brands.view"), (0, validate_1.validate)(brand_schema_1.brandIdSchema), (0, asyncHandler_1.asyncHandler)(brand_controller_1.getUsage));
// Public
router.get("/:id", (0, asyncHandler_1.asyncHandler)(brand_controller_1.getById));
// Admin
router.post("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("brands.create"), uploadMemory_1.uploadMemory.single("logo"), (0, validate_1.validate)(brand_schema_1.createBrandSchema), (0, asyncHandler_1.asyncHandler)(brand_controller_1.create));
router.put("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("brands.update"), uploadMemory_1.uploadMemory.single("logo"), (0, validate_1.validate)(brand_schema_1.updateBrandSchema), (0, asyncHandler_1.asyncHandler)(brand_controller_1.update));
router.post("/:id/move", auth_1.authMiddleware, (0, auth_1.requirePermission)("brands.update"), (0, validate_1.validate)(brand_schema_1.brandMoveSchema), (0, asyncHandler_1.asyncHandler)(brand_controller_1.moveProducts));
router.delete("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("brands.delete"), (0, validate_1.validate)(brand_schema_1.brandIdSchema), (0, asyncHandler_1.asyncHandler)(brand_controller_1.remove));
exports.default = router;
//# sourceMappingURL=brand.route.js.map