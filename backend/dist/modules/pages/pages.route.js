"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const pages_controller_1 = require("./pages.controller");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const pages_schema_1 = require("./pages.schema");
const router = (0, express_1.Router)();
// Public
router.get("/p/:slug", (0, validate_1.validate)(pages_schema_1.pageSlugSchema), (0, asyncHandler_1.asyncHandler)(pages_controller_1.getBySlug));
router.post("/contact", (0, validate_1.validate)(pages_schema_1.contactMessageSchema), (0, asyncHandler_1.asyncHandler)(pages_controller_1.submitContact));
// Admin
router.get("/", auth_1.authMiddleware, auth_1.adminOnly, (0, asyncHandler_1.asyncHandler)(pages_controller_1.getAll));
router.post("/", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(pages_schema_1.createPageSchema), (0, asyncHandler_1.asyncHandler)(pages_controller_1.create));
router.put("/:id", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(pages_schema_1.updatePageSchema), (0, asyncHandler_1.asyncHandler)(pages_controller_1.update));
router.delete("/:id", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(pages_schema_1.pageIdSchema), (0, asyncHandler_1.asyncHandler)(pages_controller_1.remove));
router.get("/contact", auth_1.authMiddleware, auth_1.adminOnly, (0, asyncHandler_1.asyncHandler)(pages_controller_1.getContactMessages));
router.patch("/contact/:id", auth_1.authMiddleware, auth_1.adminOnly, (0, asyncHandler_1.asyncHandler)(pages_controller_1.updateContactStatus));
exports.default = router;
//# sourceMappingURL=pages.route.js.map