import { Router } from "express";
import {
  getActiveMethods,
  getAll,
  getById,
  create,
  update,
  updateStatuses,
  remove,
} from "./payment.controller";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import {
  createPaymentMethodSchema,
  updatePaymentMethodSchema,
  paymentMethodIdSchema,
  paymentMethodsStatusSchema,
} from "./payment.schema";

const router = Router();

// Public
router.get("/public", asyncHandler(getActiveMethods));

// Admin
router.get("/", authMiddleware, requirePermission("payment_methods.view"), asyncHandler(getAll));
router.get("/:id", authMiddleware, requirePermission("payment_methods.view"), validate(paymentMethodIdSchema), asyncHandler(getById));
router.post("/", authMiddleware, requirePermission("payment_methods.manage"), validate(createPaymentMethodSchema), asyncHandler(create));
router.put("/:id", authMiddleware, requirePermission("payment_methods.manage"), validate(updatePaymentMethodSchema), asyncHandler(update));
router.put("/", authMiddleware, requirePermission("payment_methods.manage"), validate(paymentMethodsStatusSchema), asyncHandler(updateStatuses));
router.delete("/:id", authMiddleware, requirePermission("payment_methods.manage"), validate(paymentMethodIdSchema), asyncHandler(remove));

export default router;
