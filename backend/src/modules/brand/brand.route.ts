import { Router } from "express";
import {
  getAll,
  getById,
  getBySlug,
  getUsage,
  listAdmin,
  create,
  update,
  remove,
  moveProducts,
} from "./brand.controller";
import { uploadMemory } from "../../middleware/uploadMemory";
import { authMiddleware, adminOnly } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import {
  createBrandSchema,
  updateBrandSchema,
  brandIdSchema,
  brandListSchema,
  brandMoveSchema,
} from "./brand.schema";

const router = Router();

// Public
router.get("/", asyncHandler(getAll));
router.get("/slug/:slug", asyncHandler(getBySlug));

// Admin (before /:id)
router.get("/admin", authMiddleware, adminOnly, validate(brandListSchema), asyncHandler(listAdmin));
router.get("/:id/usage", authMiddleware, validate(brandIdSchema), asyncHandler(getUsage));

// Public
router.get("/:id", asyncHandler(getById));

// Admin
router.post("/", authMiddleware, adminOnly, uploadMemory.single("logo"), validate(createBrandSchema), asyncHandler(create));
router.put("/:id", authMiddleware, adminOnly, uploadMemory.single("logo"), validate(updateBrandSchema), asyncHandler(update));
router.post("/:id/move", authMiddleware, adminOnly, validate(brandMoveSchema), asyncHandler(moveProducts));
router.delete("/:id", authMiddleware, adminOnly, validate(brandIdSchema), asyncHandler(remove));

export default router;
