import { Router } from "express";
import {
  getAll,
  getBySlug,
  getById,
  getTree,
  listAdmin,
  getUsage,
  create,
  update,
  remove,
  moveProducts,
} from "./category.controller";
import { uploadMemory } from "../../middleware/uploadMemory";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import {
  createCategorySchema,
  updateCategorySchema,
  categoryIdSchema,
  categorySlugSchema,
  categoryListSchema,
  categoryMoveSchema,
} from "./category.schema";

const router = Router();

// Public
router.get("/", asyncHandler(getAll));
router.get("/tree", asyncHandler(getTree));
router.get("/slug/:slug", validate(categorySlugSchema), asyncHandler(getBySlug));

// Admin (registered before /:id to avoid param capture)
router.get("/admin", authMiddleware, requirePermission("categories.view"), validate(categoryListSchema), asyncHandler(listAdmin));
router.get("/:id/usage", authMiddleware, requirePermission("categories.view"), validate(categoryIdSchema), asyncHandler(getUsage));

// Public
router.get("/:id", validate(categoryIdSchema), asyncHandler(getById));

// Admin
router.post("/", authMiddleware, requirePermission("categories.create"), uploadMemory.single("image"), validate(createCategorySchema), asyncHandler(create));
router.put("/:id", authMiddleware, requirePermission("categories.update"), uploadMemory.single("image"), validate(updateCategorySchema), asyncHandler(update));
router.post("/:id/move", authMiddleware, requirePermission("categories.update"), validate(categoryMoveSchema), asyncHandler(moveProducts));
router.delete("/:id", authMiddleware, requirePermission("categories.delete"), validate(categoryIdSchema), asyncHandler(remove));

export default router;
