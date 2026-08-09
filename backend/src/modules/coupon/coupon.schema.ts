import { z } from "zod";

export const createCouponSchema = z.object({
  body: z.object({
    code: z.string().min(1, "Code is required"),
    discountType: z.enum(["percentage", "fixed"], { required_error: "Discount type is required" }),
    discountValue: z.union([z.string(), z.number()]).pipe(z.coerce.number().positive("Discount value must be positive")),
    minOrderAmount: z.union([z.string(), z.number()]).optional(),
    expiryDate: z.string().optional(),
    status: z.enum(["active", "inactive"]).optional(),
  }),
});

export const updateCouponSchema = z.object({
  params: z.object({ id: z.string() }),
  body: z.object({
    code: z.string().min(1).optional(),
    discountType: z.enum(["percentage", "fixed"]).optional(),
    discountValue: z.union([z.string(), z.number()]).optional(),
    minOrderAmount: z.union([z.string(), z.number()]).optional(),
    expiryDate: z.string().optional(),
    status: z.enum(["active", "inactive"]).optional(),
  }),
});

export const validateCouponSchema = z.object({
  body: z.object({
    code: z.string().min(1, "Code is required"),
    subtotal: z.union([z.string(), z.number()]).pipe(z.coerce.number().nonnegative()),
  }),
});

export const couponIdSchema = z.object({
  params: z.object({ id: z.string() }),
});
