"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.trackPurchaseSchema = void 0;
const zod_1 = require("zod");
exports.trackPurchaseSchema = zod_1.z.object({
    body: zod_1.z.object({
        value: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).pipe(zod_1.z.coerce.number().positive()),
        contentIds: zod_1.z.union([zod_1.z.string(), zod_1.z.number(), zod_1.z.array(zod_1.z.union([zod_1.z.string(), zod_1.z.number()]))]),
        currency: zod_1.z.string().optional(),
        contentType: zod_1.z.string().optional(),
        fbp: zod_1.z.string().optional(),
        fbc: zod_1.z.string().optional(),
        email: zod_1.z.string().optional(),
        phone: zod_1.z.string().optional(),
    }),
});
//# sourceMappingURL=analytics.schema.js.map