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
exports.getStats = exports.remove = exports.getCustomerInvoice = exports.getInvoice = exports.trackOrder = exports.getMyOrders = exports.addAdminNote = exports.verifyPayment = exports.updateStatus = exports.getById = exports.getAll = exports.create = void 0;
const orderService = __importStar(require("./order.service"));
const jsonwebtoken_1 = __importDefault(require("jsonwebtoken"));
const env_1 = require("../../config/env");
const AppError_1 = require("../../utils/AppError");
const DEFAULT_PAYMENT_METHOD = "cod";
const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 20;
const extractUserId = (req) => {
    const authHeader = req.headers.authorization;
    if (authHeader?.startsWith("Bearer ")) {
        const token = authHeader.split(" ")[1];
        try {
            const decoded = jsonwebtoken_1.default.verify(token, env_1.env.JWT_SECRET);
            return decoded.id;
        }
        catch {
            // Token is invalid or expired, proceed as guest
        }
    }
    return undefined;
};
const str = (v) => (v === undefined || v === null || v === "" ? undefined : String(v));
const create = async (req, res) => {
    const body = req.body;
    const input = {
        userId: extractUserId(req),
        name: body.name,
        phone: body.phone,
        alternativePhone: str(body.alternativePhone),
        email: str(body.email),
        country: str(body.country),
        division: str(body.division),
        district: str(body.district),
        upazila: str(body.upazila),
        area: str(body.area),
        apartment: str(body.apartment),
        postalCode: str(body.postalCode),
        address: body.address,
        shippingArea: body.shippingArea,
        shippingCost: body.shippingCost !== undefined ? Number(body.shippingCost) : undefined,
        shippingMethodId: body.shippingMethodId !== undefined ? Number(body.shippingMethodId) : undefined,
        couponCode: str(body.couponCode),
        orderNote: str(body.orderNote),
        checkoutNotes: str(body.checkoutNotes),
        paymentMethod: body.paymentMethod || DEFAULT_PAYMENT_METHOD,
        transactionId: str(body.transactionId),
        senderNumber: str(body.senderNumber),
        paymentScreenshot: str(body.paymentScreenshot),
        amountSent: body.amountSent !== undefined ? Number(body.amountSent) : undefined,
        paymentInstructions: str(body.paymentInstructions),
        taxAmount: body.taxAmount !== undefined ? Number(body.taxAmount) : undefined,
        items: body.items,
    };
    const result = await orderService.create(input);
    const message = result.auth
        ? "Order placed successfully"
        : "Order placed successfully";
    res.status(201).json({ success: true, data: result, message });
};
exports.create = create;
const getAll = async (req, res) => {
    const result = await orderService.getAll(Number(req.query.page) || DEFAULT_PAGE, Number(req.query.limit) || DEFAULT_LIMIT, req.query.status, req.query.search);
    res.json({ success: true, ...result });
};
exports.getAll = getAll;
const getById = async (req, res) => {
    const order = await orderService.getById(Number(req.params.id));
    if (!order)
        throw new AppError_1.AppError(404, "Order not found");
    res.json({ success: true, data: order });
};
exports.getById = getById;
const updateStatus = async (req, res) => {
    const actorUserId = req.user?.id;
    const order = await orderService.updateStatus(Number(req.params.id), {
        status: req.body.status,
        note: req.body.note,
        trackingNumber: req.body.trackingNumber,
        userId: actorUserId,
    });
    res.json({ success: true, data: order });
};
exports.updateStatus = updateStatus;
const verifyPayment = async (req, res) => {
    const actorUserId = req.user?.id;
    const order = await orderService.verifyPayment(Number(req.params.id), {
        action: req.body.action,
        note: req.body.note,
        userId: actorUserId,
    });
    res.json({ success: true, data: order });
};
exports.verifyPayment = verifyPayment;
const addAdminNote = async (req, res) => {
    const actorUserId = req.user?.id;
    const order = await orderService.updateAdminNotes(Number(req.params.id), req.body.note, actorUserId);
    res.json({ success: true, data: order });
};
exports.addAdminNote = addAdminNote;
const getMyOrders = async (req, res) => {
    const userId = req.user?.id;
    if (!userId)
        throw new AppError_1.AppError(401, "Please sign in to view your orders");
    const data = await orderService.getMyOrders(userId);
    res.json({ success: true, data });
};
exports.getMyOrders = getMyOrders;
const trackOrder = async (req, res) => {
    const { orderId, phone } = req.body;
    const data = await orderService.trackOrder(orderId, phone);
    res.json({ success: true, data });
};
exports.trackOrder = trackOrder;
const getInvoice = async (req, res) => {
    const order = await orderService.getInvoice(Number(req.params.id));
    res.json({ success: true, data: order });
};
exports.getInvoice = getInvoice;
const getCustomerInvoice = async (req, res) => {
    const userId = req.user?.id;
    if (!userId)
        throw new AppError_1.AppError(401, "Please sign in to view your invoice");
    const order = await orderService.getCustomerInvoice(Number(req.params.id), userId);
    res.json({ success: true, data: order });
};
exports.getCustomerInvoice = getCustomerInvoice;
const remove = async (req, res) => {
    await orderService.remove(Number(req.params.id));
    res.json({ success: true, message: "Order deleted" });
};
exports.remove = remove;
const getStats = async (_req, res) => {
    const stats = await orderService.getStats();
    res.json({ success: true, data: stats });
};
exports.getStats = getStats;
//# sourceMappingURL=order.controller.js.map