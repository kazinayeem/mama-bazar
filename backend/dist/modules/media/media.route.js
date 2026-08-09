"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const media_controller_1 = require("./media.controller");
const uploadMemory_1 = require("../../middleware/uploadMemory");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const media_schema_1 = require("./media.schema");
const router = (0, express_1.Router)();
// Admin only — media library
router.post("/upload", auth_1.authMiddleware, uploadMemory_1.uploadMemory.single("file"), (0, validate_1.validate)(media_schema_1.mediaUploadSchema), (0, asyncHandler_1.asyncHandler)(media_controller_1.upload));
router.post("/upload/multiple", auth_1.authMiddleware, uploadMemory_1.uploadMemory.array("files", 20), (0, validate_1.validate)(media_schema_1.mediaUploadSchema), (0, asyncHandler_1.asyncHandler)(media_controller_1.uploadMultiple));
router.get("/", auth_1.authMiddleware, (0, validate_1.validate)(media_schema_1.mediaListSchema), (0, asyncHandler_1.asyncHandler)(media_controller_1.getAll));
router.get("/folders", auth_1.authMiddleware, (0, asyncHandler_1.asyncHandler)(media_controller_1.getFolders));
router.get("/config", auth_1.authMiddleware, (0, asyncHandler_1.asyncHandler)(media_controller_1.config));
router.get("/:id", auth_1.authMiddleware, (0, validate_1.validate)(media_schema_1.mediaIdSchema), (0, asyncHandler_1.asyncHandler)(media_controller_1.getById));
router.put("/:id", auth_1.authMiddleware, (0, validate_1.validate)(media_schema_1.mediaUpdateSchema), (0, asyncHandler_1.asyncHandler)(media_controller_1.update));
router.delete("/:id", auth_1.authMiddleware, (0, validate_1.validate)(media_schema_1.mediaIdSchema), (0, asyncHandler_1.asyncHandler)(media_controller_1.remove));
exports.default = router;
//# sourceMappingURL=media.route.js.map