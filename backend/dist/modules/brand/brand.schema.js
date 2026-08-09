"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.brandMoveSchema = exports.brandListSchema = exports.brandIdSchema = exports.updateBrandSchema = exports.createBrandSchema = void 0;
const zod_1 = require("zod");
const baseFields = {
    name: zod_1.z.string().min(1, "Name is required"),
    slug: zod_1.z.string().optional(),
    logo: zod_1.z.string().optional(),
    bannerImage: zod_1.z.string().optional(),
    description: zod_1.z.string().optional(),
    website: zod_1.z.string().optional(),
    countryOfOrigin: zod_1.z.string().optional(),
    featured: zod_1.z.union([zod_1.z.boolean(), zod_1.z.string()]).optional(),
    homepageVisibility: zod_1.z.union([zod_1.z.boolean(), zod_1.z.string()]).optional(),
    sortOrder: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    seoTitle: zod_1.z.string().optional(),
    seoDescription: zod_1.z.string().optional(),
    seoKeywords: zod_1.z.string().optional(),
    status: zod_1.z.enum(["active", "inactive", "archived"]).optional(),
};
exports.createBrandSchema = zod_1.z.object({
    body: zod_1.z.object(baseFields),
});
exports.updateBrandSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
    body: zod_1.z.object({
        ...baseFields,
        name: zod_1.z.string().min(1).optional(),
    }),
});
exports.brandIdSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
});
exports.brandListSchema = zod_1.z.object({
    query: zod_1.z.object({
        page: zod_1.z.string().optional(),
        limit: zod_1.z.string().optional(),
        search: zod_1.z.string().optional(),
        status: zod_1.z.string().optional(),
        featured: zod_1.z.string().optional(),
        sort: zod_1.z.string().optional(),
    }),
});
exports.brandMoveSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
    body: zod_1.z.object({
        targetId: zod_1.z.union([zod_1.z.string(), zod_1.z.number(), zod_1.z.null()]),
    }),
});
//# sourceMappingURL=brand.schema.js.map