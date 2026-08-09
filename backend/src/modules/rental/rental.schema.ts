import { z } from "zod";

export const rentalListSchema = z.object({
  query: z.object({
    page: z.string().optional(),
    limit: z.string().optional(),
    search: z.string().optional(),
    status: z.enum(["reserved", "rented", "returned", "overdue", "cancelled"]).optional(),
    paymentStatus: z.enum(["pending", "partial", "paid", "refunded"]).optional(),
  }),
});

export const rentalIdSchema = z.object({
  params: z.object({ id: z.string().refine((v) => /^\d+$/.test(v), { message: "Invalid id" }) }),
});

export const rentalCreateSchema = z.object({
  body: z.object({
    rentalItem: z.string().min(1, "Rental item is required"),
    productId: z.number().int().positive().optional().nullable(),
    customerName: z.string().min(1, "Customer name is required"),
    phone: z.string().min(1, "Phone is required"),
    email: z.string().email().optional().nullable(),
    userId: z.number().int().positive().optional().nullable(),
    quantity: z.number().int().positive().optional().default(1),
    startDate: z.string().min(1, "Start date is required"),
    endDate: z.string().min(1, "End date is required"),
    returnDate: z.string().optional().nullable(),
    rateType: z.enum(["daily", "weekly", "monthly"]).optional(),
    dailyRate: z.union([z.string(), z.number()]).optional(),
    weeklyRate: z.union([z.string(), z.number()]).optional(),
    monthlyRate: z.union([z.string(), z.number()]).optional(),
    rate: z.union([z.string(), z.number()]).optional(),
    durationUnits: z.number().int().optional().default(0),
    securityDeposit: z.union([z.string(), z.number()]).optional(),
    discount: z.union([z.string(), z.number()]).optional(),
    additionalCharge: z.union([z.string(), z.number()]).optional(),
    totalAmount: z.union([z.string(), z.number()]).optional(),
    paymentStatus: z.enum(["pending", "partial", "paid", "refunded"]).optional(),
    status: z.enum(["reserved", "rented", "returned", "overdue", "cancelled"]).optional(),
    notes: z.string().optional().nullable(),
    attachmentUrl: z.string().optional().nullable(),
    createdById: z.number().int().positive().optional().nullable(),
  }),
});

export const rentalUpdateSchema = z.object({
  params: z.object({ id: z.string().refine((v) => /^\d+$/.test(v), { message: "Invalid id" }) }),
  body: z.object({
    rentalItem: z.string().min(1, "Rental item is required").optional(),
    productId: z.number().int().positive().optional().nullable(),
    customerName: z.string().min(1, "Customer name is required").optional(),
    phone: z.string().optional(),
    email: z.string().email().optional().nullable(),
    userId: z.number().int().positive().optional().nullable(),
    quantity: z.number().int().positive().optional(),
    startDate: z.string().optional(),
    endDate: z.string().optional(),
    returnDate: z.string().optional().nullable(),
    rateType: z.enum(["daily", "weekly", "monthly"]).optional(),
    dailyRate: z.union([z.string(), z.number()]).optional(),
    weeklyRate: z.union([z.string(), z.number()]).optional(),
    monthlyRate: z.union([z.string(), z.number()]).optional(),
    rate: z.union([z.string(), z.number()]).optional(),
    durationUnits: z.number().int().optional(),
    securityDeposit: z.union([z.string(), z.number()]).optional(),
    discount: z.union([z.string(), z.number()]).optional(),
    additionalCharge: z.union([z.string(), z.number()]).optional(),
    totalAmount: z.union([z.string(), z.number()]).optional(),
    paymentStatus: z.enum(["pending", "partial", "paid", "refunded"]).optional(),
    status: z.enum(["reserved", "rented", "returned", "overdue", "cancelled"]).optional(),
    notes: z.string().optional().nullable(),
    attachmentUrl: z.string().optional().nullable(),
    createdById: z.number().int().positive().optional().nullable(),
  }),
});
