"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.PAYMENT_METHODS = exports.PAYMENT_STATUSES = exports.ORDER_STATUSES = void 0;
exports.ORDER_STATUSES = [
    "pending",
    "payment_pending",
    "payment_verification",
    "confirmed",
    "processing",
    "packed",
    "shipped",
    "out_for_delivery",
    "delivered",
    "returned",
    "cancelled",
    "refunded",
];
exports.PAYMENT_STATUSES = [
    "pending",
    "payment_pending",
    "payment_verification",
    "verified",
    "success",
    "failed",
    "rejected",
    "refunded",
];
exports.PAYMENT_METHODS = [
    "cod",
    "bkash",
    "nagad",
    "rocket",
    "bank",
    "stripe",
    "sslcommerz",
    "paypal",
];
//# sourceMappingURL=order.interface.js.map