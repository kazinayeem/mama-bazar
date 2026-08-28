"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || (function () {
    var ownKeys = function(o) {
        ownKeys = Object.getOwnPropertyNames || function (o) {
            var ar = [];
            for (var k in o) if (Object.prototype.hasOwnProperty.call(o, k)) ar[ar.length] = k;
            return ar;
        };
        return ownKeys(o);
    };
    return function (mod) {
        if (mod && mod.__esModule) return mod;
        var result = {};
        if (mod != null) for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
        __setModuleDefault(result, mod);
        return result;
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
exports.deleteBackup = exports.restoreBackup = exports.downloadBackup = exports.createBackup = exports.listBackups = void 0;
const backupService = __importStar(require("./backup.service"));
const backup_storage_1 = require("./backup.storage");
const AppError_1 = require("../../utils/AppError");
const listBackups = async (_req, res) => {
    const data = await backupService.listBackups();
    res.json({ success: true, data });
};
exports.listBackups = listBackups;
const createBackup = async (req, res) => {
    const actor = req.user;
    const data = await backupService.createBackup({
        type: req.body.type || "manual",
        createdById: actor?.id,
        actorName: actor?.name || "Super Admin",
        actorEmail: actor?.email,
        ip: req.ip,
        userAgent: req.headers["user-agent"],
    });
    res.status(201).json({ success: true, message: "Backup created successfully", data });
};
exports.createBackup = createBackup;
/**
 * Download endpoint — always proxies through the backend.
 *
 * WHY: The `download` attribute on <a> tags only works for same-origin URLs.
 * Cross-origin Cloudinary signed URLs are silently ignored by browsers, causing
 * the browser to navigate to the URL instead of downloading, returning a ~225-byte
 * Cloudinary error/redirect HTML page instead of the actual ZIP archive.
 *
 * Proxy approach ensures:
 * - Correct Content-Disposition: attachment header (forces download in every browser)
 * - Correct Content-Type: application/zip
 * - Auth is enforced server-side (JWT checked before any bytes are served)
 * - No cross-origin limitations
 */
const downloadBackup = async (req, res) => {
    const id = Number(req.params.id);
    if (isNaN(id))
        throw new AppError_1.AppError(400, "Invalid backup ID");
    const backup = await backupService.getBackupById(id);
    if (!backup)
        throw new AppError_1.AppError(404, "Backup not found");
    let buffer;
    try {
        buffer = await backup_storage_1.backupStorage.download(backup.filepath);
    }
    catch (err) {
        console.error("[Backup] Download failed:", err?.message);
        throw new AppError_1.AppError(503, "Failed to retrieve backup archive from storage. The archive may have been deleted.");
    }
    // Validate that what we got is actually a ZIP (magic bytes PK\x03\x04)
    if (buffer.length < 4 || buffer[0] !== 0x50 || buffer[1] !== 0x4b) {
        console.error("[Backup] Retrieved archive has invalid ZIP magic bytes. Size:", buffer.length);
        throw new AppError_1.AppError(502, "Backup archive in storage is corrupted or not a valid ZIP file.");
    }
    const safeFilename = backup.filename.replace(/[^a-zA-Z0-9._-]/g, "_");
    res.setHeader("Content-Disposition", `attachment; filename="${safeFilename}"`);
    res.setHeader("Content-Type", "application/zip");
    res.setHeader("Content-Length", String(buffer.length));
    res.setHeader("Cache-Control", "no-store, no-cache, must-revalidate");
    res.setHeader("X-Content-Type-Options", "nosniff");
    return res.send(buffer);
};
exports.downloadBackup = downloadBackup;
const restoreBackup = async (req, res) => {
    const actor = req.user;
    const file = req.file;
    if (!file)
        throw new AppError_1.AppError(400, "Please attach a backup archive (.zip) in the 'file' field");
    if (!file.buffer || file.buffer.length === 0)
        throw new AppError_1.AppError(400, "Uploaded file is empty");
    const result = await backupService.restoreBackup(file.buffer, {
        id: actor?.id,
        name: actor?.name,
        email: actor?.email,
        ip: req.ip,
        userAgent: req.headers["user-agent"],
    });
    res.json({
        success: true,
        message: "Database successfully restored. Pre-restore safety backup was preserved.",
        data: result,
    });
};
exports.restoreBackup = restoreBackup;
const deleteBackup = async (req, res) => {
    const id = Number(req.params.id);
    if (isNaN(id))
        throw new AppError_1.AppError(400, "Invalid backup ID");
    const actor = req.user;
    await backupService.deleteBackup(id, {
        id: actor?.id,
        name: actor?.name,
        email: actor?.email,
        ip: req.ip,
        userAgent: req.headers["user-agent"],
    });
    res.json({ success: true, message: "Backup deleted successfully" });
};
exports.deleteBackup = deleteBackup;
//# sourceMappingURL=backup.controller.js.map