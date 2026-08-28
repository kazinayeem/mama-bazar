"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const user_controller_1 = require("./user.controller");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const user_schema_1 = require("./user.schema");
const router = (0, express_1.Router)();
// Public
router.post("/register", (0, validate_1.validate)(user_schema_1.registerSchema), (0, asyncHandler_1.asyncHandler)(user_controller_1.register));
router.post("/login", (0, validate_1.validate)(user_schema_1.loginSchema), (0, asyncHandler_1.asyncHandler)(user_controller_1.login));
router.post("/password-reset-request", (0, validate_1.validate)(user_schema_1.passwordResetRequestSchema), (0, asyncHandler_1.asyncHandler)(user_controller_1.requestPasswordReset));
router.post("/password-reset", (0, validate_1.validate)(user_schema_1.passwordResetSchema), (0, asyncHandler_1.asyncHandler)(user_controller_1.resetPassword));
// Dev-only: real JWT login as the seeded dev account (404 in production)
router.post("/dev-login", (0, asyncHandler_1.asyncHandler)(user_controller_1.devLogin));
// Protected
router.get("/profile", auth_1.authMiddleware, (0, asyncHandler_1.asyncHandler)(user_controller_1.getProfile));
router.put("/profile", auth_1.authMiddleware, (0, validate_1.validate)(user_schema_1.updateProfileSchema), (0, asyncHandler_1.asyncHandler)(user_controller_1.updateProfile));
router.post("/change-password", auth_1.authMiddleware, (0, validate_1.validate)(user_schema_1.changePasswordSchema), (0, asyncHandler_1.asyncHandler)(user_controller_1.changePassword));
router.get("/orders", auth_1.authMiddleware, (0, asyncHandler_1.asyncHandler)(user_controller_1.getOrderHistory));
router.get("/addresses", auth_1.authMiddleware, (0, asyncHandler_1.asyncHandler)(user_controller_1.getAddresses));
router.post("/addresses", auth_1.authMiddleware, (0, validate_1.validate)(user_schema_1.createAddressSchema), (0, asyncHandler_1.asyncHandler)(user_controller_1.createAddress));
router.put("/addresses/:id", auth_1.authMiddleware, (0, validate_1.validate)(user_schema_1.updateAddressSchema), (0, asyncHandler_1.asyncHandler)(user_controller_1.updateAddress));
router.delete("/addresses/:id", auth_1.authMiddleware, (0, validate_1.validate)(user_schema_1.addressIdSchema), (0, asyncHandler_1.asyncHandler)(user_controller_1.deleteAddress));
// Admin only
router.post("/admin", auth_1.authMiddleware, (0, auth_1.requirePermission)("members.create"), (0, validate_1.validate)(user_schema_1.createAdminSchema), (0, asyncHandler_1.asyncHandler)(user_controller_1.createAdmin));
router.get("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("customers.view"), (0, asyncHandler_1.asyncHandler)(user_controller_1.getAll));
router.delete("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("customers.delete"), (0, asyncHandler_1.asyncHandler)(user_controller_1.remove));
exports.default = router;
//# sourceMappingURL=user.route.js.map