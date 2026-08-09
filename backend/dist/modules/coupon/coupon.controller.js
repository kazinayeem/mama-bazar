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
exports.remove = exports.update = exports.create = exports.getById = exports.getAll = exports.validateCoupon = void 0;
const couponService = __importStar(require("./coupon.service"));
const AppError_1 = require("../../utils/AppError");
const validateCoupon = async (req, res) => {
    const { code, subtotal } = req.body;
    const result = await couponService.validate(code, Number(subtotal));
    res.json({ success: true, data: result });
};
exports.validateCoupon = validateCoupon;
const getAll = async (req, res) => {
    const data = await couponService.getAll();
    res.json({ success: true, data });
};
exports.getAll = getAll;
const getById = async (req, res) => {
    const data = await couponService.getById(Number(req.params.id));
    if (!data)
        throw new AppError_1.AppError(404, "Coupon not found");
    res.json({ success: true, data });
};
exports.getById = getById;
const create = async (req, res) => {
    const { code, discountType, discountValue, minOrderAmount, expiryDate, status } = req.body;
    const data = await couponService.create({
        code: code.toUpperCase(),
        discountType,
        discountValue: String(discountValue),
        minOrderAmount: minOrderAmount ? String(minOrderAmount) : undefined,
        expiryDate,
        status,
    });
    res.status(201).json({ success: true, data });
};
exports.create = create;
const update = async (req, res) => {
    const data = await couponService.update(Number(req.params.id), req.body);
    res.json({ success: true, data });
};
exports.update = update;
const remove = async (req, res) => {
    await couponService.remove(Number(req.params.id));
    res.json({ success: true, message: "Coupon deleted" });
};
exports.remove = remove;
//# sourceMappingURL=coupon.controller.js.map