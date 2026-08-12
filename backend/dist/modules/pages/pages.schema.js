"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.contactMessageSchema = exports.pageIdSchema = exports.updatePageSchema = exports.createPageSchema = exports.pageSlugSchema = void 0;
const zod_1 = require("zod");
exports.pageSlugSchema = zod_1.z.object({
    params: zod_1.z.object({ slug: zod_1.z.string().min(1) }),
});
exports.createPageSchema = zod_1.z.object({
    body: zod_1.z.object({
        slug: zod_1.z.string().min(1).max(150),
        title: zod_1.z.string().min(1).max(200),
        content: zod_1.z.string().min(1),
        status: zod_1.z.enum(["published", "draft"]).default("published"),
    }),
});
exports.updatePageSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
    body: zod_1.z.object({
        title: zod_1.z.string().min(1).max(200).optional(),
        content: zod_1.z.string().min(1).optional(),
        status: zod_1.z.enum(["published", "draft"]).optional(),
    }),
});
exports.pageIdSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
});
exports.contactMessageSchema = zod_1.z.object({
    body: zod_1.z.object({
        name: zod_1.z.string().min(1, "Name is required"),
        phone: zod_1.z.string().min(1, "Phone is required"),
        email: zod_1.z.string().email("Invalid email").optional().or(zod_1.z.literal("")),
        message: zod_1.z.string().min(1, "Message is required"),
    }),
});
//# sourceMappingURL=pages.schema.js.map