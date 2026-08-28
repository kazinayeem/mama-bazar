"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.chatMessageSchema = void 0;
const zod_1 = require("zod");
exports.chatMessageSchema = zod_1.z.object({
    body: zod_1.z.object({
        message: zod_1.z.string({ required_error: "Message is required" }).min(1, "Message cannot be empty").trim(),
    }),
});
//# sourceMappingURL=chat.schema.js.map