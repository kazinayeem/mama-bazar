"use strict";
/**
 * BackupStorage — cloud-agnostic storage abstraction for backup archives.
 *
 * Production (Vercel):  Cloudinary (reuses existing CLOUDINARY_* credentials)
 * Development (local):  /tmp/backups or project-local storage/backups
 *
 * The backup service only calls: upload / download / delete.
 * Downloads ALWAYS go through the backend (no client-side cross-origin signed URLs).
 */
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.backupStorage = void 0;
const crypto_1 = __importDefault(require("crypto"));
const fs_1 = __importDefault(require("fs"));
const https_1 = __importDefault(require("https"));
const http_1 = __importDefault(require("http"));
const path_1 = __importDefault(require("path"));
const cloudinary_1 = require("cloudinary");
// ─── Configuration ────────────────────────────────────────────────────────────
const CLOUD_NAME = process.env.CLOUDINARY_CLOUD_NAME;
const API_KEY = process.env.CLOUDINARY_API_KEY;
const API_SECRET = process.env.CLOUDINARY_API_SECRET;
const cloudinaryConfigured = Boolean(CLOUD_NAME && API_KEY && API_SECRET);
if (cloudinaryConfigured) {
    cloudinary_1.v2.config({
        cloud_name: CLOUD_NAME,
        api_key: API_KEY,
        api_secret: API_SECRET,
        secure: true, // Always use HTTPS for signed URLs
    });
}
// Local storage directory — /tmp on Vercel (ephemeral; for dev/testing only)
const LOCAL_DIR = process.env.VERCEL
    ? "/tmp/backups"
    : path_1.default.join(process.cwd(), "storage/backups");
// ─── Helpers ──────────────────────────────────────────────────────────────────
/**
 * Fetch a URL to a Buffer, following up to `maxRedirects` HTTP redirects.
 * This is intentionally lightweight — no extra dependencies.
 */
function fetchBuffer(url, maxRedirects = 5) {
    return new Promise((resolve, reject) => {
        const follow = (u, remaining) => {
            const proto = u.startsWith("https://") ? https_1.default : http_1.default;
            proto
                .get(u, (res) => {
                // Follow redirects
                if (remaining > 0 &&
                    res.statusCode &&
                    [301, 302, 303, 307, 308].includes(res.statusCode) &&
                    res.headers.location) {
                    res.resume(); // drain response body so socket is reused
                    const next = res.headers.location.startsWith("http")
                        ? res.headers.location
                        : new URL(res.headers.location, u).toString();
                    return follow(next, remaining - 1);
                }
                if (!res.statusCode || res.statusCode >= 400) {
                    // Drain to free socket, then reject
                    res.resume();
                    return reject(new Error(`HTTP ${res.statusCode} fetching backup from storage`));
                }
                const chunks = [];
                res.on("data", (chunk) => chunks.push(chunk));
                res.on("end", () => resolve(Buffer.concat(chunks)));
                res.on("error", reject);
            })
                .on("error", reject);
        };
        follow(url, maxRedirects);
    });
}
// ─── Cloudinary adapter ───────────────────────────────────────────────────────
const uploadToCloudinary = (buffer) => {
    return new Promise((resolve, reject) => {
        const publicId = `mamabazar/backups/${Date.now()}-${crypto_1.default.randomBytes(4).toString("hex")}`;
        const stream = cloudinary_1.v2.uploader.upload_stream({
            public_id: publicId,
            resource_type: "raw", // binary / ZIP file
            overwrite: false,
            tags: ["backup", "mamabazar"],
        }, (error, result) => {
            if (error || !result) {
                return reject(error ?? new Error("Cloudinary upload failed with no error detail"));
            }
            resolve({ storageKey: result.public_id, size: result.bytes });
        });
        stream.end(buffer);
    });
};
const downloadFromCloudinary = async (storageKey) => {
    // Build a short-lived signed download URL (server-side use only — never exposed to client)
    const signedUrl = cloudinary_1.v2.url(storageKey, {
        resource_type: "raw",
        sign_url: true,
        expires_at: Math.floor(Date.now() / 1000) + 120, // 2-minute window
        type: "upload",
        secure: true,
    });
    const buf = await fetchBuffer(signedUrl);
    if (buf.length === 0) {
        throw new Error("Cloudinary returned an empty response for backup download");
    }
    return buf;
};
const deleteFromCloudinary = async (storageKey) => {
    await cloudinary_1.v2.uploader.destroy(storageKey, {
        resource_type: "raw",
        invalidate: true,
    });
};
// ─── Local /tmp adapter (dev / non-Cloudinary) ────────────────────────────────
const uploadToLocal = (buffer, filename) => {
    fs_1.default.mkdirSync(LOCAL_DIR, { recursive: true });
    const dest = path_1.default.join(LOCAL_DIR, filename);
    fs_1.default.writeFileSync(dest, buffer);
    return { storageKey: dest, size: buffer.length };
};
const downloadFromLocal = (storageKey) => {
    if (!fs_1.default.existsSync(storageKey)) {
        throw new Error(`Backup archive not found: ${storageKey}`);
    }
    return fs_1.default.readFileSync(storageKey);
};
const deleteFromLocal = (storageKey) => {
    if (fs_1.default.existsSync(storageKey))
        fs_1.default.unlinkSync(storageKey);
};
// ─── Public interface ─────────────────────────────────────────────────────────
exports.backupStorage = {
    get provider() {
        return cloudinaryConfigured ? "cloudinary" : "local";
    },
    isCloudinary() {
        return cloudinaryConfigured;
    },
    async upload(buffer, filename) {
        if (cloudinaryConfigured)
            return uploadToCloudinary(buffer);
        return uploadToLocal(buffer, filename);
    },
    /** Always returns the raw binary ZIP buffer — the controller streams it to the client. */
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
};
//# sourceMappingURL=backup.storage.js.map