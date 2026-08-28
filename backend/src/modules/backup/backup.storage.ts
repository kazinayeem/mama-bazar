/**
 * BackupStorage — cloud-agnostic storage abstraction for backup archives.
 *
 * Production (Vercel):  Cloudinary (reuses existing CLOUDINARY_* credentials)
 * Development (local):  /tmp/backups or project-local storage/backups
 *
 * The backup service only calls: upload / download / delete.
 * Downloads ALWAYS go through the backend (no client-side cross-origin signed URLs).
 */

import crypto from "crypto";
import fs from "fs";
import https from "https";
import http from "http";
import path from "path";
import { v2 as cloudinary } from "cloudinary";

// ─── Configuration ────────────────────────────────────────────────────────────

const CLOUD_NAME = process.env.CLOUDINARY_CLOUD_NAME;
const API_KEY    = process.env.CLOUDINARY_API_KEY;
const API_SECRET = process.env.CLOUDINARY_API_SECRET;

const cloudinaryConfigured = Boolean(CLOUD_NAME && API_KEY && API_SECRET);

if (cloudinaryConfigured) {
  cloudinary.config({
    cloud_name: CLOUD_NAME,
    api_key:    API_KEY,
    api_secret: API_SECRET,
    secure:     true,   // Always use HTTPS for signed URLs
  });
}

// Local storage directory — /tmp on Vercel (ephemeral; for dev/testing only)
const LOCAL_DIR = process.env.VERCEL
  ? "/tmp/backups"
  : path.join(process.cwd(), "storage/backups");

// ─── Types ────────────────────────────────────────────────────────────────────

export interface BackupUploadResult {
  /** Stable identifier — Cloudinary public_id or absolute local filesystem path */
  storageKey: string;
  /** Byte size of the stored archive */
  size: number;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Fetch a URL to a Buffer, following up to `maxRedirects` HTTP redirects.
 * This is intentionally lightweight — no extra dependencies.
 */
function fetchBuffer(url: string, maxRedirects = 5): Promise<Buffer> {
  return new Promise((resolve, reject) => {
    const follow = (u: string, remaining: number) => {
      const proto = u.startsWith("https://") ? https : http;
      proto
        .get(u, (res) => {
          // Follow redirects
          if (
            remaining > 0 &&
            res.statusCode &&
            [301, 302, 303, 307, 308].includes(res.statusCode) &&
            res.headers.location
          ) {
            res.resume(); // drain response body so socket is reused
            const next = res.headers.location.startsWith("http")
              ? res.headers.location
              : new URL(res.headers.location, u).toString();
            return follow(next, remaining - 1);
          }

          if (!res.statusCode || res.statusCode >= 400) {
            // Drain to free socket, then reject
            res.resume();
            return reject(
              new Error(`HTTP ${res.statusCode} fetching backup from storage`)
            );
          }

          const chunks: Buffer[] = [];
          res.on("data", (chunk: Buffer) => chunks.push(chunk));
          res.on("end", () => resolve(Buffer.concat(chunks)));
          res.on("error", reject);
        })
        .on("error", reject);
    };
    follow(url, maxRedirects);
  });
}

// ─── Cloudinary adapter ───────────────────────────────────────────────────────

const uploadToCloudinary = (buffer: Buffer): Promise<BackupUploadResult> => {
  return new Promise((resolve, reject) => {
    const publicId = `mamabazar/backups/${Date.now()}-${crypto.randomBytes(4).toString("hex")}`;
    const stream = cloudinary.uploader.upload_stream(
      {
        public_id:     publicId,
        resource_type: "raw",   // binary / ZIP file
        overwrite:     false,
        tags:          ["backup", "mamabazar"],
      },
      (error, result) => {
        if (error || !result) {
          return reject(error ?? new Error("Cloudinary upload failed with no error detail"));
        }
        resolve({ storageKey: result.public_id, size: result.bytes });
      }
    );
    stream.end(buffer);
  });
};

const downloadFromCloudinary = async (storageKey: string): Promise<Buffer> => {
  // Build a short-lived signed download URL (server-side use only — never exposed to client)
  const signedUrl = cloudinary.url(storageKey, {
    resource_type: "raw",
    sign_url:      true,
    expires_at:    Math.floor(Date.now() / 1000) + 120, // 2-minute window
    type:          "upload",
    secure:        true,
  });

  const buf = await fetchBuffer(signedUrl);
  if (buf.length === 0) {
    throw new Error("Cloudinary returned an empty response for backup download");
  }
  return buf;
};

const deleteFromCloudinary = async (storageKey: string): Promise<void> => {
  await cloudinary.uploader.destroy(storageKey, {
    resource_type: "raw",
    invalidate:    true,
  });
};

// ─── Local /tmp adapter (dev / non-Cloudinary) ────────────────────────────────

const uploadToLocal = (buffer: Buffer, filename: string): BackupUploadResult => {
  fs.mkdirSync(LOCAL_DIR, { recursive: true });
  const dest = path.join(LOCAL_DIR, filename);
  fs.writeFileSync(dest, buffer);
  return { storageKey: dest, size: buffer.length };
};

const downloadFromLocal = (storageKey: string): Buffer => {
  if (!fs.existsSync(storageKey)) {
    throw new Error(`Backup archive not found: ${storageKey}`);
  }
  return fs.readFileSync(storageKey);
};

const deleteFromLocal = (storageKey: string): void => {
  if (fs.existsSync(storageKey)) fs.unlinkSync(storageKey);
};

// ─── Public interface ─────────────────────────────────────────────────────────

export const backupStorage = {
  get provider(): "cloudinary" | "local" {
    return cloudinaryConfigured ? "cloudinary" : "local";
  },

  isCloudinary(): boolean {
    return cloudinaryConfigured;
  },

  async upload(buffer: Buffer, filename: string): Promise<BackupUploadResult> {
    if (cloudinaryConfigured) return uploadToCloudinary(buffer);
    return uploadToLocal(buffer, filename);
  },

  /** Always returns the raw binary ZIP buffer — the controller streams it to the client. */
  async download(storageKey: string): Promise<Buffer> {
    if (cloudinaryConfigured) return downloadFromCloudinary(storageKey);
    return downloadFromLocal(storageKey);
  },

  async delete(storageKey: string): Promise<void> {
    if (cloudinaryConfigured) return deleteFromCloudinary(storageKey);
    deleteFromLocal(storageKey);
  },
};
