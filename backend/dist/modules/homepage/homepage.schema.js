"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.subscribeNewsletterSchema = exports.saveConfigSchema = void 0;
const zod_1 = require("zod");
const sectionSchema = zod_1.z
    .object({
    id: zod_1.z.string().min(1),
    type: zod_1.z.string().min(1),
    enabled: zod_1.z.boolean(),
    title: zod_1.z.string().optional(),
    subtitle: zod_1.z.string().optional(),
    eyebrow: zod_1.z.string().optional(),
    ctaText: zod_1.z.string().optional(),
    ctaUrl: zod_1.z.string().optional(),
    limit: zod_1.z.number().int().positive().optional(),
    columns: zod_1.z.number().int().positive().optional(),
    background: zod_1.z.enum(["default", "muted", "dark"]).optional(),
})
    .passthrough();
const heroSlideSchema = zod_1.z
    .object({
    id: zod_1.z.string().min(1),
    desktopImage: zod_1.z.string().min(1),
    tabletImage: zod_1.z.string().optional(),
    mobileImage: zod_1.z.string().optional(),
    badge: zod_1.z.string().optional(),
    title: zod_1.z.string().optional(),
    subtitle: zod_1.z.string().optional(),
    description: zod_1.z.string().optional(),
    primaryButtonText: zod_1.z.string().optional(),
    primaryButtonUrl: zod_1.z.string().optional(),
    secondaryButtonText: zod_1.z.string().optional(),
    secondaryButtonUrl: zod_1.z.string().optional(),
    backgroundColor: zod_1.z.string().optional(),
    textColor: zod_1.z.string().optional(),
    overlay: zod_1.z.boolean().optional(),
    overlayOpacity: zod_1.z.number().min(0).max(1).optional(),
    alignment: zod_1.z.enum(["left", "center", "right"]).optional(),
    status: zod_1.z.enum(["active", "inactive"]),
    priority: zod_1.z.number().int(),
})
    .passthrough();
exports.saveConfigSchema = zod_1.z.object({
    body: zod_1.z
        .object({
        announcement: zod_1.z.object({ enabled: zod_1.z.boolean(), text: zod_1.z.string(), backgroundColor: zod_1.z.string().optional(), textColor: zod_1.z.string().optional() }).passthrough().optional(),
        heroSlides: zod_1.z.array(heroSlideSchema).optional(),
        sections: zod_1.z.array(sectionSchema).optional(),
        trustStrip: zod_1.z.array(zod_1.z.object({ icon: zod_1.z.string().optional(), title: zod_1.z.string(), text: zod_1.z.string().optional() }).passthrough()).optional(),
        whyChooseUs: zod_1.z.array(zod_1.z.object({ icon: zod_1.z.string().optional(), title: zod_1.z.string(), text: zod_1.z.string().optional() }).passthrough()).optional(),
        newsletter: zod_1.z.object({ enabled: zod_1.z.boolean(), title: zod_1.z.string().optional(), subtitle: zod_1.z.string().optional(), buttonText: zod_1.z.string().optional() }).passthrough().optional(),
        flashSaleWindow: zod_1.z.object({ enabled: zod_1.z.boolean(), start: zod_1.z.string().nullable().optional(), end: zod_1.z.string().nullable().optional() }).passthrough().optional(),
    })
        .passthrough(),
});
exports.subscribeNewsletterSchema = zod_1.z.object({
    body: zod_1.z.object({
        email: zod_1.z.string().min(1, "Email is required"),
        source: zod_1.z.string().optional(),
    }),
});
//# sourceMappingURL=homepage.schema.js.map