"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.couponIdSchema = exports.validateCouponSchema = exports.updateCouponSchema = exports.createCouponSchema = void 0;
const zod_1 = require("zod");
exports.createCouponSchema = zod_1.z.object({
    body: zod_1.z.object({
        code: zod_1.z.string().min(1, "Code is required"),
        discountType: zod_1.z.enum(["percentage", "fixed"], { required_error: "Discount type is required" }),
        discountValue: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).pipe(zod_1.z.coerce.number().positive("Discount value must be positive")),
        minOrderAmount: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        expiryDate: zod_1.z.string().optional(),
        status: zod_1.z.enum(["active", "inactive"]).optional(),
    }),
});
exports.updateCouponSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
    body: zod_1.z.object({
        code: zod_1.z.string().min(1).optional(),
        discountType: zod_1.z.enum(["percentage", "fixed"]).optional(),
        discountValue: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        minOrderAmount: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        expiryDate: zod_1.z.string().optional(),
        status: zod_1.z.enum(["active", "inactive"]).optional(),
    }),
});
exports.validateCouponSchema = zod_1.z.object({
    body: zod_1.z.object({
        code: zod_1.z.string().min(1, "Code is required"),
        subtotal: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).pipe(zod_1.z.coerce.number().nonnegative()),
    }),
});
exports.couponIdSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
});
//# sourceMappingURL=coupon.schema.js.map