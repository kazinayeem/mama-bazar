"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.trackOrderSchema = exports.orderListSchema = exports.orderIdSchema = exports.adminNoteSchema = exports.verifyPaymentSchema = exports.updateOrderStatusSchema = exports.createOrderSchema = void 0;
const zod_1 = require("zod");
const order_interface_1 = require("./order.interface");
const BD_PHONE_REGEX = /^(\+880|0)[1-9]\d{9}$/;
const optStr = zod_1.z.string().optional().nullable();
const orderItemSchema = zod_1.z.object({
    productId: zod_1.z.number().int().positive(),
    variantId: zod_1.z.number().int().positive().optional().nullable(),
    quantity: zod_1.z.number().int().positive(),
    size: zod_1.z.string().optional().nullable(),
    color: zod_1.z.string().optional().nullable(),
});
exports.createOrderSchema = zod_1.z.object({
    body: zod_1.z.object({
        name: zod_1.z.string().min(1, "Name is required"),
        phone: zod_1.z.string().regex(BD_PHONE_REGEX, "Invalid phone number format"),
        alternativePhone: zod_1.z.string().regex(BD_PHONE_REGEX, "Invalid alternative phone format").optional().nullable(),
        email: zod_1.z.string().email("Invalid email format").optional().nullable(),
        country: optStr,
        division: optStr,
        district: optStr,
        upazila: optStr,
        area: optStr,
        apartment: optStr,
        postalCode: optStr,
        address: zod_1.z.string().min(1, "Address is required"),
        shippingArea: zod_1.z.string().min(1, "Shipping area is required"),
        shippingCost: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional().nullable(),
        shippingMethodId: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional().nullable(),
        couponCode: optStr,
        orderNote: zod_1.z.string().max(1000).optional().nullable(),
        checkoutNotes: optStr,
        paymentMethod: zod_1.z.enum(order_interface_1.PAYMENT_METHODS).optional(),
        transactionId: optStr,
        senderNumber: zod_1.z.string().max(30).optional().nullable(),
        paymentScreenshot: zod_1.z.string().url("Invalid screenshot URL").optional().nullable(),
        amountSent: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional().nullable(),
        paymentInstructions: optStr,
        taxAmount: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional().nullable(),
        items: zod_1.z.array(orderItemSchema).min(1, "At least one item is required"),
    }),
});
exports.updateOrderStatusSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
    body: zod_1.z.object({
        status: zod_1.z.enum(order_interface_1.ORDER_STATUSES),
        note: zod_1.z.string().max(500).optional(),
        trackingNumber: zod_1.z.string().max(120).optional(),
    }),
});
exports.verifyPaymentSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
    body: zod_1.z.object({
        action: zod_1.z.enum(["verified", "rejected"]),
        note: zod_1.z.string().max(500).optional(),
    }),
});
exports.adminNoteSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
    body: zod_1.z.object({
        note: zod_1.z.string().min(1, "Note is required").max(1000),
    }),
});
exports.orderIdSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
});
exports.orderListSchema = zod_1.z.object({
    query: zod_1.z.object({
        page: zod_1.z.string().optional(),
        limit: zod_1.z.string().optional(),
        status: zod_1.z.string().optional(),
        search: zod_1.z.string().optional(),
    }),
});
exports.trackOrderSchema = zod_1.z.object({
    body: zod_1.z.object({
        orderId: zod_1.z.string().min(1, "Order ID is required"),
        phone: zod_1.z.string().regex(BD_PHONE_REGEX, "Invalid phone number format"),
    }),
});
//# sourceMappingURL=order.schema.js.map