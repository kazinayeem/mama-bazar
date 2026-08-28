import { Router } from "express";
import {
  listBackups,
  createBackup,
  downloadBackup,
  restoreBackup,
  deleteBackup,
} from "./backup.controller";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { uploadMemory } from "../../middleware/uploadMemory";
import { asyncHandler } from "../../middleware/asyncHandler";

const router = Router();

// All backup endpoints require authentication
router.use(authMiddleware);

router.get("/history", requirePermission("backup.view"), asyncHandler(listBackups));
router.post("/create", requirePermission("backup.create"), asyncHandler(createBackup));
router.get("/download/:id", requirePermission("backup.create"), asyncHandler(downloadBackup));
router.post("/restore", requirePermission("backup.restore"), uploadMemory.single("file"), asyncHandler(restoreBackup));
router.delete("/:id", requirePermission("backup.restore"), asyncHandler(deleteBackup));

export default router;
