import { Router } from "express";
import { upload, uploadMultiple, getAll, getFolders, getById, update, remove, config } from "./media.controller";
import { uploadMemory } from "../../middleware/uploadMemory";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { mediaUploadSchema, mediaListSchema, mediaIdSchema, mediaUpdateSchema } from "./media.schema";

const router = Router();

// Admin only — media library
router.post("/upload", authMiddleware, requirePermission("media.upload"), uploadMemory.single("file"), validate(mediaUploadSchema), asyncHandler(upload));
router.post("/upload/multiple", authMiddleware, requirePermission("media.upload"), uploadMemory.array("files", 20), validate(mediaUploadSchema), asyncHandler(uploadMultiple));
router.get("/", authMiddleware, requirePermission("media.view"), validate(mediaListSchema), asyncHandler(getAll));
router.get("/folders", authMiddleware, requirePermission("media.view"), asyncHandler(getFolders));
router.get("/config", authMiddleware, requirePermission("media.view"), asyncHandler(config));
router.get("/:id", authMiddleware, requirePermission("media.view"), validate(mediaIdSchema), asyncHandler(getById));
router.put("/:id", authMiddleware, requirePermission("media.upload"), validate(mediaUpdateSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, requirePermission("media.delete"), validate(mediaIdSchema), asyncHandler(remove));

export default router;
