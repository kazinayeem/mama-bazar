"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const product_controller_1 = require("./product.controller");
const uploadMemory_1 = require("../../middleware/uploadMemory");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const product_schema_1 = require("./product.schema");
const router = (0, express_1.Router)();
// Public
router.get("/", (0, validate_1.validate)(product_schema_1.productListSchema), (0, asyncHandler_1.asyncHandler)(product_controller_1.getAll));
router.get("/slug/:slug", (0, validate_1.validate)(product_schema_1.productSlugSchema), (0, asyncHandler_1.asyncHandler)(product_controller_1.getBySlug));
router.get("/export/csv", auth_1.authMiddleware, auth_1.adminOnly, (0, asyncHandler_1.asyncHandler)(product_controller_1.exportCsv));
router.get("/:id", (0, validate_1.validate)(product_schema_1.productIdSchema), (0, asyncHandler_1.asyncHandler)(product_controller_1.getById));
router.get("/:id/related", (0, validate_1.validate)(product_schema_1.productIdSchema), (0, asyncHandler_1.asyncHandler)(product_controller_1.getRelated));
// Admin
router.post("/", auth_1.authMiddleware, uploadMemory_1.uploadMemory.array("images", 10), (0, validate_1.validate)(product_schema_1.createProductSchema), (0, asyncHandler_1.asyncHandler)(product_controller_1.create));
router.post("/import/csv", auth_1.authMiddleware, auth_1.adminOnly, (0, asyncHandler_1.asyncHandler)(product_controller_1.importCsv));
router.post("/bulk", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(product_schema_1.bulkProductSchema), (0, asyncHandler_1.asyncHandler)(product_controller_1.bulk));
router.post("/:id/duplicate", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(product_schema_1.productIdSchema), (0, asyncHandler_1.asyncHandler)(product_controller_1.duplicate));
router.post("/:id/draft", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(product_schema_1.saveDraftSchema), (0, asyncHandler_1.asyncHandler)(product_controller_1.saveDraft));
router.put("/:id", auth_1.authMiddleware, uploadMemory_1.uploadMemory.array("images", 10), (0, validate_1.validate)(product_schema_1.updateProductSchema), (0, asyncHandler_1.asyncHandler)(product_controller_1.update));
router.delete("/:id", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(product_schema_1.productIdSchema), (0, asyncHandler_1.asyncHandler)(product_controller_1.remove));
exports.default = router;
//# sourceMappingURL=product.route.js.map