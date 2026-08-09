import { z } from "zod";

export const mediaUploadSchema = z.object({
  body: z.object({
    folder: z.string().optional().default("general"),
    alt: z.string().optional(),
  }),
});

export const mediaListSchema = z.object({
  query: z.object({
    page: z.string().optional(),
    limit: z.string().optional(),
    folder: z.string().optional(),
    search: z.string().optional(),
  }),
});

export const mediaIdSchema = z.object({
  params: z.object({ id: z.string() }),
});

export const mediaUpdateSchema = z.object({
  params: z.object({ id: z.string() }),
  body: z.object({
    alt: z.string().max(255).optional(),
  }),
});
