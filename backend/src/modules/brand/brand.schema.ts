import { z } from "zod";

const baseFields = {
  name: z.string().min(1, "Name is required"),
  slug: z.string().optional(),
  logo: z.string().optional(),
  bannerImage: z.string().optional(),
  description: z.string().optional(),
  website: z.string().optional(),
  countryOfOrigin: z.string().optional(),
  featured: z.union([z.boolean(), z.string()]).optional(),
  homepageVisibility: z.union([z.boolean(), z.string()]).optional(),
  sortOrder: z.union([z.string(), z.number()]).optional(),
  seoTitle: z.string().optional(),
  seoDescription: z.string().optional(),
  seoKeywords: z.string().optional(),
  status: z.enum(["active", "inactive", "archived"]).optional(),
};

export const createBrandSchema = z.object({
  body: z.object(baseFields),
});

export const updateBrandSchema = z.object({
  params: z.object({ id: z.string() }),
  body: z.object({
    ...baseFields,
    name: z.string().min(1).optional(),
  }),
});

export const brandIdSchema = z.object({
  params: z.object({ id: z.string() }),
});

export const brandListSchema = z.object({
  query: z.object({
    page: z.string().optional(),
    limit: z.string().optional(),
    search: z.string().optional(),
    status: z.string().optional(),
    featured: z.string().optional(),
    sort: z.string().optional(),
  }),
});

export const brandMoveSchema = z.object({
  params: z.object({ id: z.string() }),
  body: z.object({
    targetId: z.union([z.string(), z.number(), z.null()]),
  }),
});
