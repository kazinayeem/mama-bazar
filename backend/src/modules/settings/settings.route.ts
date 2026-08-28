import { Router } from "express";
import { getAll, get, set, getStoreInfo, getHeroSlides, addHeroSlide, deleteHeroSlide, addHeroSlideByLink } from "./settings.controller";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { setSettingSchema, getSettingSchema } from "./settings.schema";
import { upload } from "../../middleware/upload";

const router = Router();

// Public
router.get("/", asyncHandler(getAll));
router.get("/hero-slides", asyncHandler(getHeroSlides));
router.get("/store-info", asyncHandler(getStoreInfo));
router.get("/:key", validate(getSettingSchema), asyncHandler(get));
router.post("/hero-slides/link", authMiddleware, requirePermission("homepage.manage"), asyncHandler(addHeroSlideByLink));

// Admin only
router.put("/", authMiddleware, requirePermission("settings.manage"), validate(setSettingSchema), asyncHandler(set));
router.post("/hero-slides", authMiddleware, requirePermission("homepage.manage"), upload.single("image"), asyncHandler(addHeroSlide));
router.delete("/hero-slides/:index", authMiddleware, requirePermission("homepage.manage"), asyncHandler(deleteHeroSlide));

export default router;
