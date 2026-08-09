"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.checkoutNoticeIdSchema = exports.updateCheckoutNoticeSchema = exports.createCheckoutNoticeSchema = void 0;
const zod_1 = require("zod");
exports.createCheckoutNoticeSchema = zod_1.z.object({
    body: zod_1.z.object({
        text: zod_1.z.string().min(1, "Text is required"),
        priority: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        backgroundColor: zod_1.z.string().optional(),
        textColor: zod_1.z.string().optional(),
        icon: zod_1.z.string().optional(),
        status: zod_1.z.enum(["active", "inactive"]).optional(),
    }),
});
exports.updateCheckoutNoticeSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
    body: zod_1.z.object({
        text: zod_1.z.string().min(1).optional(),
        priority: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
        backgroundColor: zod_1.z.string().optional(),
        textColor: zod_1.z.string().optional(),
        icon: zod_1.z.string().optional(),
        status: zod_1.z.enum(["active", "inactive"]).optional(),
    }),
});
exports.checkoutNoticeIdSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
});
//# sourceMappingURL=checkout-notice.schema.js.map