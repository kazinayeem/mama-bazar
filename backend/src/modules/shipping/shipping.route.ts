import { Router } from "express";
import { getActiveMethods, estimateShipping, getAll, getById, create, update, remove } from "./shipping.controller";
import { authMiddleware } from "../../middleware/auth";
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
router.get("/", authMiddleware, asyncHandler(getAll));
router.get("/:id", authMiddleware, validate(shippingMethodIdSchema), asyncHandler(getById));
router.post("/", authMiddleware, validate(createShippingMethodSchema), asyncHandler(create));
router.put("/:id", authMiddleware, validate(updateShippingMethodSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, validate(shippingMethodIdSchema), asyncHandler(remove));

export default router;
