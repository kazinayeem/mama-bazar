import { z } from "zod";

const idParam = z.object({ id: z.string().refine((v) => /^\d+$/.test(v), { message: "Invalid id" }) });

const dateString = z.string().refine((v) => /^\d{4}-\d{2}-\d{2}$/.test(v), {
  message: "Date must be in YYYY-MM-DD format",
});

const dateTimeString = z.string().refine((v) => /^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}(:\d{2})?)?$/.test(v), {
  message: "Date must be in YYYY-MM-DD or YYYY-MM-DD HH:mm:ss format",
});

const positiveAmount = z
  .union([z.string(), z.number()])
  .refine((v) => Number(v) > 0, { message: "Amount must be greater than 0" });

export const expenseListSchema = z.object({
  query: z.object({
    page: z.string().optional(),
    limit: z.string().optional(),
    search: z.string().optional(),
    status: z.enum(["pending", "approved", "rejected"]).optional(),
    memberId: z.string().optional(),
    categoryId: z.string().optional(),
    paymentMethod: z.string().optional(),
    dateFrom: dateString.optional(),
    dateTo: dateString.optional(),
    amountMin: z.string().optional(),
    amountMax: z.string().optional(),
  }),
});

export const expenseIdSchema = z.object({ params: idParam });

export const expenseCreateSchema = z.object({
  body: z.object({
    title: z.string().min(1, "Title is required"),
    description: z.string().nullable().optional(),
    categoryId: z.number().int().positive().nullable().optional(),
    amount: positiveAmount,
    paymentMethod: z.string().optional(),
    vendor: z.string().nullable().optional(),
    memberId: z.number().int().positive().nullable().optional(),
    expenseDate: dateTimeString,
    referenceNumber: z.string().nullable().optional(),
    attachmentUrl: z.string().nullable().optional(),
    notes: z.string().nullable().optional(),
    status: z.enum(["pending", "approved", "rejected"]).optional(),
  }),
});

export const expenseUpdateSchema = z.object({
  params: idParam,
  body: z.object({
    title: z.string().min(1, "Title is required").optional(),
    description: z.string().nullable().optional(),
    categoryId: z.number().int().positive().nullable().optional(),
    amount: positiveAmount.optional(),
    paymentMethod: z.string().optional(),
    vendor: z.string().nullable().optional(),
    memberId: z.number().int().positive().nullable().optional(),
    expenseDate: dateTimeString.optional(),
    referenceNumber: z.string().nullable().optional(),
    attachmentUrl: z.string().nullable().optional(),
    notes: z.string().nullable().optional(),
    status: z.enum(["pending", "approved", "rejected"]).optional(),
  }),
});

export const expenseSummarySchema = z.object({
  query: z.object({
    dateFrom: dateString.optional(),
    dateTo: dateString.optional(),
  }),
});

export const expenseMemberSchema = z.object({
  query: z.object({
    memberId: z.string().optional(),
    dateFrom: dateString.optional(),
    dateTo: dateString.optional(),
  }),
});

export const expenseByCategorySchema = z.object({
  query: z.object({
    dateFrom: dateString.optional(),
    dateTo: dateString.optional(),
  }),
});

export const expenseMonthlyReportSchema = z.object({
  query: z.object({
    year: z.string().optional(),
    month: z.string().optional(),
    memberId: z.string().optional(),
    categoryId: z.string().optional(),
    page: z.string().optional(),
    limit: z.string().optional(),
  }),
});

export const expenseTrendSchema = z.object({
  query: z.object({
    year: z.string().optional(),
  }),
});

export const expenseRangeReportSchema = z.object({
  query: z.object({
    dateFrom: dateString.optional(),
    dateTo: dateString.optional(),
    memberId: z.string().optional(),
    categoryId: z.string().optional(),
    status: z.enum(["pending", "approved", "rejected"]).optional(),
  }),
});

export const expenseProfitSchema = z.object({
  query: z.object({
    year: z.string().optional(),
    month: z.string().optional(),
  }),
});

export const expenseExportSchema = z.object({
  query: expenseListSchema.shape.query,
});

export const expenseCategoryCreateSchema = z.object({
  body: z.object({
    name: z.string().min(1, "Name is required"),
    description: z.string().nullable().optional(),
    sortOrder: z.number().int().optional(),
    status: z.enum(["active", "inactive"]).optional(),
  }),
});

export const expenseCategoryUpdateSchema = z.object({
  params: idParam,
  body: z.object({
    name: z.string().min(1, "Name is required").optional(),
    description: z.string().nullable().optional(),
    sortOrder: z.number().int().optional(),
    status: z.enum(["active", "inactive"]).optional(),
  }),
});

export const expenseCategoryIdSchema = z.object({ params: idParam });
