import { z } from "zod";

export const createShippingMethodSchema = z.object({
  body: z.object({
    name: z.string().min(1, "Name is required"),
    charge: z.union([z.string(), z.number()]).pipe(z.coerce.number().min(0, "Charge must be non-negative")),
    estimatedDelivery: z.string().optional(),
    description: z.string().optional(),
    priority: z.union([z.string(), z.number()]).optional(),
    freeShippingMinAmount: z.union([z.string(), z.number()]).optional(),
    codAvailable: z.boolean().optional(),
    status: z.enum(["active", "inactive"]).optional(),
  }),
});

export const updateShippingMethodSchema = z.object({
  params: z.object({ id: z.string() }),
  body: z.object({
    name: z.string().min(1).optional(),
    charge: z.union([z.string(), z.number()]).optional(),
    estimatedDelivery: z.string().optional(),
    description: z.string().optional(),
    priority: z.union([z.string(), z.number()]).optional(),
    freeShippingMinAmount: z.union([z.string(), z.number()]).optional(),
    codAvailable: z.boolean().optional(),
    status: z.enum(["active", "inactive"]).optional(),
  }),
});

export const shippingMethodIdSchema = z.object({
  params: z.object({ id: z.string() }),
});

export const estimateShippingSchema = z.object({
  body: z.object({
    subtotal: z.union([z.string(), z.number()]).pipe(z.coerce.number().min(0)),
  }),
});

export type CreateShippingMethodInput = {
  name: string;
  charge: string;
  estimatedDelivery?: string;
  description?: string;
  priority?: number;
  freeShippingMinAmount?: string;
  codAvailable?: boolean;
  status?: "active" | "inactive";
};

export type UpdateShippingMethodInput = Partial<CreateShippingMethodInput>;
