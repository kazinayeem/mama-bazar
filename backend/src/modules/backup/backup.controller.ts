import { Request, Response } from "express";
import * as backupService from "./backup.service";
import { backupStorage } from "./backup.storage";
import { AppError } from "../../utils/AppError";

export const listBackups = async (_req: Request, res: Response) => {
  const data = await backupService.listBackups();
  res.json({ success: true, data });
};

export const createBackup = async (req: Request, res: Response) => {
  const actor = (req as any).user;
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

export const downloadBackup = async (req: Request, res: Response) => {
  const id = Number(req.params.id);
  const backup = await backupService.getBackupById(id);
  if (!backup) throw new AppError(404, "Backup not found");

  if (backupStorage.isCloudinary()) {
    // Return a time-limited signed URL so the browser can download directly from Cloudinary
    const signedUrl = backupStorage.getDownloadUrl(backup.filepath);
    return res.json({ success: true, data: { downloadUrl: signedUrl, filename: backup.filename } });
  }

  // Local fallback — stream file directly
  try {
    const buffer = await backupStorage.download(backup.filepath);
    res.setHeader("Content-Disposition", `attachment; filename="${backup.filename}"`);
    res.setHeader("Content-Type", "application/zip");
    res.setHeader("Content-Length", buffer.length);
    return res.send(buffer);
  } catch {
    throw new AppError(404, "Backup archive not found in storage");
  }
};

export const restoreBackup = async (req: Request, res: Response) => {
  const actor = (req as any).user;
  const file = req.file;
  if (!file) throw new AppError(400, "Please provide a backup archive file (.zip) to restore");

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

export const deleteBackup = async (req: Request, res: Response) => {
  const id = Number(req.params.id);
  const actor = (req as any).user;
  await backupService.deleteBackup(id, {
    id: actor?.id,
    name: actor?.name,
    email: actor?.email,
    ip: req.ip,
    userAgent: req.headers["user-agent"],
  });
  res.json({ success: true, message: "Backup deleted successfully" });
};
