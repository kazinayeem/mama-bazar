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
import { authMiddleware, adminOnly } from "../../middleware/auth";
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
router.get("/export/csv", authMiddleware, adminOnly, asyncHandler(exportCsv));
router.get("/:id", validate(productIdSchema), asyncHandler(getById));
router.get("/:id/related", validate(productIdSchema), asyncHandler(getRelated));

// Admin
router.post("/", authMiddleware, uploadMemory.array("images", 10), validate(createProductSchema), asyncHandler(create));
router.post("/import/csv", authMiddleware, adminOnly, asyncHandler(importCsv));
router.post("/bulk", authMiddleware, adminOnly, validate(bulkProductSchema), asyncHandler(bulk));
router.post("/:id/duplicate", authMiddleware, adminOnly, validate(productIdSchema), asyncHandler(duplicate));
router.post("/:id/draft", authMiddleware, adminOnly, validate(saveDraftSchema), asyncHandler(saveDraft));
router.put("/:id", authMiddleware, uploadMemory.array("images", 10), validate(updateProductSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, adminOnly, validate(productIdSchema), asyncHandler(remove));

export default router;
