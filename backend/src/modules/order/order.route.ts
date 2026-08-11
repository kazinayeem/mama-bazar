import { Router } from "express";
import rateLimit from "express-rate-limit";
import {
  create,
  getAll,
  getStats,
  getById,
  updateStatus,
  verifyPayment,
  addAdminNote,
  getMyOrders,
  trackOrder,
  getInvoice,
  getCustomerInvoice,
  remove,
} from "./order.controller";
import { authMiddleware, adminOnly } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import {
  createOrderSchema,
  updateOrderStatusSchema,
  verifyPaymentSchema,
  adminNoteSchema,
  orderIdSchema,
  orderListSchema,
  trackOrderSchema,
} from "./order.schema";

const router = Router();

const orderCreateLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 30,
  standardHeaders: true,
  legacyHeaders: false,
  message: { success: false, message: "Too many order attempts, please try again later." },
});

// PUBLIC - Guest checkout (NO AUTH, optional Bearer token = signed-in checkout)
router.post("/create", orderCreateLimiter, validate(createOrderSchema), asyncHandler(create));

// PUBLIC - Track order by order ID + phone
router.post("/track", validate(trackOrderSchema), asyncHandler(trackOrder));

// CUSTOMER - Signed-in user's own orders
router.get("/my-orders", authMiddleware, asyncHandler(getMyOrders));

// CUSTOMER - Get invoice for own order (authenticated, checks ownership)
router.get("/:id/my-invoice", authMiddleware, validate(orderIdSchema), asyncHandler(getCustomerInvoice));

// Admin routes
router.get("/", authMiddleware, adminOnly, validate(orderListSchema), asyncHandler(getAll));
router.get("/stats", authMiddleware, adminOnly, asyncHandler(getStats));
router.get("/:id", authMiddleware, adminOnly, validate(orderIdSchema), asyncHandler(getById));
router.get("/:id/invoice", authMiddleware, adminOnly, validate(orderIdSchema), asyncHandler(getInvoice));
router.patch("/:id/status", authMiddleware, adminOnly, validate(updateOrderStatusSchema), asyncHandler(updateStatus));
router.patch("/:id/payment/verify", authMiddleware, adminOnly, validate(verifyPaymentSchema), asyncHandler(verifyPayment));
router.patch("/:id/admin-note", authMiddleware, adminOnly, validate(adminNoteSchema), asyncHandler(addAdminNote));
router.delete("/:id", authMiddleware, adminOnly, validate(orderIdSchema), asyncHandler(remove));

export default router;
