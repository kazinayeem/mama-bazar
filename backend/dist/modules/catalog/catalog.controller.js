"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.supplierController = exports.vendorController = exports.collectionController = exports.sizeController = exports.colorController = void 0;
const catalog_service_1 = require("./catalog.service");
const AppError_1 = require("../../utils/AppError");
const cloud_1 = require("../../utils/cloud");
const slugify_1 = __importDefault(require("slugify"));
const IMAGE_FIELDS = {
    collections: "image",
    vendors: "logo",
    suppliers: "logo",
};
const IMAGE_FOLDERS = {
    collections: "collections",
    vendors: "vendors",
    suppliers: "suppliers",
};
const makeSlug = (value) => (0, slugify_1.default)(value, { lower: true, strict: true });
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
const persistUploadedFile = async (file, folder) => {
    if (!file)
        return undefined;
    const uploaded = await (0, cloud_1.uploadBuffer)(file.buffer, {
        folder,
        filename: file.originalname,
        mimeType: file.mimetype,
    });
    return uploaded.url;
};
const makeController = (name) => {
    const list = async (_req, res) => {
        const data = await catalog_service_1.catalogService.list(name);
        res.json({ success: true, data });
    };
    const listAdmin = async (req, res) => {
        const q = req.query;
        const result = await catalog_service_1.catalogService.listAdmin(name, {
            page: q.page ? Number(q.page) : 1,
            limit: q.limit ? Number(q.limit) : 20,
            search: q.search,
            status: q.status,
            sort: q.sort,
        });
        res.json({ success: true, ...result });
    };
    const getById = async (req, res) => {
        const data = await catalog_service_1.catalogService.getById(name, Number(req.params.id));
        if (!data)
            throw new AppError_1.AppError(404, "Not found");
        res.json({ success: true, data });
    };
    const getUsage = async (req, res) => {
        const id = Number(req.params.id);
        const existing = await catalog_service_1.catalogService.getById(name, id);
        if (!existing)
            throw new AppError_1.AppError(404, "Not found");
        const products = await catalog_service_1.catalogService.getUsage(name, id, existing.name);
        res.json({ success: true, data: { products } });
    };
    const create = async (req, res) => {
        const body = req.body || {};
        const data = { ...body };
        if (body.name) {
            data.slug = body.slug || makeSlug(String(body.name));
        }
        // coerce non-string fields
        if ("featured" in data)
            data.featured = toBool(data.featured);
        if ("homepageVisibility" in data)
            data.homepageVisibility = toBool(data.homepageVisibility);
        if ("sortOrder" in data)
            data.sortOrder = toNumber(data.sortOrder) ?? 0;
        if ("status" in data && !data.status)
            delete data.status;
        if (data.startDate === "" || data.startDate === null)
            data.startDate = null;
        if (data.endDate === "" || data.endDate === null)
            data.endDate = null;
        const imageField = IMAGE_FIELDS[name];
        if (imageField) {
            const uploaded = await persistUploadedFile(req.file, IMAGE_FOLDERS[name] || name);
            if (uploaded)
                data[imageField] = uploaded;
        }
        const created = await catalog_service_1.catalogService.create(name, data);
        res.status(201).json({ success: true, data: created });
    };
    const update = async (req, res) => {
        const id = Number(req.params.id);
        const body = req.body || {};
        const existing = await catalog_service_1.catalogService.getById(name, id);
        if (!existing)
            throw new AppError_1.AppError(404, "Not found");
        const data = { ...body };
        if ("featured" in data)
            data.featured = toBool(data.featured);
        if ("homepageVisibility" in data)
            data.homepageVisibility = toBool(data.homepageVisibility);
        if ("sortOrder" in data)
            data.sortOrder = toNumber(data.sortOrder) ?? 0;
        if ("status" in data && !data.status)
            delete data.status;
        if ("startDate" in data)
            data.startDate = data.startDate ? String(data.startDate) : null;
        if ("endDate" in data)
            data.endDate = data.endDate ? String(data.endDate) : null;
        if (data.slug === "")
            delete data.slug;
        if (data.name && !body.slug) {
            data.slug = makeSlug(String(data.name));
        }
        const imageField = IMAGE_FIELDS[name];
        if (imageField) {
            const uploaded = await persistUploadedFile(req.file, IMAGE_FOLDERS[name] || name);
            if (uploaded)
                data[imageField] = uploaded;
        }
        const updated = await catalog_service_1.catalogService.update(name, id, data);
        res.json({ success: true, data: updated });
    };
    const remove = async (req, res) => {
        const id = Number(req.params.id);
        const existing = await catalog_service_1.catalogService.getById(name, id);
        if (!existing)
            throw new AppError_1.AppError(404, "Not found");
        const usage = await catalog_service_1.catalogService.getUsage(name, id, existing.name);
        if (usage > 0) {
            throw new AppError_1.AppError(409, `This ${name === "colors" || name === "sizes" ? name.slice(0, -1) : name.slice(0, -1)} is currently used by ${usage} product${usage > 1 ? "s" : ""}.`, { usageCount: usage, code: "in_use" });
        }
        await catalog_service_1.catalogService.remove(name, id);
        res.json({ success: true, message: "Deleted" });
    };
    const moveProducts = async (req, res) => {
        const id = Number(req.params.id);
        const existing = await catalog_service_1.catalogService.getById(name, id);
        if (!existing)
            throw new AppError_1.AppError(404, "Not found");
        const targetId = toIdOrNull(req.body.targetId) ?? null;
        const result = await catalog_service_1.catalogService.move(name, id, targetId, existing.name);
        res.json({ success: true, data: result });
    };
    return { list, listAdmin, getById, getUsage, create, update, remove, moveProducts };
};
exports.colorController = makeController("colors");
exports.sizeController = makeController("sizes");
exports.collectionController = makeController("collections");
exports.vendorController = makeController("vendors");
exports.supplierController = makeController("suppliers");
//# sourceMappingURL=catalog.controller.js.map