"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || (function () {
    var ownKeys = function(o) {
        ownKeys = Object.getOwnPropertyNames || function (o) {
            var ar = [];
            for (var k in o) if (Object.prototype.hasOwnProperty.call(o, k)) ar[ar.length] = k;
            return ar;
        };
        return ownKeys(o);
    };
    return function (mod) {
        if (mod && mod.__esModule) return mod;
        var result = {};
        if (mod != null) for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
        __setModuleDefault(result, mod);
        return result;
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
exports.config = exports.remove = exports.update = exports.getById = exports.getFolders = exports.getAll = exports.uploadMultiple = exports.upload = void 0;
const mediaService = __importStar(require("./media.service"));
const AppError_1 = require("../../utils/AppError");
const cloud_1 = require("../../utils/cloud");
const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 48;
const DEFAULT_FOLDER = "general";
const upload = async (req, res) => {
    if (!req.file)
        throw new AppError_1.AppError(400, "No file uploaded");
    const uploaderId = req.user?.id;
    const folder = req.body.folder || DEFAULT_FOLDER;
    const alt = req.body.alt;
    const data = await mediaService.saveMedia({
        buffer: req.file.buffer,
        filename: req.file.originalname,
        mimeType: req.file.mimetype,
        size: req.file.size,
        folder,
        alt,
        uploaderId,
    });
    res.status(201).json({ success: true, data });
};
exports.upload = upload;
const uploadMultiple = async (req, res) => {
    const files = req.files;
    if (!files || files.length === 0)
        throw new AppError_1.AppError(400, "No files uploaded");
    const uploaderId = req.user?.id;
    const folder = req.body.folder || DEFAULT_FOLDER;
    const results = [];
    for (const file of files) {
        const data = await mediaService.saveMedia({
            buffer: file.buffer,
            filename: file.originalname,
            mimeType: file.mimetype,
            size: file.size,
            folder,
            uploaderId,
        });
        results.push(data);
    }
    res.status(201).json({ success: true, data: results });
};
exports.uploadMultiple = uploadMultiple;
const getAll = async (req, res) => {
    const result = await mediaService.getAll({
        page: Number(req.query.page) || DEFAULT_PAGE,
        limit: Number(req.query.limit) || DEFAULT_LIMIT,
        folder: req.query.folder,
        search: req.query.search,
    });
    res.json({ success: true, ...result });
};
exports.getAll = getAll;
const getFolders = async (_req, res) => {
    const data = await mediaService.getFolders();
    res.json({ success: true, data });
};
exports.getFolders = getFolders;
const getById = async (req, res) => {
    const data = await mediaService.getById(Number(req.params.id));
    if (!data)
        throw new AppError_1.AppError(404, "Media not found");
    res.json({ success: true, data });
};
exports.getById = getById;
const update = async (req, res) => {
    const data = await mediaService.updateAlt(Number(req.params.id), req.body.alt);
    if (!data)
        throw new AppError_1.AppError(404, "Media not found");
    res.json({ success: true, data });
};
exports.update = update;
const remove = async (req, res) => {
    const result = await mediaService.remove(Number(req.params.id));
    if (!result.success)
        throw new AppError_1.AppError(404, "Media not found");
    res.json({ success: true, message: "Media deleted" });
};
exports.remove = remove;
const config = async (_req, res) => {
    res.json({ success: true, data: cloud_1.cloudinaryConfig });
};
exports.config = config;
//# sourceMappingURL=media.controller.js.map