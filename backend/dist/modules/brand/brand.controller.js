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
exports.moveProducts = exports.remove = exports.update = exports.create = exports.getUsage = exports.getBySlug = exports.getById = exports.listAdmin = exports.getAll = void 0;
const brandService = __importStar(require("./brand.service"));
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
const persistLogo = async (req) => {
    if (!req.file)
        return undefined;
    const uploaded = await (0, cloud_1.uploadBuffer)(req.file.buffer, {
        folder: "brands",
        filename: req.file.originalname,
        mimeType: req.file.mimetype,
    });
    return uploaded.url;
};
const getAll = async (_req, res) => {
    const data = await brandService.getAllActive();
    res.json({ success: true, data });
};
exports.getAll = getAll;
const listAdmin = async (req, res) => {
    const q = req.query;
    const result = await brandService.getAll({
        page: q.page ? Number(q.page) : 1,
        limit: q.limit ? Number(q.limit) : 20,
        search: q.search,
        status: q.status,
        featured: toBool(q.featured),
        sort: q.sort,
    });
    res.json({ success: true, ...result });
};
exports.listAdmin = listAdmin;
const getById = async (req, res) => {
    const data = await brandService.getById(Number(req.params.id));
    if (!data)
        throw new AppError_1.AppError(404, "Brand not found");
    res.json({ success: true, data });
};
exports.getById = getById;
const getBySlug = async (req, res) => {
    const data = await brandService.getBySlug(req.params.slug);
    if (!data)
        throw new AppError_1.AppError(404, "Brand not found");
    res.json({ success: true, data });
};
exports.getBySlug = getBySlug;
const getUsage = async (req, res) => {
    const data = await brandService.getUsage(Number(req.params.id));
    res.json({ success: true, data: { products: data } });
};
exports.getUsage = getUsage;
const create = async (req, res) => {
    const body = req.body;
    if (!body.name || !String(body.name).trim())
        throw new AppError_1.AppError(400, "Name is required");
    const slug = body.slug ? String(body.slug) : (0, slugify_1.default)(String(body.name), { lower: true, strict: true });
    const logo = await persistLogo(req);
    const data = await brandService.create({
        name: String(body.name).trim(),
        slug,
        logo,
        bannerImage: body.bannerImage ? String(body.bannerImage) : undefined,
        description: body.description ? String(body.description) : undefined,
        website: body.website ? String(body.website) : undefined,
        countryOfOrigin: body.countryOfOrigin ? String(body.countryOfOrigin) : undefined,
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
    const existing = await brandService.getById(id);
    if (!existing)
        throw new AppError_1.AppError(404, "Brand not found");
    const updateData = {
        featured: toBool(body.featured),
        homepageVisibility: toBool(body.homepageVisibility),
        sortOrder: toNumber(body.sortOrder),
        status: body.status,
    };
    if (body.name) {
        updateData.name = String(body.name).trim();
        if (!body.slug)
            updateData.slug = (0, slugify_1.default)(String(body.name), { lower: true, strict: true });
    }
    if (body.slug)
        updateData.slug = String(body.slug);
    if (body.bannerImage !== undefined)
        updateData.bannerImage = body.bannerImage ? String(body.bannerImage) : null;
    if (body.description !== undefined)
        updateData.description = body.description ? String(body.description) : null;
    if (body.website !== undefined)
        updateData.website = body.website ? String(body.website) : null;
    if (body.countryOfOrigin !== undefined)
        updateData.countryOfOrigin = body.countryOfOrigin ? String(body.countryOfOrigin) : null;
    if (body.seoTitle !== undefined)
        updateData.seoTitle = body.seoTitle ? String(body.seoTitle) : null;
    if (body.seoDescription !== undefined)
        updateData.seoDescription = body.seoDescription ? String(body.seoDescription) : null;
    if (body.seoKeywords !== undefined)
        updateData.seoKeywords = body.seoKeywords ? String(body.seoKeywords) : null;
    const uploadedLogo = await persistLogo(req);
    if (uploadedLogo)
        updateData.logo = uploadedLogo;
    else if (body.logo !== undefined)
        updateData.logo = body.logo ? String(body.logo) : null;
    const data = await brandService.update(id, updateData);
    res.json({ success: true, data });
};
exports.update = update;
const remove = async (req, res) => {
    const id = Number(req.params.id);
    const existing = await brandService.getById(id);
    if (!existing)
        throw new AppError_1.AppError(404, "Brand not found");
    const usage = await brandService.getUsage(id);
    if (usage > 0) {
        throw new AppError_1.AppError(409, `This brand is currently used by ${usage} product${usage > 1 ? "s" : ""}.`, { usageCount: usage, code: "in_use" });
    }
    await brandService.remove(id);
    res.json({ success: true, message: "Brand deleted" });
};
exports.remove = remove;
const moveProducts = async (req, res) => {
    const id = Number(req.params.id);
    const targetId = toIdOrNull(req.body.targetId) ?? null;
    const result = await brandService.moveProducts(id, targetId);
    res.json({ success: true, data: result });
};
exports.moveProducts = moveProducts;
//# sourceMappingURL=brand.controller.js.map