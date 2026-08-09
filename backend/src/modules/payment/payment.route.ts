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
import { authMiddleware } from "../../middleware/auth";
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
router.get("/", authMiddleware, asyncHandler(getAll));
router.get("/:id", authMiddleware, validate(paymentMethodIdSchema), asyncHandler(getById));
router.post("/", authMiddleware, validate(createPaymentMethodSchema), asyncHandler(create));
router.put("/:id", authMiddleware, validate(updatePaymentMethodSchema), asyncHandler(update));
router.put("/", authMiddleware, validate(paymentMethodsStatusSchema), asyncHandler(updateStatuses));
router.delete("/:id", authMiddleware, validate(paymentMethodIdSchema), asyncHandler(remove));

export default router;
