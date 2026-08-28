"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const member_controller_1 = require("./member.controller");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const router = (0, express_1.Router)();
// All member endpoints require authentication
router.use(auth_1.authMiddleware);
// Roles & Permissions Catalog
router.get("/roles-permissions", (0, auth_1.requirePermission)("members.view"), (0, asyncHandler_1.asyncHandler)(member_controller_1.getRolesAndPermissions));
// Audit Logs
router.get("/audit-logs", (0, auth_1.requirePermission)("members.view"), (0, asyncHandler_1.asyncHandler)(member_controller_1.listAuditLogs));
// Member CRUD
router.get("/", (0, auth_1.requirePermission)("members.view"), (0, asyncHandler_1.asyncHandler)(member_controller_1.listMembers));
router.post("/", (0, auth_1.requirePermission)("members.create"), (0, asyncHandler_1.asyncHandler)(member_controller_1.createMember));
router.put("/:id", (0, auth_1.requirePermission)("members.update"), (0, asyncHandler_1.asyncHandler)(member_controller_1.updateMember));
router.delete("/:id", (0, auth_1.requirePermission)("members.delete"), (0, asyncHandler_1.asyncHandler)(member_controller_1.deleteMember));
exports.default = router;
//# sourceMappingURL=member.route.js.map