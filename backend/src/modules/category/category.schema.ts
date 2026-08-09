import { z } from "zod";

const statusEnum = z.enum(["active", "inactive", "archived"]);

const baseFields = {
  name: z.string().min(1, "Name is required"),
  slug: z.string().optional(),
  parentId: z.union([z.string(), z.number(), z.null()]).optional(),
  description: z.string().optional(),
  image: z.string().optional(),
  icon: z.string().optional(),
  banner: z.string().optional(),
  thumbnail: z.string().optional(),
  featured: z.union([z.boolean(), z.string()]).optional(),
  homepageVisibility: z.union([z.boolean(), z.string()]).optional(),
  sortOrder: z.union([z.string(), z.number()]).optional(),
  seoTitle: z.string().optional(),
  seoDescription: z.string().optional(),
  seoKeywords: z.string().optional(),
  status: statusEnum.optional(),
};

export const createCategorySchema = z.object({
  body: z.object(baseFields),
});

export const updateCategorySchema = z.object({
  params: z.object({ id: z.string() }),
  body: z.object({
    ...baseFields,
    name: z.string().min(1).optional(),
    parentId: z.union([z.string(), z.number(), z.null()]).optional(),
  }),
});

export const categoryIdSchema = z.object({
  params: z.object({ id: z.string() }),
});

export const categorySlugSchema = z.object({
  params: z.object({ slug: z.string().min(1) }),
});

export const categoryListSchema = z.object({
  query: z.object({
    page: z.string().optional(),
    limit: z.string().optional(),
    search: z.string().optional(),
    status: z.string().optional(),
    parentId: z.string().optional(),
    featured: z.string().optional(),
    sort: z.string().optional(),
  }),
});

export const categoryMoveSchema = z.object({
  params: z.object({ id: z.string() }),
  body: z.object({
    targetId: z.union([z.string(), z.number(), z.null()]),
  }),
});
