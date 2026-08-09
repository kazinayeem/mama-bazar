import { Router } from "express";
import { getAll, getById, create, update, remove } from "./banner.controller";
import { uploadMemory } from "../../middleware/uploadMemory";
import { authMiddleware } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { createBannerSchema, updateBannerSchema, bannerIdSchema } from "./banner.schema";

const router = Router();

const bannerUpload = uploadMemory.fields([
  { name: "image", maxCount: 1 },
  { name: "imageTablet", maxCount: 1 },
  { name: "imageMobile", maxCount: 1 },
]);

// Public
router.get("/", asyncHandler(getAll));
router.get("/:id", asyncHandler(getById));

// Admin
router.post("/", authMiddleware, bannerUpload, validate(createBannerSchema), asyncHandler(create));
router.put("/:id", authMiddleware, bannerUpload, validate(updateBannerSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, validate(bannerIdSchema), asyncHandler(remove));

export default router;
