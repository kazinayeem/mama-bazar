import { Router } from "express";
import { getActiveMethods, estimateShipping, getAll, getById, create, update, remove } from "./shipping.controller";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import {
  createShippingMethodSchema,
  updateShippingMethodSchema,
  shippingMethodIdSchema,
  estimateShippingSchema,
} from "./shipping.schema";

const router = Router();

// Public
router.get("/public", asyncHandler(getActiveMethods));
router.post("/estimate", validate(estimateShippingSchema), asyncHandler(estimateShipping));

// Admin
router.get("/", authMiddleware, requirePermission("shipping.view"), asyncHandler(getAll));
router.get("/:id", authMiddleware, requirePermission("shipping.view"), validate(shippingMethodIdSchema), asyncHandler(getById));
router.post("/", authMiddleware, requirePermission("shipping.manage"), validate(createShippingMethodSchema), asyncHandler(create));
router.put("/:id", authMiddleware, requirePermission("shipping.manage"), validate(updateShippingMethodSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, requirePermission("shipping.manage"), validate(shippingMethodIdSchema), asyncHandler(remove));

export default router;
