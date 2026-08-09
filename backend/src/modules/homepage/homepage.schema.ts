import { z } from "zod";

const sectionSchema = z
  .object({
    id: z.string().min(1),
    type: z.string().min(1),
    enabled: z.boolean(),
    title: z.string().optional(),
    subtitle: z.string().optional(),
    eyebrow: z.string().optional(),
    ctaText: z.string().optional(),
    ctaUrl: z.string().optional(),
    limit: z.number().int().positive().optional(),
    columns: z.number().int().positive().optional(),
    background: z.enum(["default", "muted", "dark"]).optional(),
  })
  .passthrough();

const heroSlideSchema = z
  .object({
    id: z.string().min(1),
    desktopImage: z.string().min(1),
    tabletImage: z.string().optional(),
    mobileImage: z.string().optional(),
    badge: z.string().optional(),
    title: z.string().optional(),
    subtitle: z.string().optional(),
    description: z.string().optional(),
    primaryButtonText: z.string().optional(),
    primaryButtonUrl: z.string().optional(),
    secondaryButtonText: z.string().optional(),
    secondaryButtonUrl: z.string().optional(),
    backgroundColor: z.string().optional(),
    textColor: z.string().optional(),
    overlay: z.boolean().optional(),
    overlayOpacity: z.number().min(0).max(1).optional(),
    alignment: z.enum(["left", "center", "right"]).optional(),
    status: z.enum(["active", "inactive"]),
    priority: z.number().int(),
  })
  .passthrough();

export const saveConfigSchema = z.object({
  body: z
    .object({
      announcement: z.object({ enabled: z.boolean(), text: z.string(), backgroundColor: z.string().optional(), textColor: z.string().optional() }).passthrough().optional(),
      heroSlides: z.array(heroSlideSchema).optional(),
      sections: z.array(sectionSchema).optional(),
      trustStrip: z.array(z.object({ icon: z.string().optional(), title: z.string(), text: z.string().optional() }).passthrough()).optional(),
      whyChooseUs: z.array(z.object({ icon: z.string().optional(), title: z.string(), text: z.string().optional() }).passthrough()).optional(),
      newsletter: z.object({ enabled: z.boolean(), title: z.string().optional(), subtitle: z.string().optional(), buttonText: z.string().optional() }).passthrough().optional(),
      flashSaleWindow: z.object({ enabled: z.boolean(), start: z.string().nullable().optional(), end: z.string().nullable().optional() }).passthrough().optional(),
    })
    .passthrough(),
});

export const subscribeNewsletterSchema = z.object({
  body: z.object({
    email: z.string().min(1, "Email is required"),
    source: z.string().optional(),
  }),
});
