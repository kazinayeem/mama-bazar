import { z } from "zod";

export const memoListSchema = z.object({
  query: z.object({
    page: z.string().optional(),
    limit: z.string().optional(),
    search: z.string().optional(),
    entityType: z.string().optional(),
    folder: z.string().optional(),
  }),
});

export const memoIdSchema = z.object({
  params: z.object({ id: z.string().refine((v) => /^\d+$/.test(v), { message: "Invalid id" }) }),
});

export const memoCreateSchema = z.object({
  body: z.object({
    title: z.string().min(1, "Title is required"),
    entityType: z.string().min(1, "Entity type is required"),
    entityId: z.number().int().positive().optional().nullable(),
    url: z.string().url("Valid url is required"),
    publicId: z.string().min(1, "Public id is required"),
    filename: z.string().min(1, "Filename is required"),
    mimeType: z.string().min(1, "Mime type is required"),
    size: z.number().int().nonnegative().optional().default(0),
    folder: z.string().optional(),
    notes: z.string().optional().nullable(),
    uploadedById: z.number().int().positive().optional().nullable(),
  }),
});

export const memoDeleteManySchema = z.object({
  body: z.object({
    ids: z.array(z.number().int().positive()).min(1, "At least one id is required"),
  }),
});
