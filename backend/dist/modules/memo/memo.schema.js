"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.memoDeleteManySchema = exports.memoCreateSchema = exports.memoIdSchema = exports.memoListSchema = void 0;
const zod_1 = require("zod");
exports.memoListSchema = zod_1.z.object({
    query: zod_1.z.object({
        page: zod_1.z.string().optional(),
        limit: zod_1.z.string().optional(),
        search: zod_1.z.string().optional(),
        entityType: zod_1.z.string().optional(),
        folder: zod_1.z.string().optional(),
    }),
});
exports.memoIdSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string().refine((v) => /^\d+$/.test(v), { message: "Invalid id" }) }),
});
exports.memoCreateSchema = zod_1.z.object({
    body: zod_1.z.object({
        title: zod_1.z.string().min(1, "Title is required"),
        entityType: zod_1.z.string().min(1, "Entity type is required"),
        entityId: zod_1.z.number().int().positive().optional().nullable(),
        url: zod_1.z.string().url("Valid url is required"),
        publicId: zod_1.z.string().min(1, "Public id is required"),
        filename: zod_1.z.string().min(1, "Filename is required"),
        mimeType: zod_1.z.string().min(1, "Mime type is required"),
        size: zod_1.z.number().int().nonnegative().optional().default(0),
        folder: zod_1.z.string().optional(),
        notes: zod_1.z.string().optional().nullable(),
        uploadedById: zod_1.z.number().int().positive().optional().nullable(),
    }),
});
exports.memoDeleteManySchema = zod_1.z.object({
    body: zod_1.z.object({
        ids: zod_1.z.array(zod_1.z.number().int().positive()).min(1, "At least one id is required"),
    }),
});
//# sourceMappingURL=memo.schema.js.map