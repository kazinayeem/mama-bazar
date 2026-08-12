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
exports.remove = exports.update = exports.create = exports.getById = exports.getAll = void 0;
const bannerService = __importStar(require("./banner.service"));
const AppError_1 = require("../../utils/AppError");
const cloud_1 = require("../../utils/cloud");
const persistImages = async (req) => {
    const files = (req.files || {});
    const pick = (field) => files[field]?.[0];
    const result = {};
    for (const field of ["image", "imageTablet", "imageMobile"]) {
        const file = pick(field);
        if (!file)
            continue;
        const uploaded = await (0, cloud_1.uploadBuffer)(file.buffer, {
            folder: "banners",
            filename: file.originalname,
            mimeType: file.mimetype,
        });
        const key = field === "imageTablet" ? "imageTablet" : field === "imageMobile" ? "imageMobile" : "image";
        result[key] = uploaded.url;
    }
    return result;
};
const getAll = async (_req, res) => {
    const data = await bannerService.getAll();
    res.json({ success: true, data });
};
exports.getAll = getAll;
const getById = async (req, res) => {
    const data = await bannerService.getById(Number(req.params.id));
    if (!data)
        throw new AppError_1.AppError(404, "Banner not found");
    res.json({ success: true, data });
};
exports.getById = getById;
const create = async (req, res) => {
    const { title, subtitle, link, position, buttonText, priority, status } = req.body;
    const images = await persistImages(req);
    if (!images.image) {
        const url = ["image", "imageTablet", "imageMobile"].find((k) => req.body[k]);
        if (url)
            images[url] = req.body[url];
    }
    if (!images.image)
        throw new AppError_1.AppError(400, "Banner image is required");
    const data = await bannerService.create({
        title,
        subtitle,
        link,
        position: position || "hero",
        buttonText,
        priority: Number(priority) || 0,
        status: status || "active",
        image: images.image,
        imageMobile: images.imageMobile,
        imageTablet: images.imageTablet,
    });
    res.status(201).json({ success: true, data });
};
exports.create = create;
const update = async (req, res) => {
    const id = Number(req.params.id);
    const { title, subtitle, link, position, buttonText, priority, status } = req.body;
    const images = await persistImages(req);
    const updateData = {};
    if (title !== undefined)
        updateData.title = title;
    if (subtitle !== undefined)
        updateData.subtitle = subtitle;
    if (link !== undefined)
        updateData.link = link;
    if (position !== undefined)
        updateData.position = position;
    if (buttonText !== undefined)
        updateData.buttonText = buttonText;
    if (priority !== undefined)
        updateData.priority = Number(priority);
    if (status !== undefined)
        updateData.status = status;
    for (const field of ["image", "imageMobile", "imageTablet"]) {
        if (images[field])
            updateData[field] = images[field];
        else if (req.body[field])
            updateData[field] = req.body[field];
    }
    const data = await bannerService.update(id, updateData);
    res.json({ success: true, data });
};
exports.update = update;
const remove = async (req, res) => {
    await bannerService.remove(Number(req.params.id));
    res.json({ success: true, message: "Banner deleted" });
};
exports.remove = remove;
//# sourceMappingURL=banner.controller.js.map