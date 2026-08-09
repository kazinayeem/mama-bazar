"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const category_controller_1 = require("./category.controller");
const uploadMemory_1 = require("../../middleware/uploadMemory");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const category_schema_1 = require("./category.schema");
const router = (0, express_1.Router)();
// Public
router.get("/", (0, asyncHandler_1.asyncHandler)(category_controller_1.getAll));
router.get("/tree", (0, asyncHandler_1.asyncHandler)(category_controller_1.getTree));
router.get("/slug/:slug", (0, validate_1.validate)(category_schema_1.categorySlugSchema), (0, asyncHandler_1.asyncHandler)(category_controller_1.getBySlug));
// Admin (registered before /:id to avoid param capture)
router.get("/admin", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(category_schema_1.categoryListSchema), (0, asyncHandler_1.asyncHandler)(category_controller_1.listAdmin));
router.get("/:id/usage", auth_1.authMiddleware, (0, validate_1.validate)(category_schema_1.categoryIdSchema), (0, asyncHandler_1.asyncHandler)(category_controller_1.getUsage));
// Public
router.get("/:id", (0, validate_1.validate)(category_schema_1.categoryIdSchema), (0, asyncHandler_1.asyncHandler)(category_controller_1.getById));
// Admin
router.post("/", auth_1.authMiddleware, auth_1.adminOnly, uploadMemory_1.uploadMemory.single("image"), (0, validate_1.validate)(category_schema_1.createCategorySchema), (0, asyncHandler_1.asyncHandler)(category_controller_1.create));
router.put("/:id", auth_1.authMiddleware, auth_1.adminOnly, uploadMemory_1.uploadMemory.single("image"), (0, validate_1.validate)(category_schema_1.updateCategorySchema), (0, asyncHandler_1.asyncHandler)(category_controller_1.update));
router.post("/:id/move", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(category_schema_1.categoryMoveSchema), (0, asyncHandler_1.asyncHandler)(category_controller_1.moveProducts));
router.delete("/:id", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(category_schema_1.categoryIdSchema), (0, asyncHandler_1.asyncHandler)(category_controller_1.remove));
exports.default = router;
//# sourceMappingURL=category.route.js.map