"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const memo_schema_1 = require("./memo.schema");
const memo_controller_1 = require("./memo.controller");
const router = (0, express_1.Router)();
router.get("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("memos.view"), (0, validate_1.validate)(memo_schema_1.memoListSchema), (0, asyncHandler_1.asyncHandler)(memo_controller_1.list));
router.get("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("memos.view"), (0, validate_1.validate)(memo_schema_1.memoIdSchema), (0, asyncHandler_1.asyncHandler)(memo_controller_1.getById));
router.post("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("memos.upload"), (0, validate_1.validate)(memo_schema_1.memoCreateSchema), (0, asyncHandler_1.asyncHandler)(memo_controller_1.create));
router.delete("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("memos.delete"), (0, validate_1.validate)(memo_schema_1.memoIdSchema), (0, asyncHandler_1.asyncHandler)(memo_controller_1.remove));
router.post("/bulk-delete", auth_1.authMiddleware, (0, auth_1.requirePermission)("memos.delete"), (0, validate_1.validate)(memo_schema_1.memoDeleteManySchema), (0, asyncHandler_1.asyncHandler)(memo_controller_1.removeMany));
exports.default = router;
//# sourceMappingURL=memo.route.js.map