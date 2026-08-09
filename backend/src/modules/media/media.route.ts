import { Router } from "express";
import { upload, uploadMultiple, getAll, getFolders, getById, update, remove, config } from "./media.controller";
import { uploadMemory } from "../../middleware/uploadMemory";
import { authMiddleware } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { mediaUploadSchema, mediaListSchema, mediaIdSchema, mediaUpdateSchema } from "./media.schema";

const router = Router();

// Admin only — media library
router.post("/upload", authMiddleware, uploadMemory.single("file"), validate(mediaUploadSchema), asyncHandler(upload));
router.post("/upload/multiple", authMiddleware, uploadMemory.array("files", 20), validate(mediaUploadSchema), asyncHandler(uploadMultiple));
router.get("/", authMiddleware, validate(mediaListSchema), asyncHandler(getAll));
router.get("/folders", authMiddleware, asyncHandler(getFolders));
router.get("/config", authMiddleware, asyncHandler(config));
router.get("/:id", authMiddleware, validate(mediaIdSchema), asyncHandler(getById));
router.put("/:id", authMiddleware, validate(mediaUpdateSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, validate(mediaIdSchema), asyncHandler(remove));

export default router;
