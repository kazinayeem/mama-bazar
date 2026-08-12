"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.expenseCategoryIdSchema = exports.expenseCategoryUpdateSchema = exports.expenseCategoryCreateSchema = exports.expenseExportSchema = exports.expenseProfitSchema = exports.expenseRangeReportSchema = exports.expenseTrendSchema = exports.expenseMonthlyReportSchema = exports.expenseByCategorySchema = exports.expenseMemberSchema = exports.expenseSummarySchema = exports.expenseUpdateSchema = exports.expenseCreateSchema = exports.expenseIdSchema = exports.expenseListSchema = void 0;
const zod_1 = require("zod");
const idParam = zod_1.z.object({ id: zod_1.z.string().refine((v) => /^\d+$/.test(v), { message: "Invalid id" }) });
const dateString = zod_1.z.string().refine((v) => /^\d{4}-\d{2}-\d{2}$/.test(v), {
    message: "Date must be in YYYY-MM-DD format",
});
const dateTimeString = zod_1.z.string().refine((v) => /^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}(:\d{2})?)?$/.test(v), {
    message: "Date must be in YYYY-MM-DD or YYYY-MM-DD HH:mm:ss format",
});
const positiveAmount = zod_1.z
    .union([zod_1.z.string(), zod_1.z.number()])
    .refine((v) => Number(v) > 0, { message: "Amount must be greater than 0" });
exports.expenseListSchema = zod_1.z.object({
    query: zod_1.z.object({
        page: zod_1.z.string().optional(),
        limit: zod_1.z.string().optional(),
        search: zod_1.z.string().optional(),
        status: zod_1.z.enum(["pending", "approved", "rejected"]).optional(),
        memberId: zod_1.z.string().optional(),
        categoryId: zod_1.z.string().optional(),
        paymentMethod: zod_1.z.string().optional(),
        dateFrom: dateString.optional(),
        dateTo: dateString.optional(),
        amountMin: zod_1.z.string().optional(),
        amountMax: zod_1.z.string().optional(),
    }),
});
exports.expenseIdSchema = zod_1.z.object({ params: idParam });
exports.expenseCreateSchema = zod_1.z.object({
    body: zod_1.z.object({
        title: zod_1.z.string().min(1, "Title is required"),
        description: zod_1.z.string().nullable().optional(),
        categoryId: zod_1.z.number().int().positive().nullable().optional(),
        amount: positiveAmount,
        paymentMethod: zod_1.z.string().optional(),
        vendor: zod_1.z.string().nullable().optional(),
        memberId: zod_1.z.number().int().positive().nullable().optional(),
        expenseDate: dateTimeString,
        referenceNumber: zod_1.z.string().nullable().optional(),
        attachmentUrl: zod_1.z.string().nullable().optional(),
        notes: zod_1.z.string().nullable().optional(),
        status: zod_1.z.enum(["pending", "approved", "rejected"]).optional(),
    }),
});
exports.expenseUpdateSchema = zod_1.z.object({
    params: idParam,
    body: zod_1.z.object({
        title: zod_1.z.string().min(1, "Title is required").optional(),
        description: zod_1.z.string().nullable().optional(),
        categoryId: zod_1.z.number().int().positive().nullable().optional(),
        amount: positiveAmount.optional(),
        paymentMethod: zod_1.z.string().optional(),
        vendor: zod_1.z.string().nullable().optional(),
        memberId: zod_1.z.number().int().positive().nullable().optional(),
        expenseDate: dateTimeString.optional(),
        referenceNumber: zod_1.z.string().nullable().optional(),
        attachmentUrl: zod_1.z.string().nullable().optional(),
        notes: zod_1.z.string().nullable().optional(),
        status: zod_1.z.enum(["pending", "approved", "rejected"]).optional(),
    }),
});
exports.expenseSummarySchema = zod_1.z.object({
    query: zod_1.z.object({
        dateFrom: dateString.optional(),
        dateTo: dateString.optional(),
    }),
});
exports.expenseMemberSchema = zod_1.z.object({
    query: zod_1.z.object({
        memberId: zod_1.z.string().optional(),
        dateFrom: dateString.optional(),
        dateTo: dateString.optional(),
    }),
});
exports.expenseByCategorySchema = zod_1.z.object({
    query: zod_1.z.object({
        dateFrom: dateString.optional(),
        dateTo: dateString.optional(),
    }),
});
exports.expenseMonthlyReportSchema = zod_1.z.object({
    query: zod_1.z.object({
        year: zod_1.z.string().optional(),
        month: zod_1.z.string().optional(),
        memberId: zod_1.z.string().optional(),
        categoryId: zod_1.z.string().optional(),
        page: zod_1.z.string().optional(),
        limit: zod_1.z.string().optional(),
    }),
});
exports.expenseTrendSchema = zod_1.z.object({
    query: zod_1.z.object({
        year: zod_1.z.string().optional(),
    }),
});
exports.expenseRangeReportSchema = zod_1.z.object({
    query: zod_1.z.object({
        dateFrom: dateString.optional(),
        dateTo: dateString.optional(),
        memberId: zod_1.z.string().optional(),
        categoryId: zod_1.z.string().optional(),
        status: zod_1.z.enum(["pending", "approved", "rejected"]).optional(),
    }),
});
exports.expenseProfitSchema = zod_1.z.object({
    query: zod_1.z.object({
        year: zod_1.z.string().optional(),
        month: zod_1.z.string().optional(),
    }),
});
exports.expenseExportSchema = zod_1.z.object({
    query: exports.expenseListSchema.shape.query,
});
exports.expenseCategoryCreateSchema = zod_1.z.object({
    body: zod_1.z.object({
        name: zod_1.z.string().min(1, "Name is required"),
        description: zod_1.z.string().nullable().optional(),
        sortOrder: zod_1.z.number().int().optional(),
        status: zod_1.z.enum(["active", "inactive"]).optional(),
    }),
});
exports.expenseCategoryUpdateSchema = zod_1.z.object({
    params: idParam,
    body: zod_1.z.object({
        name: zod_1.z.string().min(1, "Name is required").optional(),
        description: zod_1.z.string().nullable().optional(),
        sortOrder: zod_1.z.number().int().optional(),
        status: zod_1.z.enum(["active", "inactive"]).optional(),
    }),
});
exports.expenseCategoryIdSchema = zod_1.z.object({ params: idParam });
//# sourceMappingURL=expense.schema.js.map