"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.estimateShippingSchema = exports.shippingMethodIdSchema = exports.updateShippingMethodSchema = exports.createShippingMethodSchema = void 0;
const zod_1 = require("zod");
exports.createShippingMethodSchema = zod_1.z.object({
    body: zod_1.z.object({
        name: zod_1.z.string().min(1, "Name is required"),
        charge: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).pipe(zod_1.z.coerce.number().min(0, "Charge must be non-negative")),
        estimatedDelivery: zod_1.z.string().optional(),
        description: zod_1.z.string().optional(),
        priority: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        freeShippingMinAmount: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        codAvailable: zod_1.z.boolean().optional(),
        status: zod_1.z.enum(["active", "inactive"]).optional(),
    }),
});
exports.updateShippingMethodSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
    body: zod_1.z.object({
        name: zod_1.z.string().min(1).optional(),
        charge: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        estimatedDelivery: zod_1.z.string().optional(),
        description: zod_1.z.string().optional(),
        priority: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        freeShippingMinAmount: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        codAvailable: zod_1.z.boolean().optional(),
        status: zod_1.z.enum(["active", "inactive"]).optional(),
    }),
});
exports.shippingMethodIdSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
});
exports.estimateShippingSchema = zod_1.z.object({
    body: zod_1.z.object({
        subtotal: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).pipe(zod_1.z.coerce.number().min(0)),
    }),
});
//# sourceMappingURL=shipping.schema.js.map