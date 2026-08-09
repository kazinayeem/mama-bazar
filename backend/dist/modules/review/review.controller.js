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
exports.getById = exports.remove = exports.updateStatus = exports.create = exports.getAllAdmin = exports.getAll = void 0;
const reviewService = __importStar(require("./review.service"));
const AppError_1 = require("../../utils/AppError");
const getAll = async (req, res) => {
    const { page, limit, productId, search } = req.query;
    const result = await reviewService.getAll({
        page: page ? Number(page) : undefined,
        limit: limit ? Number(limit) : undefined,
        productId: productId ? Number(productId) : undefined,
        search: search ? String(search) : undefined,
        status: "approved",
    });
    res.json({ success: true, data: result.data, pagination: result.pagination });
};
exports.getAll = getAll;
const getAllAdmin = async (req, res) => {
    const { page, limit, status, search } = req.query;
    const result = await reviewService.getAll({
        page: page ? Number(page) : undefined,
        limit: limit ? Number(limit) : undefined,
        status: status || undefined,
        search: search ? String(search) : undefined,
    });
    res.json({ success: true, data: result.data, pagination: result.pagination });
};
exports.getAllAdmin = getAllAdmin;
const create = async (req, res) => {
    const { productId, rating, title, comment } = req.body;
    const user = req.user;
    const data = await reviewService.create({
        productId: Number(productId),
        userId: user?.id ?? null,
        customerName: user?.name ?? req.body.customerName,
        rating: Number(rating),
        title,
        comment,
    });
    res.status(201).json({ success: true, data, message: "Review submitted and pending approval" });
};
exports.create = create;
const updateStatus = async (req, res) => {
    const { status } = req.body;
    const data = await reviewService.updateStatus(Number(req.params.id), status);
    res.json({ success: true, data });
};
exports.updateStatus = updateStatus;
const remove = async (req, res) => {
    await reviewService.remove(Number(req.params.id));
    res.json({ success: true, message: "Review deleted" });
};
exports.remove = remove;
const getById = async (req, res) => {
    const data = await reviewService.getById(Number(req.params.id));
    if (!data)
        throw new AppError_1.AppError(404, "Review not found");
    res.json({ success: true, data });
};
exports.getById = getById;
//# sourceMappingURL=review.controller.js.map