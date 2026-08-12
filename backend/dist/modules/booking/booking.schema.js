"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.bookingUpdateSchema = exports.bookingCreateSchema = exports.bookingIdSchema = exports.bookingListSchema = void 0;
const zod_1 = require("zod");
exports.bookingListSchema = zod_1.z.object({
    query: zod_1.z.object({
        page: zod_1.z.string().optional(),
        limit: zod_1.z.string().optional(),
        search: zod_1.z.string().optional(),
        status: zod_1.z.enum(["pending", "confirmed", "active", "completed", "cancelled"]).optional(),
        paymentStatus: zod_1.z.enum(["pending", "partial", "paid", "refunded"]).optional(),
    }),
});
exports.bookingIdSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string().refine((v) => /^\d+$/.test(v), { message: "Invalid id" }) }),
});
exports.bookingCreateSchema = zod_1.z.object({
    body: zod_1.z.object({
        customerName: zod_1.z.string().min(1, "Customer name is required"),
        phone: zod_1.z.string().min(1, "Phone is required"),
        email: zod_1.z.string().email().optional().nullable(),
        userId: zod_1.z.number().int().positive().optional().nullable(),
        bookingType: zod_1.z.string().optional(),
        service: zod_1.z.string().optional().nullable(),
        productId: zod_1.z.number().int().positive().optional().nullable(),
        startDate: zod_1.z.string().min(1, "Start date is required"),
        endDate: zod_1.z.string().min(1, "End date is required"),
        quantity: zod_1.z.number().int().positive().optional().default(1),
        price: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        discount: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        additionalCost: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        totalAmount: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        paymentStatus: zod_1.z.enum(["pending", "partial", "paid", "refunded"]).optional(),
        status: zod_1.z.enum(["pending", "confirmed", "active", "completed", "cancelled"]).optional(),
        notes: zod_1.z.string().optional().nullable(),
        attachmentUrl: zod_1.z.string().optional().nullable(),
    }),
});
exports.bookingUpdateSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string().refine((v) => /^\d+$/.test(v), { message: "Invalid id" }) }),
    body: zod_1.z.object({
        customerName: zod_1.z.string().min(1, "Customer name is required").optional(),
        phone: zod_1.z.string().min(1, "Phone is required").optional(),
        email: zod_1.z.string().email().optional().nullable(),
        userId: zod_1.z.number().int().positive().optional().nullable(),
        bookingType: zod_1.z.string().optional(),
        service: zod_1.z.string().optional().nullable(),
        productId: zod_1.z.number().int().positive().optional().nullable(),
        startDate: zod_1.z.string().optional(),
        endDate: zod_1.z.string().optional(),
        quantity: zod_1.z.number().int().positive().optional(),
        price: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        discount: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        additionalCost: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        totalAmount: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        paymentStatus: zod_1.z.enum(["pending", "partial", "paid", "refunded"]).optional(),
        status: zod_1.z.enum(["pending", "confirmed", "active", "completed", "cancelled"]).optional(),
        notes: zod_1.z.string().optional().nullable(),
        attachmentUrl: zod_1.z.string().optional().nullable(),
    }),
});
//# sourceMappingURL=booking.schema.js.map