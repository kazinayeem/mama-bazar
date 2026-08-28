"use strict";
/**
 * BackupStorage — cloud-agnostic storage abstraction for backup archives.
 *
 * Production (Vercel):  Cloudinary (reuses existing CLOUDINARY_* credentials)
 * Development (local):  /tmp/backups — only valid within the current process run
 *
 * The backup service never knows which adapter is active; it simply calls
 * upload / download / delete / getDownloadUrl.
 */
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.backupStorage = void 0;
const crypto_1 = __importDefault(require("crypto"));
const fs_1 = __importDefault(require("fs"));
const path_1 = __importDefault(require("path"));
const cloudinary_1 = require("cloudinary");
// ─── Configuration ────────────────────────────────────────────────────────────
const CLOUD_NAME = process.env.CLOUDINARY_CLOUD_NAME;
const API_KEY = process.env.CLOUDINARY_API_KEY;
const API_SECRET = process.env.CLOUDINARY_API_SECRET;
const cloudinaryConfigured = Boolean(CLOUD_NAME && API_KEY && API_SECRET);
if (cloudinaryConfigured) {
    cloudinary_1.v2.config({ cloud_name: CLOUD_NAME, api_key: API_KEY, api_secret: API_SECRET });
}
// Vercel serverless: write to /tmp (ephemeral, only used as final fallback in dev)
const LOCAL_DIR = process.env.VERCEL ? "/tmp/backups" : path_1.default.join(process.cwd(), "storage/backups");
// ─── Cloudinary adapter ───────────────────────────────────────────────────────
const uploadToCloudinary = (buffer, filename) => {
    return new Promise((resolve, reject) => {
        const publicId = `mamabazar/backups/${Date.now()}-${crypto_1.default.randomBytes(4).toString("hex")}`;
        const stream = cloudinary_1.v2.uploader.upload_stream({
            public_id: publicId,
            resource_type: "raw", // binary / ZIP file
            overwrite: false,
            tags: ["backup", "mamabazar"],
        }, (error, result) => {
            if (error || !result)
                return reject(error ?? new Error("Cloudinary upload failed"));
            resolve({ storageKey: result.public_id, size: result.bytes });
        });
        stream.end(buffer);
    });
};
const downloadFromCloudinary = async (storageKey) => {
    const signedUrl = cloudinary_1.v2.url(storageKey, {
        resource_type: "raw",
        sign_url: true,
        expires_at: Math.floor(Date.now() / 1000) + 300,
        type: "upload",
    });
    return new Promise((resolve, reject) => {
        const https = require("https");
        const http = require("http");
        const proto = signedUrl.startsWith("https") ? https : http;
        proto.get(signedUrl, (res) => {
            if (res.statusCode && res.statusCode >= 400) {
                return reject(new Error(`Cloudinary download returned ${res.statusCode}`));
            }
            const chunks = [];
            res.on("data", (chunk) => chunks.push(chunk));
            res.on("end", () => resolve(Buffer.concat(chunks)));
            res.on("error", reject);
        }).on("error", reject);
    });
};
const deleteFromCloudinary = async (storageKey) => {
    await cloudinary_1.v2.uploader.destroy(storageKey, { resource_type: "raw", invalidate: true });
};
const getCloudinaryDownloadUrl = (storageKey) => {
    // Signed URL expires in 1 hour — safe for admin-initiated downloads
    return cloudinary_1.v2.url(storageKey, {
        resource_type: "raw",
        sign_url: true,
        expires_at: Math.floor(Date.now() / 1000) + 3600,
        type: "upload",
    });
};
// ─── Local /tmp adapter (dev only) ────────────────────────────────────────────
const uploadToLocal = (buffer, filename) => {
    fs_1.default.mkdirSync(LOCAL_DIR, { recursive: true });
    const dest = path_1.default.join(LOCAL_DIR, filename);
    fs_1.default.writeFileSync(dest, buffer);
    return { storageKey: dest, size: buffer.length };
};
const downloadFromLocal = (storageKey) => {
    if (!fs_1.default.existsSync(storageKey))
        throw new Error("Backup file not found on local filesystem");
    return fs_1.default.readFileSync(storageKey);
};
const deleteFromLocal = (storageKey) => {
    if (fs_1.default.existsSync(storageKey))
        fs_1.default.unlinkSync(storageKey);
};
const getLocalDownloadUrl = (storageKey) => {
    // In dev, the controller streams the file directly; the URL is just the path
    return storageKey;
};
// ─── Public interface (adapter-transparent) ────────────────────────────────────
exports.backupStorage = {
    get provider() {
        return cloudinaryConfigured ? "cloudinary" : "local";
    },
    async upload(buffer, filename) {
        if (cloudinaryConfigured)
            return uploadToCloudinary(buffer, filename);
        return uploadToLocal(buffer, filename);
    },
    async download(storageKey) {
        if (cloudinaryConfigured)
            return downloadFromCloudinary(storageKey);
        return downloadFromLocal(storageKey);
    },
    async delete(storageKey) {
        if (cloudinaryConfigured)
            return deleteFromCloudinary(storageKey);
        deleteFromLocal(storageKey);
    },
    getDownloadUrl(storageKey) {
        if (cloudinaryConfigured)
            return getCloudinaryDownloadUrl(storageKey);
        return getLocalDownloadUrl(storageKey);
    },
    isCloudinary() {
        return cloudinaryConfigured;
    },
};
//# sourceMappingURL=backup.storage.js.map