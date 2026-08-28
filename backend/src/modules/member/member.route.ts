import { Router } from "express";
import {
  listMembers,
  createMember,
  updateMember,
  deleteMember,
  getRolesAndPermissions,
  listAuditLogs,
} from "./member.controller";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";

const router = Router();

// All member endpoints require authentication
router.use(authMiddleware);

// Roles & Permissions Catalog
router.get("/roles-permissions", requirePermission("members.view"), asyncHandler(getRolesAndPermissions));

// Audit Logs
router.get("/audit-logs", requirePermission("members.view"), asyncHandler(listAuditLogs));

// Member CRUD
router.get("/", requirePermission("members.view"), asyncHandler(listMembers));
router.post("/", requirePermission("members.create"), asyncHandler(createMember));
router.put("/:id", requirePermission("members.update"), asyncHandler(updateMember));
router.delete("/:id", requirePermission("members.delete"), asyncHandler(deleteMember));

export default router;
