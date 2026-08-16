import { Router } from "express";
import {
	register,
	login,
	devLogin,
	createAdmin,
	getProfile,
	updateProfile,
	getOrderHistory,
	getAll,
	remove,
	requestPasswordReset,
	resetPassword,
	changePassword,
	getAddresses,
	createAddress,
	updateAddress,
	deleteAddress,
} from "./user.controller";
import { authMiddleware, adminOnly } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import {
	registerSchema,
	loginSchema,
	createAdminSchema,
	passwordResetRequestSchema,
	passwordResetSchema,
	changePasswordSchema,
	updateProfileSchema,
	createAddressSchema,
	updateAddressSchema,
	addressIdSchema,
} from "./user.schema";

const router = Router();

// Public
router.post("/register", validate(registerSchema), asyncHandler(register));
router.post("/login", validate(loginSchema), asyncHandler(login));
router.post("/password-reset-request", validate(passwordResetRequestSchema), asyncHandler(requestPasswordReset));
router.post("/password-reset", validate(passwordResetSchema), asyncHandler(resetPassword));

// Dev-only: real JWT login as the seeded dev account (404 in production)
router.post("/dev-login", asyncHandler(devLogin));

// Protected
router.get("/profile", authMiddleware, asyncHandler(getProfile));
router.put("/profile", authMiddleware, validate(updateProfileSchema), asyncHandler(updateProfile));
router.post("/change-password", authMiddleware, validate(changePasswordSchema), asyncHandler(changePassword));
router.get("/orders", authMiddleware, asyncHandler(getOrderHistory));
router.get("/addresses", authMiddleware, asyncHandler(getAddresses));
router.post("/addresses", authMiddleware, validate(createAddressSchema), asyncHandler(createAddress));
router.put("/addresses/:id", authMiddleware, validate(updateAddressSchema), asyncHandler(updateAddress));
router.delete("/addresses/:id", authMiddleware, validate(addressIdSchema), asyncHandler(deleteAddress));

// Admin only
router.post("/admin", authMiddleware, adminOnly, validate(createAdminSchema), asyncHandler(createAdmin));
router.get("/", authMiddleware, adminOnly, asyncHandler(getAll));
router.delete("/:id", authMiddleware, adminOnly, asyncHandler(remove));

export default router;
