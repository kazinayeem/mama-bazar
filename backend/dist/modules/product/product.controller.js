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
exports.saveDraft = exports.importCsv = exports.exportCsv = exports.duplicate = exports.bulk = exports.remove = exports.update = exports.create = exports.getRelated = exports.getBySlug = exports.getById = exports.getAll = void 0;
const productService = __importStar(require("./product.service"));
const slug_util_1 = require("./slug.util");
const AppError_1 = require("../../utils/AppError");
const cloud_1 = require("../../utils/cloud");
const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 12;
const DEFAULT_DISCOUNT = "0";
const DEFAULT_STOCK = 0;
const DEFAULT_STATUS = "active";
const DEFAULT_PAYMENT_METHODS = ["cod"];
const parseJsonField = (value, field = "field") => {
    if (value === undefined || value === null || value === "")
        return undefined;
    if (typeof value === "string") {
        try {
            return JSON.parse(value);
        }
        catch {
            throw new AppError_1.AppError(400, `Invalid JSON in "${field}". Please check the value and try again.`);
        }
    }
    return value;
};
const parseArrayField = (value) => {
    const parsed = parseJsonField(value);
    return Array.isArray(parsed) ? parsed : [];
};
const toBool = (value) => {
    if (value === undefined || value === null)
        return undefined;
    return value === true || value === "true" || value === 1 || value === "1";
};
const toStr = (value) => value === undefined || value === null || value === "" ? undefined : String(value);
const toNum = (value) => {
    if (value === undefined || value === null || value === "")
        return undefined;
    const n = Number(value);
    return Number.isNaN(n) ? undefined : n;
};
const assertCloudinary = (files) => {
    if (files && files.length > 0 && !cloud_1.cloudinaryConfigured) {
        throw new AppError_1.AppError(400, "Cloudinary is not configured. Set CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY and CLOUDINARY_API_SECRET in backend/.env to upload images.");
    }
};
const persistUploadedImages = async (files) => {
    if (!files || files.length === 0)
        return [];
    const urls = [];
    for (const f of files) {
        const uploaded = await (0, cloud_1.uploadBuffer)(f.buffer, {
            folder: "products",
            filename: f.originalname,
            mimeType: f.mimetype,
        });
        urls.push(uploaded.url);
    }
    return urls;
};
const extractBaseProduct = (body) => {
    const labels = (key) => (body[key] !== undefined ? toBool(body[key]) : undefined);
    return {
        title: body.title,
        description: body.description,
        shortDescription: body.shortDescription,
        price: body.price !== undefined ? String(body.price) : undefined,
        salePrice: toStr(body.salePrice),
        discount: body.discount !== undefined && body.discount !== "" ? String(body.discount) : undefined,
        costPrice: toStr(body.costPrice),
        tax: toStr(body.tax),
        vat: toStr(body.vat),
        shippingCharge: toStr(body.shippingCharge),
        codFee: toStr(body.codFee),
        flashSalePrice: toStr(body.flashSalePrice),
        wholesalePrice: toStr(body.wholesalePrice),
        dealerPrice: toStr(body.dealerPrice),
        categoryId: body.categoryId !== undefined && body.categoryId !== "" ? Number(body.categoryId) : undefined,
        subCategoryId: body.subCategoryId !== undefined && body.subCategoryId !== "" ? Number(body.subCategoryId) : undefined,
        childCategoryId: body.childCategoryId !== undefined && body.childCategoryId !== "" ? Number(body.childCategoryId) : undefined,
        collectionId: body.collectionId !== undefined && body.collectionId !== "" ? Number(body.collectionId) : undefined,
        brandId: body.brandId !== undefined && body.brandId !== "" ? Number(body.brandId) : undefined,
        brand: toStr(body.brand),
        vendorId: body.vendorId !== undefined && body.vendorId !== "" ? Number(body.vendorId) : undefined,
        supplierId: body.supplierId !== undefined && body.supplierId !== "" ? Number(body.supplierId) : undefined,
        supplier: toStr(body.supplier),
        countryOfOrigin: toStr(body.countryOfOrigin),
        sku: toStr(body.sku),
        barcode: toStr(body.barcode),
        warranty: toStr(body.warranty),
        weight: toStr(body.weight),
        dimensions: toStr(body.dimensions),
        returnPolicy: toStr(body.returnPolicy),
        warehouse: toStr(body.warehouse),
        videoUrl: toStr(body.videoUrl),
        seoTitle: toStr(body.seoTitle),
        seoDescription: toStr(body.seoDescription),
        seoKeywords: toStr(body.seoKeywords),
        canonicalUrl: toStr(body.canonicalUrl),
        ogImage: toStr(body.ogImage),
        twitterImage: toStr(body.twitterImage),
        structuredData: parseJsonField(body.structuredData, "structuredData"),
        emiAvailable: labels("emiAvailable"),
        isFeatured: labels("isFeatured"),
        isTrending: labels("isTrending"),
        isFlashSale: labels("isFlashSale"),
        isNewArrival: labels("isNewArrival"),
        isBestSeller: labels("isBestSeller"),
        isLimitedEdition: labels("isLimitedEdition"),
        isOfficial: labels("isOfficial"),
        isHotDeal: labels("isHotDeal"),
        isArchived: labels("isArchived"),
        lowStockAlert: toNum(body.lowStockAlert),
        minOrder: toNum(body.minOrder),
        maxOrder: toNum(body.maxOrder),
        unlimitedStock: labels("unlimitedStock"),
        backorder: labels("backorder"),
        trackInventory: labels("trackInventory"),
        stockStatus: toStr(body.stockStatus),
        productStatus: body.productStatus || undefined,
        stock: toNum(body.stock),
        tags: parseArrayField(body.tags),
        features: parseArrayField(body.features),
        sizeOptions: parseArrayField(body.sizeOptions),
        colorOptions: parseArrayField(body.colorOptions),
        paymentMethods: body.paymentMethods ? parseArrayField(body.paymentMethods) : DEFAULT_PAYMENT_METHODS,
        paymentPhoneNumber: toStr(body.paymentPhoneNumber),
        variants: parseArrayField(body.variants),
        specs: parseArrayField(body.specs),
        relations: parseArrayField(body.relations).map((r) => ({
            relatedProductId: Number(r.relatedProductId),
            type: r.type,
        })),
    };
};
const getAll = async (req, res) => {
    const result = await productService.getAll({
        page: Number(req.query.page) || DEFAULT_PAGE,
        limit: Number(req.query.limit) || DEFAULT_LIMIT,
        category: req.query.category,
        search: req.query.search,
        brand: req.query.brand,
        supplier: req.query.supplier,
        vendor: req.query.vendor,
        collection: req.query.collection,
        stock: req.query.stock,
        minPrice: req.query.minPrice ? Number(req.query.minPrice) : undefined,
        maxPrice: req.query.maxPrice ? Number(req.query.maxPrice) : undefined,
        dateFrom: req.query.dateFrom,
        dateTo: req.query.dateTo,
        sort: req.query.sort,
        status: req.query.status,
        productStatus: req.query.productStatus,
        label: req.query.label,
        tags: req.query.tags,
        sku: req.query.sku,
        barcode: req.query.barcode,
        inStock: req.query.inStock === "true",
    });
    res.json({ success: true, ...result });
};
exports.getAll = getAll;
const getById = async (req, res) => {
    const data = await productService.getById(Number(req.params.id));
    if (!data)
        throw new AppError_1.AppError(404, "Product not found");
    res.json({ success: true, data });
};
exports.getById = getById;
const getBySlug = async (req, res) => {
    const data = await productService.getBySlug(req.params.slug);
    if (!data)
        throw new AppError_1.AppError(404, "Product not found");
    res.json({ success: true, data });
};
exports.getBySlug = getBySlug;
const getRelated = async (req, res) => {
    const product = await productService.getById(Number(req.params.id));
    if (!product)
        throw new AppError_1.AppError(404, "Product not found");
    const data = await productService.getRelated(product.categoryId || 0, product.id);
    res.json({ success: true, data });
};
exports.getRelated = getRelated;
const create = async (req, res) => {
    const body = req.body || {};
    const base = extractBaseProduct(body);
    const files = req.files;
    assertCloudinary(files);
    const uploadedImages = await persistUploadedImages(files);
    const bodyImages = Array.isArray(body.images) ? body.images : parseArrayField(body.images);
    const images = [...(bodyImages || []), ...uploadedImages];
    // Slug: use the user-provided value when given (zod already enforced ASCII),
    // otherwise generate one from the title with Bangla → English transliteration.
    const requestedSlug = typeof body.slug === "string" && body.slug.trim() ? body.slug.trim().toLowerCase() : null;
    const generatedSlug = (0, slug_util_1.toAsciiSlug)(base.title) || "product";
    const slug = await productService.ensureUniqueSlug(requestedSlug ?? generatedSlug, {
        autoSuffix: !requestedSlug,
    });
    const data = await productService.create({
        ...base,
        title: base.title,
        slug,
        price: base.price || "0",
        discount: base.discount || DEFAULT_DISCOUNT,
        stock: base.stock ?? (base.variants?.length ? 0 : DEFAULT_STOCK),
        images,
        paymentMethods: (base.paymentMethods || DEFAULT_PAYMENT_METHODS),
    });
    res.status(201).json({ success: true, data });
};
exports.create = create;
const update = async (req, res) => {
    const id = Number(req.params.id);
    const body = req.body || {};
    const base = extractBaseProduct(body);
    const files = req.files;
    assertCloudinary(files);
    const uploadedImages = await persistUploadedImages(files);
    const updateData = { ...base };
    // Slug: honor an explicitly provided slug (zod already enforced ASCII and
    // uniqueness excludes this product). When the slug is absent but the title
    // changed, regenerate from the title like before.
    if (body.slug !== undefined) {
        const requestedSlug = typeof body.slug === "string" && body.slug.trim() ? body.slug.trim().toLowerCase() : null;
        const generatedSlug = (base.title ? (0, slug_util_1.toAsciiSlug)(base.title) : "") || "product";
        updateData.slug = await productService.ensureUniqueSlug(requestedSlug ?? generatedSlug, {
            excludeId: id,
            autoSuffix: !requestedSlug,
        });
    }
    else if (base.title) {
        updateData.slug = await productService.ensureUniqueSlug((0, slug_util_1.toAsciiSlug)(base.title) || "product", {
            excludeId: id,
            autoSuffix: true,
        });
    }
    if (body.images !== undefined || uploadedImages.length > 0) {
        const bodyImages = Array.isArray(body.images) ? body.images : parseArrayField(body.images);
        const existing = parseArrayField(body.existingImages);
        const combined = existing.length ? [...existing, ...(bodyImages || [])] : bodyImages || [];
        updateData.images = [...combined, ...uploadedImages];
    }
    const data = await productService.update(id, updateData);
    res.json({ success: true, data });
};
exports.update = update;
const remove = async (req, res) => {
    await productService.remove(Number(req.params.id));
    res.json({ success: true, message: "Product deleted" });
};
exports.remove = remove;
const bulk = async (req, res) => {
    const { ids, action } = req.body;
    const result = await productService.bulkAction(ids, action);
    res.json({ success: true, message: `Bulk ${action} complete`, ...result });
};
exports.bulk = bulk;
const duplicate = async (req, res) => {
    const data = await productService.duplicate(Number(req.params.id));
    res.status(201).json({ success: true, data });
};
exports.duplicate = duplicate;
const exportCsv = async (req, res) => {
    const csv = await productService.exportCsv({
        category: req.query.category,
        search: req.query.search,
        brand: req.query.brand,
        status: req.query.status || undefined,
        productStatus: req.query.productStatus,
        label: req.query.label,
    });
    res.json({ success: true, csv });
};
exports.exportCsv = exportCsv;
const importCsv = async (req, res) => {
    const { csv } = req.body || {};
    if (!csv || typeof csv !== "string")
        throw new AppError_1.AppError(400, "CSV payload is required");
    const result = await productService.importCsv(csv);
    res.json({ success: true, ...result });
};
exports.importCsv = importCsv;
const saveDraft = async (req, res) => {
    const id = Number(req.params.id);
    await productService.autoSaveDraft(id, req.body.draft || {});
    res.json({ success: true, message: "Draft saved" });
};
exports.saveDraft = saveDraft;
//# sourceMappingURL=product.controller.js.map