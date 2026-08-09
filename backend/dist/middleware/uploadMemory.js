"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.uploadMemory = void 0;
const multer_1 = __importDefault(require("multer"));
const storage = multer_1.default.memoryStorage();
const fileFilter = (req, file, cb) => {
    const allowedTypes = [
        "image/jpeg",
        "image/png",
        "image/webp",
        "image/gif",
        "image/svg+xml",
        "image/avif",
        "video/mp4",
        "video/webm",
        "application/pdf",
    ];
    if (allowedTypes.includes(file.mimetype)) {
        cb(null, true);
    }
    else {
        cb(new Error("File type not allowed"));
    }
};
exports.uploadMemory = (0, multer_1.default)({
    storage,
    fileFilter,
    limits: { fileSize: 20 * 1024 * 1024 },
});
//# sourceMappingURL=uploadMemory.js.map