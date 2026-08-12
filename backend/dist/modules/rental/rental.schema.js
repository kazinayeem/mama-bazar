"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.rentalUpdateSchema = exports.rentalCreateSchema = exports.rentalIdSchema = exports.rentalListSchema = void 0;
const zod_1 = require("zod");
exports.rentalListSchema = zod_1.z.object({
    query: zod_1.z.object({
        page: zod_1.z.string().optional(),
        limit: zod_1.z.string().optional(),
        search: zod_1.z.string().optional(),
        status: zod_1.z.enum(["reserved", "rented", "returned", "overdue", "cancelled"]).optional(),
        paymentStatus: zod_1.z.enum(["pending", "partial", "paid", "refunded"]).optional(),
    }),
});
exports.rentalIdSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string().refine((v) => /^\d+$/.test(v), { message: "Invalid id" }) }),
});
exports.rentalCreateSchema = zod_1.z.object({
    body: zod_1.z.object({
        rentalItem: zod_1.z.string().min(1, "Rental item is required"),
        productId: zod_1.z.number().int().positive().optional().nullable(),
        customerName: zod_1.z.string().min(1, "Customer name is required"),
        phone: zod_1.z.string().min(1, "Phone is required"),
        email: zod_1.z.string().email().optional().nullable(),
        userId: zod_1.z.number().int().positive().optional().nullable(),
        quantity: zod_1.z.number().int().positive().optional().default(1),
        startDate: zod_1.z.string().min(1, "Start date is required"),
        endDate: zod_1.z.string().min(1, "End date is required"),
        returnDate: zod_1.z.string().optional().nullable(),
        rateType: zod_1.z.enum(["daily", "weekly", "monthly"]).optional(),
        dailyRate: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        weeklyRate: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        monthlyRate: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        rate: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        durationUnits: zod_1.z.number().int().optional().default(0),
        securityDeposit: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        discount: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        additionalCharge: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        totalAmount: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        paymentStatus: zod_1.z.enum(["pending", "partial", "paid", "refunded"]).optional(),
        status: zod_1.z.enum(["reserved", "rented", "returned", "overdue", "cancelled"]).optional(),
        notes: zod_1.z.string().optional().nullable(),
        attachmentUrl: zod_1.z.string().optional().nullable(),
        createdById: zod_1.z.number().int().positive().optional().nullable(),
    }),
});
exports.rentalUpdateSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string().refine((v) => /^\d+$/.test(v), { message: "Invalid id" }) }),
    body: zod_1.z.object({
        rentalItem: zod_1.z.string().min(1, "Rental item is required").optional(),
        productId: zod_1.z.number().int().positive().optional().nullable(),
        customerName: zod_1.z.string().min(1, "Customer name is required").optional(),
        phone: zod_1.z.string().optional(),
        email: zod_1.z.string().email().optional().nullable(),
        userId: zod_1.z.number().int().positive().optional().nullable(),
        quantity: zod_1.z.number().int().positive().optional(),
        startDate: zod_1.z.string().optional(),
        endDate: zod_1.z.string().optional(),
        returnDate: zod_1.z.string().optional().nullable(),
        rateType: zod_1.z.enum(["daily", "weekly", "monthly"]).optional(),
        dailyRate: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        weeklyRate: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        monthlyRate: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        rate: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        durationUnits: zod_1.z.number().int().optional(),
        securityDeposit: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        discount: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        additionalCharge: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        totalAmount: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        paymentStatus: zod_1.z.enum(["pending", "partial", "paid", "refunded"]).optional(),
        status: zod_1.z.enum(["reserved", "rented", "returned", "overdue", "cancelled"]).optional(),
        notes: zod_1.z.string().optional().nullable(),
        attachmentUrl: zod_1.z.string().optional().nullable(),
        createdById: zod_1.z.number().int().positive().optional().nullable(),
    }),
});
//# sourceMappingURL=rental.schema.js.map