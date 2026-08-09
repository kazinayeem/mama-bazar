"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.paymentMethodsStatusSchema = exports.paymentMethodIdSchema = exports.updatePaymentMethodSchema = exports.createPaymentMethodSchema = void 0;
const zod_1 = require("zod");
const configSchema = zod_1.z
    .object({
    merchantNumber: zod_1.z.string().optional(),
    merchantName: zod_1.z.string().optional(),
    bankName: zod_1.z.string().optional(),
    accountName: zod_1.z.string().optional(),
    accountNumber: zod_1.z.string().optional(),
    routingNumber: zod_1.z.string().optional(),
    branch: zod_1.z.string().optional(),
    instructions: zod_1.z.string().optional(),
    qrCode: zod_1.z.string().optional(),
    minAmount: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    maxAmount: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    extraFee: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    extraFeePercent: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
})
    .optional();
exports.createPaymentMethodSchema = zod_1.z.object({
    body: zod_1.z.object({
        code: zod_1.z.string().min(1, "Code is required"),
        name: zod_1.z.string().min(1, "Name is required"),
        type: zod_1.z.enum(["cod", "mobile_banking", "bank", "online"]),
        enabled: zod_1.z.boolean().optional(),
        sortOrder: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        maintenanceMode: zod_1.z.boolean().optional(),
        config: configSchema,
    }),
});
exports.updatePaymentMethodSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
    body: zod_1.z.object({
        code: zod_1.z.string().min(1).optional(),
        name: zod_1.z.string().min(1).optional(),
        type: zod_1.z.enum(["cod", "mobile_banking", "bank", "online"]).optional(),
        enabled: zod_1.z.boolean().optional(),
        sortOrder: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        maintenanceMode: zod_1.z.boolean().optional(),
        config: configSchema,
    }),
});
exports.paymentMethodIdSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
});
exports.paymentMethodsStatusSchema = zod_1.z.object({
    body: zod_1.z.object({
        ids: zod_1.z.array(zod_1.z.number()).min(1),
        enabled: zod_1.z.boolean(),
    }),
});
//# sourceMappingURL=payment.schema.js.map