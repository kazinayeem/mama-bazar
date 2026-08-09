"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const banner_controller_1 = require("./banner.controller");
const uploadMemory_1 = require("../../middleware/uploadMemory");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const banner_schema_1 = require("./banner.schema");
const router = (0, express_1.Router)();
const bannerUpload = uploadMemory_1.uploadMemory.fields([
    { name: "image", maxCount: 1 },
    { name: "imageTablet", maxCount: 1 },
    { name: "imageMobile", maxCount: 1 },
]);
// Public
router.get("/", (0, asyncHandler_1.asyncHandler)(banner_controller_1.getAll));
router.get("/:id", (0, asyncHandler_1.asyncHandler)(banner_controller_1.getById));
// Admin
router.post("/", auth_1.authMiddleware, bannerUpload, (0, validate_1.validate)(banner_schema_1.createBannerSchema), (0, asyncHandler_1.asyncHandler)(banner_controller_1.create));
router.put("/:id", auth_1.authMiddleware, bannerUpload, (0, validate_1.validate)(banner_schema_1.updateBannerSchema), (0, asyncHandler_1.asyncHandler)(banner_controller_1.update));
router.delete("/:id", auth_1.authMiddleware, (0, validate_1.validate)(banner_schema_1.bannerIdSchema), (0, asyncHandler_1.asyncHandler)(banner_controller_1.remove));
exports.default = router;
//# sourceMappingURL=banner.route.js.map