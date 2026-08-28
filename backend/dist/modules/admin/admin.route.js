"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const admin_controller_1 = require("./admin.controller");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const router = (0, express_1.Router)();
// Admin only
router.get("/dashboard", auth_1.authMiddleware, (0, auth_1.requirePermission)("dashboard.view"), (0, asyncHandler_1.asyncHandler)(admin_controller_1.getDashboard));
exports.default = router;
//# sourceMappingURL=admin.route.js.map