import { z } from "zod";

export const expenseListSchema = z.object({
  query: z.object({
    page: z.string().optional(),
    limit: z.string().optional(),
    search: z.string().optional(),
    status: z.string().optional(),
  }),
});

export const expenseIdSchema = z.object({
  params: z.object({ id: z.string().refine((v) => /^\d+$/.test(v), { message: "Invalid id" }) }),
});

export const expenseCreateSchema = z.object({
  body: z.object({
    title: z.string().min(1, "Title is required"),
    description: z.string().nullable().optional(),
    categoryId: z.number().int().positive().nullable().optional(),
    amount: z.union([z.string(), z.number()]).refine((v) => Number(v) > 0, { message: "Amount must be greater than 0" }),
    paymentMethod: z.string().optional(),
    vendor: z.string().nullable().optional(),
    expenseDate: z.string().min(1, "Expense date is required"),
    referenceNumber: z.string().nullable().optional(),
    attachmentUrl: z.string().nullable().optional(),
    notes: z.string().nullable().optional(),
    status: z.enum(["pending", "approved", "rejected"]).optional(),
  }),
});

export const expenseUpdateSchema = z.object({
  params: z.object({ id: z.string().refine((v) => /^\d+$/.test(v), { message: "Invalid id" }) }),
  body: z.object({
    title: z.string().min(1, "Title is required").optional(),
    description: z.string().nullable().optional(),
    categoryId: z.number().int().positive().nullable().optional(),
    amount: z.union([z.string(), z.number()]).refine((v) => Number(v) > 0, { message: "Amount must be greater than 0" }).optional(),
    paymentMethod: z.string().optional(),
    vendor: z.string().nullable().optional(),
    expenseDate: z.string().optional(),
    referenceNumber: z.string().nullable().optional(),
    attachmentUrl: z.string().nullable().optional(),
    notes: z.string().nullable().optional(),
    status: z.enum(["pending", "approved", "rejected"]).optional(),
  }),
});
