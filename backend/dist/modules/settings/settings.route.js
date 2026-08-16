"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const settings_controller_1 = require("./settings.controller");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const settings_schema_1 = require("./settings.schema");
const upload_1 = require("../../middleware/upload");
const router = (0, express_1.Router)();
// Public
router.get("/", (0, asyncHandler_1.asyncHandler)(settings_controller_1.getAll));
router.get("/hero-slides", (0, asyncHandler_1.asyncHandler)(settings_controller_1.getHeroSlides));
router.get("/store-info", (0, asyncHandler_1.asyncHandler)(settings_controller_1.getStoreInfo));
router.get("/:key", (0, validate_1.validate)(settings_schema_1.getSettingSchema), (0, asyncHandler_1.asyncHandler)(settings_controller_1.get));
router.post("/hero-slides/link", auth_1.authMiddleware, auth_1.adminOnly, (0, asyncHandler_1.asyncHandler)(settings_controller_1.addHeroSlideByLink));
// Admin only
router.put("/", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(settings_schema_1.setSettingSchema), (0, asyncHandler_1.asyncHandler)(settings_controller_1.set));
router.post("/hero-slides", auth_1.authMiddleware, auth_1.adminOnly, upload_1.upload.single("image"), (0, asyncHandler_1.asyncHandler)(settings_controller_1.addHeroSlide));
router.delete("/hero-slides/:index", auth_1.authMiddleware, auth_1.adminOnly, (0, asyncHandler_1.asyncHandler)(settings_controller_1.deleteHeroSlide));
exports.default = router;
//# sourceMappingURL=settings.route.js.map