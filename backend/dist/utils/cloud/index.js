"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.cloudinaryConfig = exports.deleteAsset = exports.uploadBuffer = exports.cloudinaryConfigured = void 0;
const cloudinary_1 = require("cloudinary");
const fs_1 = __importDefault(require("fs"));
const path_1 = __importDefault(require("path"));
const crypto_1 = __importDefault(require("crypto"));
const env_1 = require("../../config/env");
const CLOUD_NAME = process.env.CLOUDINARY_CLOUD_NAME;
const API_KEY = process.env.CLOUDINARY_API_KEY;
const API_SECRET = process.env.CLOUDINARY_API_SECRET;
exports.cloudinaryConfigured = Boolean(CLOUD_NAME && API_KEY && API_SECRET);
if (exports.cloudinaryConfigured) {
    cloudinary_1.v2.config({
        cloud_name: CLOUD_NAME,
        api_key: API_KEY,
        api_secret: API_SECRET,
    });
}
/**
 * Upload a buffer to Cloudinary. Falls back to saving locally when
 * Cloudinary is not configured.
 */
const uploadBuffer = async (buffer, opts) => {
    if (exports.cloudinaryConfigured) {
        const result = await new Promise((resolve, reject) => {
            const stream = cloudinary_1.v2.uploader.upload_stream({
                folder: opts.folder,
                public_id: crypto_1.default.randomBytes(6).toString("hex"),
                resource_type: "auto",
            }, (error, result) => {
                if (error || !result)
                    return reject(error || new Error("Cloudinary upload failed"));
                resolve(result);
            });
            stream.end(buffer);
        });
        return {
            url: result.secure_url,
            publicId: result.public_id,
            provider: "cloudinary",
            width: result.width,
            height: result.height,
        };
    }
    // Local fallback
    const uploadDir = env_1.env.NODE_ENV === "production" && process.env.VERCEL
        ? "/tmp/uploads"
        : path_1.default.join(process.cwd(), env_1.env.UPLOAD_DIR);
    const ext = path_1.default.extname(opts.filename) || ".bin";
    const filename = `${Date.now()}-${crypto_1.default.randomBytes(8).toString("hex")}${ext}`;
    const fullPath = path_1.default.join(uploadDir, filename);
    fs_1.default.mkdirSync(uploadDir, { recursive: true });
    fs_1.default.writeFileSync(fullPath, buffer);
    return {
        url: `/uploads/${filename}`,
        provider: "local",
    };
};
exports.uploadBuffer = uploadBuffer;
/**
 * Delete an asset by Cloudinary public id, or a local file path.
 */
const deleteAsset = async (publicId, url) => {
    if (publicId && exports.cloudinaryConfigured) {
        try {
            await cloudinary_1.v2.uploader.destroy(publicId);
            return { deleted: true, provider: "cloudinary" };
        }
        catch {
            // ignore destroy failures (asset may already be gone)
        }
    }
    if (url && url.startsWith("/uploads/")) {
        try {
            const fullPath = path_1.default.join(process.cwd(), env_1.env.UPLOAD_DIR, path_1.default.basename(url));
            if (fs_1.default.existsSync(fullPath))
                fs_1.default.unlinkSync(fullPath);
            return { deleted: true, provider: "local" };
        }
        catch {
            // ignore
        }
    }
    return { deleted: false };
};
exports.deleteAsset = deleteAsset;
exports.cloudinaryConfig = {
    configured: exports.cloudinaryConfigured,
    cloudName: CLOUD_NAME || null,
};
//# sourceMappingURL=index.js.map