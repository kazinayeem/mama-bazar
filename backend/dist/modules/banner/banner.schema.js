"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.bannerIdSchema = exports.updateBannerSchema = exports.createBannerSchema = void 0;
const zod_1 = require("zod");
exports.createBannerSchema = zod_1.z.object({
    body: zod_1.z.object({
        title: zod_1.z.string().optional(),
        subtitle: zod_1.z.string().optional(),
        image: zod_1.z.string().optional(),
        imageTablet: zod_1.z.string().optional(),
        imageMobile: zod_1.z.string().optional(),
        link: zod_1.z.string().optional(),
        position: zod_1.z.enum(["hero", "banner", "promo", "sidebar"]).optional(),
        buttonText: zod_1.z.string().optional(),
        priority: zod_1.z
            .union([zod_1.z.string(), zod_1.z.number()])
            .optional()
            .transform((v) => Number(v) || 0),
        status: zod_1.z.enum(["active", "inactive"]).optional(),
    }),
});
exports.updateBannerSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
    body: zod_1.z.object({
        title: zod_1.z.string().optional(),
        subtitle: zod_1.z.string().optional(),
        image: zod_1.z.string().optional(),
        imageTablet: zod_1.z.string().optional(),
        imageMobile: zod_1.z.string().optional(),
        link: zod_1.z.string().optional(),
        position: zod_1.z.enum(["hero", "banner", "promo", "sidebar"]).optional(),
        buttonText: zod_1.z.string().optional(),
        priority: zod_1.z
            .union([zod_1.z.string(), zod_1.z.number()])
            .optional()
            .transform((v) => Number(v) || 0),
        status: zod_1.z.enum(["active", "inactive"]).optional(),
    }),
});
exports.bannerIdSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
});
//# sourceMappingURL=banner.schema.js.map