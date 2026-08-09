"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.mediaUpdateSchema = exports.mediaIdSchema = exports.mediaListSchema = exports.mediaUploadSchema = void 0;
const zod_1 = require("zod");
exports.mediaUploadSchema = zod_1.z.object({
    body: zod_1.z.object({
        folder: zod_1.z.string().optional().default("general"),
        alt: zod_1.z.string().optional(),
    }),
});
exports.mediaListSchema = zod_1.z.object({
    query: zod_1.z.object({
        page: zod_1.z.string().optional(),
        limit: zod_1.z.string().optional(),
        folder: zod_1.z.string().optional(),
        search: zod_1.z.string().optional(),
    }),
});
exports.mediaIdSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
});
exports.mediaUpdateSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
    body: zod_1.z.object({
        alt: zod_1.z.string().max(255).optional(),
    }),
});
//# sourceMappingURL=media.schema.js.map