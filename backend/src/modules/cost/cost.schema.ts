import { z } from "zod";

export const costListSchema = z.object({
  query: z.object({
    page: z.string().optional(),
    limit: z.string().optional(),
    search: z.string().optional(),
    costType: z.string().optional(),
  }),
});

export const costIdSchema = z.object({
  params: z.object({ id: z.string().refine((v) => /^\d+$/.test(v), { message: "Invalid id" }) }),
});

export const costCreateSchema = z.object({
  body: z.object({
    title: z.string().min(1, "Title is required"),
    costType: z.string().optional(),
    quantity: z.union([z.string(), z.number()]).optional(),
    unitCost: z.union([z.string(), z.number()]).optional(),
    totalCost: z.union([z.string(), z.number()]).optional(),
    supplierId: z.number().int().positive().nullable().optional(),
    productId: z.number().int().positive().nullable().optional(),
    orderId: z.number().int().positive().nullable().optional(),
    bookingId: z.number().int().positive().nullable().optional(),
    costDate: z.string().min(1, "Cost date is required"),
    paymentMethod: z.string().optional(),
    notes: z.string().nullable().optional(),
    attachmentUrl: z.string().nullable().optional(),
  }),
});

export const costUpdateSchema = z.object({
  params: z.object({ id: z.string().refine((v) => /^\d+$/.test(v), { message: "Invalid id" }) }),
  body: z.object({
    title: z.string().min(1, "Title is required").optional(),
    costType: z.string().optional(),
    quantity: z.union([z.string(), z.number()]).optional(),
    unitCost: z.union([z.string(), z.number()]).optional(),
    totalCost: z.union([z.string(), z.number()]).optional(),
    supplierId: z.number().int().positive().nullable().optional(),
    productId: z.number().int().positive().nullable().optional(),
    orderId: z.number().int().positive().nullable().optional(),
    bookingId: z.number().int().positive().nullable().optional(),
    costDate: z.string().optional(),
    paymentMethod: z.string().optional(),
    notes: z.string().nullable().optional(),
    attachmentUrl: z.string().nullable().optional(),
  }),
});
