import { Router } from "express";
import { getAll, getAllAdmin, create, updateStatus, remove, getById } from "./review.controller";
import { authMiddleware, adminOnly } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { createReviewSchema, updateReviewStatusSchema, reviewIdSchema } from "./review.schema";

const router = Router();

// Public — approved reviews only
router.get("/", asyncHandler(getAll));

// Admin — moderate reviews (registered before /:id so "admin" isn't parsed as an id)
router.get("/admin/list", authMiddleware, adminOnly, asyncHandler(getAllAdmin));

router.get("/:id", asyncHandler(getById));

// Authenticated — submit a review
router.post("/", authMiddleware, validate(createReviewSchema), asyncHandler(create));

router.patch("/:id/status", authMiddleware, adminOnly, validate(updateReviewStatusSchema), asyncHandler(updateStatus));
router.delete("/:id", authMiddleware, adminOnly, validate(reviewIdSchema), asyncHandler(remove));

export default router;
