import { z } from "zod";

const configSchema = z
  .object({
    merchantNumber: z.string().optional(),
    merchantName: z.string().optional(),
    bankName: z.string().optional(),
    accountName: z.string().optional(),
    accountNumber: z.string().optional(),
    routingNumber: z.string().optional(),
    branch: z.string().optional(),
    instructions: z.string().optional(),
    qrCode: z.string().optional(),
    minAmount: z.union([z.string(), z.number()]).optional(),
    maxAmount: z.union([z.string(), z.number()]).optional(),
    extraFee: z.union([z.string(), z.number()]).optional(),
    extraFeePercent: z.union([z.string(), z.number()]).optional(),
  })
  .optional();

export const createPaymentMethodSchema = z.object({
  body: z.object({
    code: z.string().min(1, "Code is required"),
    name: z.string().min(1, "Name is required"),
    type: z.enum(["cod", "mobile_banking", "bank", "online"]),
    enabled: z.boolean().optional(),
    sortOrder: z.union([z.string(), z.number()]).optional(),
    maintenanceMode: z.boolean().optional(),
    config: configSchema,
  }),
});

export const updatePaymentMethodSchema = z.object({
  params: z.object({ id: z.string() }),
  body: z.object({
    code: z.string().min(1).optional(),
    name: z.string().min(1).optional(),
    type: z.enum(["cod", "mobile_banking", "bank", "online"]).optional(),
    enabled: z.boolean().optional(),
    sortOrder: z.union([z.string(), z.number()]).optional(),
    maintenanceMode: z.boolean().optional(),
    config: configSchema,
  }),
});

export const paymentMethodIdSchema = z.object({
  params: z.object({ id: z.string() }),
});

export const paymentMethodsStatusSchema = z.object({
  body: z.object({
    ids: z.array(z.number()).min(1),
    enabled: z.boolean(),
  }),
});

export type CreatePaymentMethodInput = {
  code: string;
  name: string;
  type: "cod" | "mobile_banking" | "bank" | "online";
  enabled?: boolean;
  sortOrder?: number;
  maintenanceMode?: boolean;
  config?: Record<string, unknown>;
};

export type UpdatePaymentMethodInput = Partial<CreatePaymentMethodInput>;
