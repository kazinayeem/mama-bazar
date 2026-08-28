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
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.deleteBackup = exports.restoreBackup = exports.downloadBackup = exports.createBackup = exports.listBackups = void 0;
const fs_1 = __importDefault(require("fs"));
const backupService = __importStar(require("./backup.service"));
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
const downloadBackup = async (req, res) => {
    const id = Number(req.params.id);
    const backup = await backupService.getBackupById(id);
    if (!backup) {
        throw new AppError_1.AppError(404, "Backup not found");
    }
    if (!fs_1.default.existsSync(backup.filepath)) {
        throw new AppError_1.AppError(404, "Backup archive file not found on server");
    }
    res.setHeader("Content-Disposition", `attachment; filename="${backup.filename}"`);
    res.setHeader("Content-Type", "application/zip");
    const fileStream = fs_1.default.createReadStream(backup.filepath);
    fileStream.pipe(res);
};
exports.downloadBackup = downloadBackup;
const restoreBackup = async (req, res) => {
    const actor = req.user;
    const file = req.file;
    if (!file) {
        throw new AppError_1.AppError(400, "Please provide a backup archive file (.zip) to restore");
    }
    const result = await backupService.restoreBackup(file.buffer, {
        id: actor?.id,
        name: actor?.name,
        email: actor?.email,
        ip: req.ip,
        userAgent: req.headers["user-agent"],
    });
    res.json({
        success: true,
        message: "Database successfully restored. Current state safety backup preserved.",
        data: result,
    });
};
exports.restoreBackup = restoreBackup;
const deleteBackup = async (req, res) => {
    const id = Number(req.params.id);
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