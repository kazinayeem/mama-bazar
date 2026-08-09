import { z } from "zod";

export const pageSlugSchema = z.object({
  params: z.object({ slug: z.string().min(1) }),
});

export const createPageSchema = z.object({
  body: z.object({
    slug: z.string().min(1).max(150),
    title: z.string().min(1).max(200),
    content: z.string().min(1),
    status: z.enum(["published", "draft"]).default("published"),
  }),
});

export const updatePageSchema = z.object({
  params: z.object({ id: z.string() }),
  body: z.object({
    title: z.string().min(1).max(200).optional(),
    content: z.string().min(1).optional(),
    status: z.enum(["published", "draft"]).optional(),
  }),
});

export const pageIdSchema = z.object({
  params: z.object({ id: z.string() }),
});

export const contactMessageSchema = z.object({
  body: z.object({
    name: z.string().min(1, "Name is required"),
    phone: z.string().min(1, "Phone is required"),
    email: z.string().email("Invalid email").optional().or(z.literal("")),
    message: z.string().min(1, "Message is required"),
  }),
});