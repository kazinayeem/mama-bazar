"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.getSettingSchema = exports.setSettingSchema = void 0;
const zod_1 = require("zod");
exports.setSettingSchema = zod_1.z.object({
    body: zod_1.z.object({
        key: zod_1.z.string().min(1, "Key is required"),
        value: zod_1.z.any(),
    }),
});
exports.getSettingSchema = zod_1.z.object({
    params: zod_1.z.object({ key: zod_1.z.string().min(1) }),
});
//# sourceMappingURL=settings.schema.js.map