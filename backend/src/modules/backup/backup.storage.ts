/**
 * BackupStorage — cloud-agnostic storage abstraction for backup archives.
 *
 * Production (Vercel):  Cloudinary (reuses existing CLOUDINARY_* credentials)
 * Development (local):  /tmp/backups — only valid within the current process run
 *
 * The backup service never knows which adapter is active; it simply calls
 * upload / download / delete / getDownloadUrl.
 */

import crypto from "crypto";
import fs from "fs";
import path from "path";
import { v2 as cloudinary } from "cloudinary";

// ─── Configuration ────────────────────────────────────────────────────────────

const CLOUD_NAME = process.env.CLOUDINARY_CLOUD_NAME;
const API_KEY    = process.env.CLOUDINARY_API_KEY;
const API_SECRET = process.env.CLOUDINARY_API_SECRET;

const cloudinaryConfigured = Boolean(CLOUD_NAME && API_KEY && API_SECRET);

if (cloudinaryConfigured) {
  cloudinary.config({ cloud_name: CLOUD_NAME, api_key: API_KEY, api_secret: API_SECRET });
}

// Vercel serverless: write to /tmp (ephemeral, only used as final fallback in dev)
const LOCAL_DIR = process.env.VERCEL ? "/tmp/backups" : path.join(process.cwd(), "storage/backups");

// ─── Types ────────────────────────────────────────────────────────────────────

export interface BackupUploadResult {
  /** Stable identifier for this backup — Cloudinary public_id or local filename */
  storageKey: string;
  /** Bytes of the stored archive */
  size: number;
}

// ─── Cloudinary adapter ───────────────────────────────────────────────────────

const uploadToCloudinary = (buffer: Buffer, filename: string): Promise<BackupUploadResult> => {
  return new Promise((resolve, reject) => {
    const publicId = `mamabazar/backups/${Date.now()}-${crypto.randomBytes(4).toString("hex")}`;
    const stream = cloudinary.uploader.upload_stream(
      {
        public_id:     publicId,
        resource_type: "raw",        // binary / ZIP file
        overwrite:     false,
        tags:          ["backup", "mamabazar"],
      },
      (error, result) => {
        if (error || !result) return reject(error ?? new Error("Cloudinary upload failed"));
        resolve({ storageKey: result.public_id, size: result.bytes });
      }
    );
    stream.end(buffer);
  });
};

const downloadFromCloudinary = async (storageKey: string): Promise<Buffer> => {
  const signedUrl = cloudinary.url(storageKey, {
    resource_type: "raw",
    sign_url:      true,
    expires_at:    Math.floor(Date.now() / 1000) + 300,
    type:          "upload",
  });

  return new Promise<Buffer>((resolve, reject) => {
    const https = require("https") as typeof import("https");
    const http  = require("http")  as typeof import("http");
    const proto = signedUrl.startsWith("https") ? https : http;

    proto.get(signedUrl, (res) => {
      if (res.statusCode && res.statusCode >= 400) {
        return reject(new Error(`Cloudinary download returned ${res.statusCode}`));
      }
      const chunks: Buffer[] = [];
      res.on("data", (chunk: Buffer) => chunks.push(chunk));
      res.on("end",  () => resolve(Buffer.concat(chunks)));
      res.on("error", reject);
    }).on("error", reject);
  });
};

const deleteFromCloudinary = async (storageKey: string): Promise<void> => {
  await cloudinary.uploader.destroy(storageKey, { resource_type: "raw", invalidate: true });
};

const getCloudinaryDownloadUrl = (storageKey: string): string => {
  // Signed URL expires in 1 hour — safe for admin-initiated downloads
  return cloudinary.url(storageKey, {
    resource_type: "raw",
    sign_url:      true,
    expires_at:    Math.floor(Date.now() / 1000) + 3600,
    type:          "upload",
  });
};

// ─── Local /tmp adapter (dev only) ────────────────────────────────────────────

const uploadToLocal = (buffer: Buffer, filename: string): BackupUploadResult => {
  fs.mkdirSync(LOCAL_DIR, { recursive: true });
  const dest = path.join(LOCAL_DIR, filename);
  fs.writeFileSync(dest, buffer);
  return { storageKey: dest, size: buffer.length };
};

const downloadFromLocal = (storageKey: string): Buffer => {
  if (!fs.existsSync(storageKey)) throw new Error("Backup file not found on local filesystem");
  return fs.readFileSync(storageKey);
};

const deleteFromLocal = (storageKey: string): void => {
  if (fs.existsSync(storageKey)) fs.unlinkSync(storageKey);
};

const getLocalDownloadUrl = (storageKey: string): string => {
  // In dev, the controller streams the file directly; the URL is just the path
  return storageKey;
};

// ─── Public interface (adapter-transparent) ────────────────────────────────────

export const backupStorage = {
  get provider(): "cloudinary" | "local" {
    return cloudinaryConfigured ? "cloudinary" : "local";
  },

  async upload(buffer: Buffer, filename: string): Promise<BackupUploadResult> {
    if (cloudinaryConfigured) return uploadToCloudinary(buffer, filename);
    return uploadToLocal(buffer, filename);
  },

  async download(storageKey: string): Promise<Buffer> {
    if (cloudinaryConfigured) return downloadFromCloudinary(storageKey);
    return downloadFromLocal(storageKey);
  },

  async delete(storageKey: string): Promise<void> {
    if (cloudinaryConfigured) return deleteFromCloudinary(storageKey);
    deleteFromLocal(storageKey);
  },

  getDownloadUrl(storageKey: string): string {
    if (cloudinaryConfigured) return getCloudinaryDownloadUrl(storageKey);
    return getLocalDownloadUrl(storageKey);
  },

  isCloudinary(): boolean {
    return cloudinaryConfigured;
  },
};
