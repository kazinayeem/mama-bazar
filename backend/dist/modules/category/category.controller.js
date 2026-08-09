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
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.moveProducts = exports.remove = exports.update = exports.create = exports.getUsage = exports.getBySlug = exports.getById = exports.getTree = exports.listAdmin = exports.getAll = void 0;
const categoryService = __importStar(require("./category.service"));
const slugify_1 = __importDefault(require("slugify"));
const AppError_1 = require("../../utils/AppError");
const cloud_1 = require("../../utils/cloud");
const toBool = (value) => {
    if (value === undefined || value === null || value === "")
        return undefined;
    return value === true || value === "true" || value === 1 || value === "1";
};
const toNumber = (value) => {
    if (value === undefined || value === null || value === "")
        return undefined;
    const n = Number(value);
    return Number.isNaN(n) ? undefined : n;
};
const toIdOrNull = (value) => {
    if (value === undefined)
        return undefined;
    if (value === null || value === "" || value === "none")
        return null;
    const n = Number(value);
    return Number.isNaN(n) ? null : n;
};
const coerce = (body) => ({
    parentId: toIdOrNull(body.parentId),
    featured: toBool(body.featured),
    homepageVisibility: toBool(body.homepageVisibility),
    sortOrder: toNumber(body.sortOrder),
    status: body.status,
});
const persistImage = async (file, fallback) => {
    if (!file)
        return fallback;
    const uploaded = await (0, cloud_1.uploadBuffer)(file.buffer, {
        folder: "categories",
        filename: file.originalname,
        mimeType: file.mimetype,
    });
    return uploaded.url;
};
const getAll = async (_req, res) => {
    const data = await categoryService.getAllFlat();
    res.json({ success: true, data });
};
exports.getAll = getAll;
const listAdmin = async (req, res) => {
    const q = req.query;
    const result = await categoryService.getAll({
        page: q.page ? Number(q.page) : 1,
        limit: q.limit ? Number(q.limit) : 20,
        search: q.search,
        status: q.status,
        parentId: q.parentId,
        featured: toBool(q.featured),
        sort: q.sort,
    });
    res.json({ success: true, ...result });
};
exports.listAdmin = listAdmin;
const getTree = async (_req, res) => {
    const data = await categoryService.getTree();
    res.json({ success: true, data });
};
exports.getTree = getTree;
const getById = async (req, res) => {
    const data = await categoryService.getById(Number(req.params.id));
    if (!data)
        throw new AppError_1.AppError(404, "Category not found");
    res.json({ success: true, data });
};
exports.getById = getById;
const getBySlug = async (req, res) => {
    const data = await categoryService.getBySlug(req.params.slug);
    if (!data)
        throw new AppError_1.AppError(404, "Category not found");
    res.json({ success: true, data });
};
exports.getBySlug = getBySlug;
const getUsage = async (req, res) => {
    const data = await categoryService.getUsage(Number(req.params.id));
    res.json({ success: true, data });
};
exports.getUsage = getUsage;
const create = async (req, res) => {
    const body = req.body;
    if (!body.name || !String(body.name).trim())
        throw new AppError_1.AppError(400, "Name is required");
    const slug = body.slug ? String(body.slug) : (0, slugify_1.default)(String(body.name), { lower: true, strict: true });
    const image = await persistImage(req.file, body.image ? String(body.image) : undefined);
    const data = await categoryService.create({
        name: String(body.name).trim(),
        slug,
        parentId: toIdOrNull(body.parentId),
        image,
        icon: body.icon ? String(body.icon) : undefined,
        banner: body.banner ? String(body.banner) : undefined,
        thumbnail: body.thumbnail ? String(body.thumbnail) : undefined,
        description: body.description ? String(body.description) : undefined,
        featured: toBool(body.featured),
        homepageVisibility: toBool(body.homepageVisibility),
        sortOrder: toNumber(body.sortOrder),
        seoTitle: body.seoTitle ? String(body.seoTitle) : undefined,
        seoDescription: body.seoDescription ? String(body.seoDescription) : undefined,
        seoKeywords: body.seoKeywords ? String(body.seoKeywords) : undefined,
        status: body.status || "active",
    });
    res.status(201).json({ success: true, data });
};
exports.create = create;
const update = async (req, res) => {
    const id = Number(req.params.id);
    const body = req.body;
    const existing = await categoryService.getById(id);
    if (!existing)
        throw new AppError_1.AppError(404, "Category not found");
    const updateData = { ...coerce(body) };
    if (body.name) {
        updateData.name = String(body.name).trim();
        if (!body.slug)
            updateData.slug = (0, slugify_1.default)(String(body.name), { lower: true, strict: true });
    }
    if (body.slug)
        updateData.slug = String(body.slug);
    if (body.description !== undefined)
        updateData.description = body.description ? String(body.description) : null;
    if (body.icon !== undefined)
        updateData.icon = body.icon ? String(body.icon) : null;
    if (body.banner !== undefined)
        updateData.banner = body.banner ? String(body.banner) : null;
    if (body.thumbnail !== undefined)
        updateData.thumbnail = body.thumbnail ? String(body.thumbnail) : null;
    if (body.seoTitle !== undefined)
        updateData.seoTitle = body.seoTitle ? String(body.seoTitle) : null;
    if (body.seoDescription !== undefined)
        updateData.seoDescription = body.seoDescription ? String(body.seoDescription) : null;
    if (body.seoKeywords !== undefined)
        updateData.seoKeywords = body.seoKeywords ? String(body.seoKeywords) : null;
    const uploadedImage = await persistImage(req.file, undefined);
    if (uploadedImage)
        updateData.image = uploadedImage;
    else if (body.image !== undefined)
        updateData.image = body.image ? String(body.image) : null;
    const data = await categoryService.update(id, updateData);
    res.json({ success: true, data });
};
exports.update = update;
const remove = async (req, res) => {
    const id = Number(req.params.id);
    const existing = await categoryService.getById(id);
    if (!existing)
        throw new AppError_1.AppError(404, "Category not found");
    const usage = await categoryService.getUsage(id);
    if (usage.subCategories > 0) {
        throw new AppError_1.AppError(409, `This category has ${usage.subCategories} sub-categories. Move or delete them first.`, { usageCount: usage.products, subCategories: usage.subCategories, code: "has_children" });
    }
    if (usage.products > 0) {
        throw new AppError_1.AppError(409, `This category is currently used by ${usage.products} product${usage.products > 1 ? "s" : ""}.`, { usageCount: usage.products, subCategories: 0, code: "in_use" });
    }
    await categoryService.remove(id);
    res.json({ success: true, message: "Category deleted" });
};
exports.remove = remove;
const moveProducts = async (req, res) => {
    const id = Number(req.params.id);
    const targetId = toIdOrNull(req.body.targetId) ?? null;
    const result = await categoryService.moveProducts(id, targetId);
    res.json({ success: true, data: result });
};
exports.moveProducts = moveProducts;
//# sourceMappingURL=category.controller.js.map