"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const review_controller_1 = require("./review.controller");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const review_schema_1 = require("./review.schema");
const router = (0, express_1.Router)();
// Public — approved reviews only
router.get("/", (0, asyncHandler_1.asyncHandler)(review_controller_1.getAll));
// Admin — moderate reviews (registered before /:id so "admin" isn't parsed as an id)
router.get("/admin/list", auth_1.authMiddleware, auth_1.adminOnly, (0, asyncHandler_1.asyncHandler)(review_controller_1.getAllAdmin));
router.get("/:id", (0, asyncHandler_1.asyncHandler)(review_controller_1.getById));
// Authenticated — submit a review
router.post("/", auth_1.authMiddleware, (0, validate_1.validate)(review_schema_1.createReviewSchema), (0, asyncHandler_1.asyncHandler)(review_controller_1.create));
router.patch("/:id/status", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(review_schema_1.updateReviewStatusSchema), (0, asyncHandler_1.asyncHandler)(review_controller_1.updateStatus));
router.delete("/:id", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(review_schema_1.reviewIdSchema), (0, asyncHandler_1.asyncHandler)(review_controller_1.remove));
exports.default = router;
//# sourceMappingURL=review.route.js.map