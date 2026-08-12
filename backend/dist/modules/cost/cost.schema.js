"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.costUpdateSchema = exports.costCreateSchema = exports.costIdSchema = exports.costListSchema = void 0;
const zod_1 = require("zod");
exports.costListSchema = zod_1.z.object({
    query: zod_1.z.object({
        page: zod_1.z.string().optional(),
        limit: zod_1.z.string().optional(),
        search: zod_1.z.string().optional(),
        costType: zod_1.z.string().optional(),
    }),
});
exports.costIdSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string().refine((v) => /^\d+$/.test(v), { message: "Invalid id" }) }),
});
exports.costCreateSchema = zod_1.z.object({
    body: zod_1.z.object({
        title: zod_1.z.string().min(1, "Title is required"),
        costType: zod_1.z.string().optional(),
        quantity: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        unitCost: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        totalCost: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        supplierId: zod_1.z.number().int().positive().nullable().optional(),
        productId: zod_1.z.number().int().positive().nullable().optional(),
        orderId: zod_1.z.number().int().positive().nullable().optional(),
        bookingId: zod_1.z.number().int().positive().nullable().optional(),
        costDate: zod_1.z.string().min(1, "Cost date is required"),
        paymentMethod: zod_1.z.string().optional(),
        notes: zod_1.z.string().nullable().optional(),
        attachmentUrl: zod_1.z.string().nullable().optional(),
    }),
});
exports.costUpdateSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string().refine((v) => /^\d+$/.test(v), { message: "Invalid id" }) }),
    body: zod_1.z.object({
        title: zod_1.z.string().min(1, "Title is required").optional(),
        costType: zod_1.z.string().optional(),
        quantity: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        unitCost: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        totalCost: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        supplierId: zod_1.z.number().int().positive().nullable().optional(),
        productId: zod_1.z.number().int().positive().nullable().optional(),
        orderId: zod_1.z.number().int().positive().nullable().optional(),
        bookingId: zod_1.z.number().int().positive().nullable().optional(),
        costDate: zod_1.z.string().optional(),
        paymentMethod: zod_1.z.string().optional(),
        notes: zod_1.z.string().nullable().optional(),
        attachmentUrl: zod_1.z.string().nullable().optional(),
    }),
});
//# sourceMappingURL=cost.schema.js.map