import { z } from "zod";

export const bookingListSchema = z.object({
  query: z.object({
    page: z.string().optional(),
    limit: z.string().optional(),
    search: z.string().optional(),
    status: z.enum(["pending", "confirmed", "active", "completed", "cancelled"]).optional(),
    paymentStatus: z.enum(["pending", "partial", "paid", "refunded"]).optional(),
  }),
});

export const bookingIdSchema = z.object({
  params: z.object({ id: z.string().refine((v) => /^\d+$/.test(v), { message: "Invalid id" }) }),
});

export const bookingCreateSchema = z.object({
  body: z.object({
    customerName: z.string().min(1, "Customer name is required"),
    phone: z.string().min(1, "Phone is required"),
    email: z.string().email().optional().nullable(),
    userId: z.number().int().positive().optional().nullable(),
    bookingType: z.string().optional(),
    service: z.string().optional().nullable(),
    productId: z.number().int().positive().optional().nullable(),
    startDate: z.string().min(1, "Start date is required"),
    endDate: z.string().min(1, "End date is required"),
    quantity: z.number().int().positive().optional().default(1),
    price: z.union([z.string(), z.number()]).optional(),
    discount: z.union([z.string(), z.number()]).optional(),
    additionalCost: z.union([z.string(), z.number()]).optional(),
    totalAmount: z.union([z.string(), z.number()]).optional(),
    paymentStatus: z.enum(["pending", "partial", "paid", "refunded"]).optional(),
    status: z.enum(["pending", "confirmed", "active", "completed", "cancelled"]).optional(),
    notes: z.string().optional().nullable(),
    attachmentUrl: z.string().optional().nullable(),
  }),
});

export const bookingUpdateSchema = z.object({
  params: z.object({ id: z.string().refine((v) => /^\d+$/.test(v), { message: "Invalid id" }) }),
  body: z.object({
    customerName: z.string().min(1, "Customer name is required").optional(),
    phone: z.string().min(1, "Phone is required").optional(),
    email: z.string().email().optional().nullable(),
    userId: z.number().int().positive().optional().nullable(),
    bookingType: z.string().optional(),
    service: z.string().optional().nullable(),
    productId: z.number().int().positive().optional().nullable(),
    startDate: z.string().optional(),
    endDate: z.string().optional(),
    quantity: z.number().int().positive().optional(),
    price: z.union([z.string(), z.number()]).optional(),
    discount: z.union([z.string(), z.number()]).optional(),
    additionalCost: z.union([z.string(), z.number()]).optional(),
    totalAmount: z.union([z.string(), z.number()]).optional(),
    paymentStatus: z.enum(["pending", "partial", "paid", "refunded"]).optional(),
    status: z.enum(["pending", "confirmed", "active", "completed", "cancelled"]).optional(),
    notes: z.string().optional().nullable(),
    attachmentUrl: z.string().optional().nullable(),
  }),
});
