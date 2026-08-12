"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const cost_schema_1 = require("./cost.schema");
const cost_controller_1 = require("./cost.controller");
const router = (0, express_1.Router)();
router.get("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("costs.view"), (0, validate_1.validate)(cost_schema_1.costListSchema), (0, asyncHandler_1.asyncHandler)(cost_controller_1.list));
router.get("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("costs.view"), (0, validate_1.validate)(cost_schema_1.costIdSchema), (0, asyncHandler_1.asyncHandler)(cost_controller_1.getById));
router.post("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("costs.create"), (0, validate_1.validate)(cost_schema_1.costCreateSchema), (0, asyncHandler_1.asyncHandler)(cost_controller_1.create));
router.put("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("costs.update"), (0, validate_1.validate)(cost_schema_1.costUpdateSchema), (0, asyncHandler_1.asyncHandler)(cost_controller_1.update));
router.delete("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("costs.delete"), (0, validate_1.validate)(cost_schema_1.costIdSchema), (0, asyncHandler_1.asyncHandler)(cost_controller_1.remove));
exports.default = router;
//# sourceMappingURL=cost.route.js.map