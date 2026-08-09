import { Router } from "express";
import { getAll, get, set, getHeroSlides, addHeroSlide, deleteHeroSlide, addHeroSlideByLink } from "./settings.controller";
import { authMiddleware, adminOnly } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { setSettingSchema, getSettingSchema } from "./settings.schema";
import { upload } from "../../middleware/upload";

const router = Router();

// Public
router.get("/", asyncHandler(getAll));
router.get("/hero-slides", asyncHandler(getHeroSlides));
router.get("/:key", validate(getSettingSchema), asyncHandler(get));
	router.post("/hero-slides/link", authMiddleware, adminOnly, asyncHandler(addHeroSlideByLink));

// Admin only
router.put("/", authMiddleware, adminOnly, validate(setSettingSchema), asyncHandler(set));
router.post("/hero-slides", authMiddleware, adminOnly, upload.single("image"), asyncHandler(addHeroSlide));
router.delete("/hero-slides/:index", authMiddleware, adminOnly, asyncHandler(deleteHeroSlide));

export default router;
