"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.trackingIdSchema = exports.updateTrackingSchema = exports.createTrackingSchema = void 0;
const zod_1 = require("zod");
exports.createTrackingSchema = zod_1.z.object({
    body: zod_1.z.object({
        name: zod_1.z.string().min(1, "Name is required"),
        type: zod_1.z.string().min(1, "Type is required"),
        pixelId: zod_1.z.string().optional(),
        scriptCode: zod_1.z.string().optional(),
        accessToken: zod_1.z.string().optional(),
        testEventCode: zod_1.z.string().optional(),
        status: zod_1.z.enum(["active", "inactive"]).optional(),
    }),
});
exports.updateTrackingSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
    body: zod_1.z.object({
        name: zod_1.z.string().min(1).optional(),
        type: zod_1.z.string().optional(),
        pixelId: zod_1.z.string().optional(),
        scriptCode: zod_1.z.string().optional(),
        accessToken: zod_1.z.string().optional(),
        testEventCode: zod_1.z.string().optional(),
        status: zod_1.z.enum(["active", "inactive"]).optional(),
    }),
});
exports.trackingIdSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
});
//# sourceMappingURL=tracking.schema.js.map