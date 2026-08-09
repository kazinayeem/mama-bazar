import { Router } from "express";
import {
  getActiveNotices,
  getAll,
  getById,
  create,
  update,
  remove,
} from "./checkout-notice.controller";
import { authMiddleware } from "../../middleware/auth";
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
router.get("/", authMiddleware, asyncHandler(getAll));
router.get("/:id", authMiddleware, validate(checkoutNoticeIdSchema), asyncHandler(getById));
router.post("/", authMiddleware, validate(createCheckoutNoticeSchema), asyncHandler(create));
router.put("/:id", authMiddleware, validate(updateCheckoutNoticeSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, validate(checkoutNoticeIdSchema), asyncHandler(remove));

export default router;
