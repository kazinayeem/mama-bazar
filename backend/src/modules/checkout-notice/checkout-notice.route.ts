import { Router } from "express";
import {
  getActiveNotices,
  getAll,
  getById,
  create,
  update,
  remove,
} from "./checkout-notice.controller";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import {
  createCheckoutNoticeSchema,
  updateCheckoutNoticeSchema,
  checkoutNoticeIdSchema,
} from "./checkout-notice.schema";

const router = Router();

// Public
router.get("/public", asyncHandler(getActiveNotices));

// Admin
router.get("/", authMiddleware, requirePermission("checkout_notices.view"), asyncHandler(getAll));
router.get("/:id", authMiddleware, requirePermission("checkout_notices.view"), validate(checkoutNoticeIdSchema), asyncHandler(getById));
router.post("/", authMiddleware, requirePermission("checkout_notices.manage"), validate(createCheckoutNoticeSchema), asyncHandler(create));
router.put("/:id", authMiddleware, requirePermission("checkout_notices.manage"), validate(updateCheckoutNoticeSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, requirePermission("checkout_notices.manage"), validate(checkoutNoticeIdSchema), asyncHandler(remove));

export default router;
