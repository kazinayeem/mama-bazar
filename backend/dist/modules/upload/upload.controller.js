"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.uploadPaymentProof = void 0;
const AppError_1 = require("../../utils/AppError");
const cloud_1 = require("../../utils/cloud");
const MAX_SIZE = 5 * 1024 * 1024;
const ALLOWED_MIME = ["image/jpeg", "image/png", "image/webp", "image/heic", "image/heif"];
const uploadPaymentProof = async (req, res) => {
    const file = req.file;
    if (!file)
        throw new AppError_1.AppError(400, "No file uploaded. Attach a screenshot of your payment.");
    if (!ALLOWED_MIME.includes(file.mimetype)) {
        throw new AppError_1.AppError(400, "Only JPG, PNG, WebP or HEIC images are allowed");
    }
    if (file.size > MAX_SIZE) {
        throw new AppError_1.AppError(400, "File too large. Maximum size is 5MB");
    }
    const result = await (0, cloud_1.uploadBuffer)(file.buffer, {
        folder: "payment-proofs",
        filename: file.originalname,
        mimeType: file.mimetype,
    });
    res.status(201).json({ success: true, data: result });
};
exports.uploadPaymentProof = uploadPaymentProof;
//# sourceMappingURL=upload.controller.js.map