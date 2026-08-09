import { z } from "zod";

export const trackPurchaseSchema = z.object({
  body: z.object({
    value: z.union([z.string(), z.number()]).pipe(z.coerce.number().positive()),
    contentIds: z.union([z.string(), z.number(), z.array(z.union([z.string(), z.number()]))]),
    currency: z.string().optional(),
    contentType: z.string().optional(),
    fbp: z.string().optional(),
    fbc: z.string().optional(),
    email: z.string().optional(),
    phone: z.string().optional(),
  }),
});
