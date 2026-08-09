import { z } from "zod";

export const createCheckoutNoticeSchema = z.object({
  body: z.object({
    text: z.string().min(1, "Text is required"),
    priority: z.union([z.string(), z.number()]).optional(),
    backgroundColor: z.string().optional(),
    textColor: z.string().optional(),
    icon: z.string().optional(),
    status: z.enum(["active", "inactive"]).optional(),
  }),
});

export const updateCheckoutNoticeSchema = z.object({
  params: z.object({ id: z.string() }),
  body: z.object({
    text: z.string().min(1).optional(),
    priority: z.union([z.string(), z.number()]).optional(),
    backgroundColor: z.string().optional(),
    textColor: z.string().optional(),
    icon: z.string().optional(),
    status: z.enum(["active", "inactive"]).optional(),
  }),
});

export const checkoutNoticeIdSchema = z.object({
  params: z.object({ id: z.string() }),
});

export type CreateCheckoutNoticeInput = {
  text: string;
  priority?: number;
  backgroundColor?: string;
  textColor?: string;
  icon?: string;
  status?: "active" | "inactive";
};

export type UpdateCheckoutNoticeInput = Partial<CreateCheckoutNoticeInput>;
