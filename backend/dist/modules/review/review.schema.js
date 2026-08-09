"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.reviewIdSchema = exports.updateReviewStatusSchema = exports.createReviewSchema = void 0;
const zod_1 = require("zod");
exports.createReviewSchema = zod_1.z.object({
    body: zod_1.z.object({
        productId: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).pipe(zod_1.z.coerce.number().int().positive("Product is required")),
        rating: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).pipe(zod_1.z.coerce.number().int().min(1).max(5, "Rating must be 1-5")),
        title: zod_1.z.string().max(255).optional(),
        comment: zod_1.z.string().min(5, "Review must be at least 5 characters").max(5000),
        customerName: zod_1.z.string().max(255).optional(),
    }),
});
exports.updateReviewStatusSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
    body: zod_1.z.object({
        status: zod_1.z.enum(["pending", "approved", "rejected"]),
    }),
});
exports.reviewIdSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
});
//# sourceMappingURL=review.schema.js.map