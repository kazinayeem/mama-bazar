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
export const downloadBackup = async (req: Request, res: Response) => {
  const id = Number(req.params.id);
  if (isNaN(id)) throw new AppError(400, "Invalid backup ID");

  const backup = await backupService.getBackupById(id);
  if (!backup) throw new AppError(404, "Backup not found");

  let buffer: Buffer;
  try {
    buffer = await backupStorage.download(backup.filepath);
  } catch (err: any) {
    console.error("[Backup] Download failed:", err?.message);
    throw new AppError(503, "Failed to retrieve backup archive from storage. The archive may have been deleted.");
  }

  // Validate that what we got is actually a ZIP (magic bytes PK\x03\x04)
  if (buffer.length < 4 || buffer[0] !== 0x50 || buffer[1] !== 0x4b) {
    console.error("[Backup] Retrieved archive has invalid ZIP magic bytes. Size:", buffer.length);
    throw new AppError(502, "Backup archive in storage is corrupted or not a valid ZIP file.");
  }

  const safeFilename = backup.filename.replace(/[^a-zA-Z0-9._-]/g, "_");

  res.setHeader("Content-Disposition", `attachment; filename="${safeFilename}"`);
  res.setHeader("Content-Type", "application/zip");
  res.setHeader("Content-Length", String(buffer.length));
  res.setHeader("Cache-Control", "no-store, no-cache, must-revalidate");
  res.setHeader("X-Content-Type-Options", "nosniff");
  return res.send(buffer);
};

export const restoreBackup = async (req: Request, res: Response) => {
  const actor = (req as any).user;
  const file = req.file;
  if (!file) throw new AppError(400, "Please attach a backup archive (.zip) in the 'file' field");
  if (!file.buffer || file.buffer.length === 0) throw new AppError(400, "Uploaded file is empty");

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

export const deleteBackup = async (req: Request, res: Response) => {
  const id = Number(req.params.id);
  if (isNaN(id)) throw new AppError(400, "Invalid backup ID");
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

export const verifyPin = async (req: Request, res: Response) => {
  const { pin } = req.body || {};
  if (!pin || typeof pin !== "string") {
    throw new AppError(400, "PIN is required");
  }

  const isValid = backupService.validateBackupPin(pin);
  if (!isValid) {
    return res.status(401).json({
      success: false,
      message: "Wrong PIN. Nice try 😄 Please check your backup PIN and try again.",
    });
  }

  return res.json({
    success: true,
    message: "Security PIN verified successfully",
  });
};

