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
import { authMiddleware, requirePermission } from "../../middleware/auth";
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
router.get("/admin", authMiddleware, requirePermission("brands.view"), validate(brandListSchema), asyncHandler(listAdmin));
router.get("/:id/usage", authMiddleware, requirePermission("brands.view"), validate(brandIdSchema), asyncHandler(getUsage));

// Public
router.get("/:id", asyncHandler(getById));

// Admin
router.post("/", authMiddleware, requirePermission("brands.create"), uploadMemory.single("logo"), validate(createBrandSchema), asyncHandler(create));
router.put("/:id", authMiddleware, requirePermission("brands.update"), uploadMemory.single("logo"), validate(updateBrandSchema), asyncHandler(update));
router.post("/:id/move", authMiddleware, requirePermission("brands.update"), validate(brandMoveSchema), asyncHandler(moveProducts));
router.delete("/:id", authMiddleware, requirePermission("brands.delete"), validate(brandIdSchema), asyncHandler(remove));

export default router;
