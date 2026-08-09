import { z } from "zod";

export const createBannerSchema = z.object({
  body: z.object({
    title: z.string().optional(),
    subtitle: z.string().optional(),
    image: z.string().optional(),
    imageTablet: z.string().optional(),
    imageMobile: z.string().optional(),
    link: z.string().optional(),
    position: z.enum(["hero", "banner", "promo", "sidebar"]).optional(),
    buttonText: z.string().optional(),
    priority: z
      .union([z.string(), z.number()])
      .optional()
      .transform((v) => Number(v) || 0),
    status: z.enum(["active", "inactive"]).optional(),
  }),
});

export const updateBannerSchema = z.object({
  params: z.object({ id: z.string() }),
  body: z.object({
    title: z.string().optional(),
    subtitle: z.string().optional(),
    image: z.string().optional(),
    imageTablet: z.string().optional(),
    imageMobile: z.string().optional(),
    link: z.string().optional(),
    position: z.enum(["hero", "banner", "promo", "sidebar"]).optional(),
    buttonText: z.string().optional(),
    priority: z
      .union([z.string(), z.number()])
      .optional()
      .transform((v) => Number(v) || 0),
    status: z.enum(["active", "inactive"]).optional(),
  }),
});

export const bannerIdSchema = z.object({
  params: z.object({ id: z.string() }),
});
