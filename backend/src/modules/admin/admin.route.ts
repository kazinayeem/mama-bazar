import { Router } from "express";
import { getDashboard } from "./admin.controller";
import { authMiddleware } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";

const router = Router();

// Admin only
router.get("/dashboard", authMiddleware, asyncHandler(getDashboard));

export default router;
