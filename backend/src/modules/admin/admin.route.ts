import { Router } from "express";
import { getDashboard } from "./admin.controller";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";

const router = Router();

// Admin only
router.get("/dashboard", authMiddleware, requirePermission("dashboard.view"), asyncHandler(getDashboard));

export default router;
