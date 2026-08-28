import { Router } from "express";
import {
  getAll,
  getBySlug,
  getById,
  getRelated,
  create,
  update,
  remove,
  bulk,
  duplicate,
  exportCsv,
  importCsv,
  saveDraft,
} from "./product.controller";
import { uploadMemory } from "../../middleware/uploadMemory";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import {
  createProductSchema,
  updateProductSchema,
  productIdSchema,
  productSlugSchema,
  productListSchema,
  bulkProductSchema,
  saveDraftSchema,
} from "./product.schema";

const router = Router();

// Public
router.get("/", validate(productListSchema), asyncHandler(getAll));
router.get("/slug/:slug", validate(productSlugSchema), asyncHandler(getBySlug));
router.get("/export/csv", authMiddleware, requirePermission("products.view"), asyncHandler(exportCsv));
router.get("/:id", validate(productIdSchema), asyncHandler(getById));
router.get("/:id/related", validate(productIdSchema), asyncHandler(getRelated));

// Admin
router.post("/", authMiddleware, requirePermission("products.create"), uploadMemory.array("images", 10), validate(createProductSchema), asyncHandler(create));
router.post("/import/csv", authMiddleware, requirePermission("products.create"), asyncHandler(importCsv));
router.post("/bulk", authMiddleware, requirePermission("products.update"), validate(bulkProductSchema), asyncHandler(bulk));
router.post("/:id/duplicate", authMiddleware, requirePermission("products.create"), validate(productIdSchema), asyncHandler(duplicate));
router.post("/:id/draft", authMiddleware, requirePermission("products.create"), validate(saveDraftSchema), asyncHandler(saveDraft));
router.put("/:id", authMiddleware, requirePermission("products.update"), uploadMemory.array("images", 10), validate(updateProductSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, requirePermission("products.delete"), validate(productIdSchema), asyncHandler(remove));

export default router;
