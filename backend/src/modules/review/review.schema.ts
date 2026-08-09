import { z } from "zod";

export const createReviewSchema = z.object({
  body: z.object({
    productId: z.union([z.string(), z.number()]).pipe(z.coerce.number().int().positive("Product is required")),
    rating: z.union([z.string(), z.number()]).pipe(z.coerce.number().int().min(1).max(5, "Rating must be 1-5")),
    title: z.string().max(255).optional(),
    comment: z.string().min(5, "Review must be at least 5 characters").max(5000),
    customerName: z.string().max(255).optional(),
  }),
});

export const updateReviewStatusSchema = z.object({
  params: z.object({ id: z.string() }),
  body: z.object({
    status: z.enum(["pending", "approved", "rejected"]),
  }),
});

export const reviewIdSchema = z.object({
  params: z.object({ id: z.string() }),
});
