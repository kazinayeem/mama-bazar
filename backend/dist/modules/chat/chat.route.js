"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const chat_controller_1 = require("./chat.controller");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const chat_schema_1 = require("./chat.schema");
const router = (0, express_1.Router)();
// Public route: POST /api/chat
router.post("/", (0, validate_1.validate)(chat_schema_1.chatMessageSchema), (0, asyncHandler_1.asyncHandler)(chat_controller_1.handleChat));
exports.default = router;
//# sourceMappingURL=chat.route.js.map