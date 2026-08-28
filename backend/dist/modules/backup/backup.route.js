"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const backup_controller_1 = require("./backup.controller");
const auth_1 = require("../../middleware/auth");
const uploadMemory_1 = require("../../middleware/uploadMemory");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const router = (0, express_1.Router)();
// All backup endpoints require authentication
router.use(auth_1.authMiddleware);
router.get("/history", (0, auth_1.requirePermission)("backup.view"), (0, asyncHandler_1.asyncHandler)(backup_controller_1.listBackups));
router.post("/verify-pin", (0, auth_1.requirePermission)("backup.view"), (0, asyncHandler_1.asyncHandler)(backup_controller_1.verifyPin));
router.post("/create", (0, auth_1.requirePermission)("backup.create"), (0, asyncHandler_1.asyncHandler)(backup_controller_1.createBackup));
router.get("/download/:id", (0, auth_1.requirePermission)("backup.create"), (0, asyncHandler_1.asyncHandler)(backup_controller_1.downloadBackup));
router.post("/restore", (0, auth_1.requirePermission)("backup.restore"), uploadMemory_1.uploadMemory.single("file"), (0, asyncHandler_1.asyncHandler)(backup_controller_1.restoreBackup));
router.delete("/:id", (0, auth_1.requirePermission)("backup.restore"), (0, asyncHandler_1.asyncHandler)(backup_controller_1.deleteBackup));
exports.default = router;
//# sourceMappingURL=backup.route.js.map