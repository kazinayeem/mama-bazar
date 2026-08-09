"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.categoryMoveSchema = exports.categoryListSchema = exports.categorySlugSchema = exports.categoryIdSchema = exports.updateCategorySchema = exports.createCategorySchema = void 0;
const zod_1 = require("zod");
const statusEnum = zod_1.z.enum(["active", "inactive", "archived"]);
const baseFields = {
    name: zod_1.z.string().min(1, "Name is required"),
    slug: zod_1.z.string().optional(),
    parentId: zod_1.z.union([zod_1.z.string(), zod_1.z.number(), zod_1.z.null()]).optional(),
    description: zod_1.z.string().optional(),
    image: zod_1.z.string().optional(),
    icon: zod_1.z.string().optional(),
    banner: zod_1.z.string().optional(),
    thumbnail: zod_1.z.string().optional(),
    featured: zod_1.z.union([zod_1.z.boolean(), zod_1.z.string()]).optional(),
    homepageVisibility: zod_1.z.union([zod_1.z.boolean(), zod_1.z.string()]).optional(),
    sortOrder: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    seoTitle: zod_1.z.string().optional(),
    seoDescription: zod_1.z.string().optional(),
    seoKeywords: zod_1.z.string().optional(),
    status: statusEnum.optional(),
};
exports.createCategorySchema = zod_1.z.object({
    body: zod_1.z.object(baseFields),
});
exports.updateCategorySchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
    body: zod_1.z.object({
        ...baseFields,
        name: zod_1.z.string().min(1).optional(),
        parentId: zod_1.z.union([zod_1.z.string(), zod_1.z.number(), zod_1.z.null()]).optional(),
    }),
});
exports.categoryIdSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
});
exports.categorySlugSchema = zod_1.z.object({
    params: zod_1.z.object({ slug: zod_1.z.string().min(1) }),
});
exports.categoryListSchema = zod_1.z.object({
    query: zod_1.z.object({
        page: zod_1.z.string().optional(),
        limit: zod_1.z.string().optional(),
        search: zod_1.z.string().optional(),
        status: zod_1.z.string().optional(),
        parentId: zod_1.z.string().optional(),
        featured: zod_1.z.string().optional(),
        sort: zod_1.z.string().optional(),
    }),
});
exports.categoryMoveSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
    body: zod_1.z.object({
        targetId: zod_1.z.union([zod_1.z.string(), zod_1.z.number(), zod_1.z.null()]),
    }),
});
//# sourceMappingURL=category.schema.js.map