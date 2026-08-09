import { v2 as cloudinary } from "cloudinary";
import fs from "fs";
import path from "path";
import crypto from "crypto";
import { env } from "../../config/env";

const CLOUD_NAME = process.env.CLOUDINARY_CLOUD_NAME;
const API_KEY = process.env.CLOUDINARY_API_KEY;
const API_SECRET = process.env.CLOUDINARY_API_SECRET;

export const cloudinaryConfigured = Boolean(CLOUD_NAME && API_KEY && API_SECRET);

if (cloudinaryConfigured) {
  cloudinary.config({
    cloud_name: CLOUD_NAME,
    api_key: API_KEY,
    api_secret: API_SECRET,
  });
}

export interface UploadedFileResult {
  url: string;
  publicId?: string;
  provider: "cloudinary" | "local";
  width?: number;
  height?: number;
}

/**
 * Upload a buffer to Cloudinary. Falls back to saving locally when
 * Cloudinary is not configured.
 */
export const uploadBuffer = async (
  buffer: Buffer,
  opts: {
    folder: string;
    filename: string;
    mimeType: string;
  }
): Promise<UploadedFileResult> => {
  if (cloudinaryConfigured) {
    const publicId = `${opts.folder}/${Date.now()}-${crypto.randomBytes(6).toString("hex")}`;
    const result = await new Promise<{ secure_url: string; public_id: string; width?: number; height?: number }>(
      (resolve, reject) => {
        const stream = cloudinary.uploader.upload_stream(
          {
            folder: opts.folder,
            public_id: crypto.randomBytes(6).toString("hex"),
            resource_type: "auto",
          },
          (error, result) => {
            if (error || !result) return reject(error || new Error("Cloudinary upload failed"));
            resolve(result as any);
          }
        );
        stream.end(buffer);
      }
    );

    return {
      url: result.secure_url,
      publicId: result.public_id,
      provider: "cloudinary",
      width: result.width,
      height: result.height,
    };
  }

  // Local fallback
  const uploadDir = env.NODE_ENV === "production" && process.env.VERCEL
    ? "/tmp/uploads"
    : path.join(process.cwd(), env.UPLOAD_DIR);
  const ext = path.extname(opts.filename) || ".bin";
  const filename = `${Date.now()}-${crypto.randomBytes(8).toString("hex")}${ext}`;
  const fullPath = path.join(uploadDir, filename);
  fs.mkdirSync(uploadDir, { recursive: true });
  fs.writeFileSync(fullPath, buffer);

  return {
    url: `/uploads/${filename}`,
    provider: "local",
  };
};

/**
 * Delete an asset by Cloudinary public id, or a local file path.
 */
export const deleteAsset = async (publicId?: string, url?: string) => {
  if (publicId && cloudinaryConfigured) {
    try {
      await cloudinary.uploader.destroy(publicId);
      return { deleted: true, provider: "cloudinary" };
    } catch {
      // ignore destroy failures (asset may already be gone)
    }
  }
  if (url && url.startsWith("/uploads/")) {
    try {
      const fullPath = path.join(process.cwd(), env.UPLOAD_DIR, path.basename(url));
      if (fs.existsSync(fullPath)) fs.unlinkSync(fullPath);
      return { deleted: true, provider: "local" };
    } catch {
      // ignore
    }
  }
  return { deleted: false };
};

export const cloudinaryConfig = {
  configured: cloudinaryConfigured,
  cloudName: CLOUD_NAME || null,
};
