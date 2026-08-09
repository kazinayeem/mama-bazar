import { Request, Response, NextFunction } from "express";
import jwt from "jsonwebtoken";
import { env } from "../config/env";

export const authMiddleware = (req: Request, res: Response, next: NextFunction) => {
  const authHeader = req.headers.authorization;
  if (!authHeader || !authHeader.startsWith("Bearer ")) {
    return res.status(401).json({ success: false, message: "Unauthorized" });
  }

  const token = authHeader.split(" ")[1];
  try {
    const decoded = jwt.verify(token, env.JWT_SECRET);
    (req as any).user = decoded;
    next();
  } catch {
    return res.status(401).json({ success: false, message: "Invalid token" });
  }
};

export const adminOnly = (req: Request, res: Response, next: NextFunction) => {
  if ((req as any).user?.role !== "admin") {
    return res.status(403).json({ success: false, message: "Admin access required" });
  }
  next();
};

// ==================== MANAGEMENT PERMISSIONS ====================
// The existing authorization system is role based (admin / manager / user).
// Management modules gate access through a named permission list that is
// derived from the authenticated user's role, so admins and managers get
// differentiated access without introducing a separate permission table.
export type ManagementPermission =
  | "expenses.view"
  | "expenses.create"
  | "expenses.update"
  | "expenses.delete"
  | "costs.view"
  | "costs.create"
  | "costs.update"
  | "costs.delete"
  | "bookings.view"
  | "bookings.create"
  | "bookings.update"
  | "bookings.delete"
  | "rentals.view"
  | "rentals.create"
  | "rentals.update"
  | "rentals.delete"
  | "memos.view"
  | "memos.upload"
  | "memos.delete"
  | "reports.view"
  | "reports.export";

const ROLE_PERMISSIONS: Record<string, ManagementPermission[]> = {
  admin: [
    "expenses.view",
    "expenses.create",
    "expenses.update",
    "expenses.delete",
    "costs.view",
    "costs.create",
    "costs.update",
    "costs.delete",
    "bookings.view",
    "bookings.create",
    "bookings.update",
    "bookings.delete",
    "rentals.view",
    "rentals.create",
    "rentals.update",
    "rentals.delete",
    "memos.view",
    "memos.upload",
    "memos.delete",
    "reports.view",
    "reports.export",
  ],
  manager: [
    "expenses.view",
    "expenses.create",
    "expenses.update",
    "costs.view",
    "costs.create",
    "costs.update",
    "bookings.view",
    "bookings.create",
    "bookings.update",
    "rentals.view",
    "rentals.create",
    "rentals.update",
    "memos.view",
    "memos.upload",
    "reports.view",
  ],
  user: [],
};

export const hasManagementPermission = (role: string | undefined, permission: ManagementPermission): boolean => {
  const perms = ROLE_PERMISSIONS[role || ""] || [];
  return perms.includes(permission);
};

export const requirePermission =
  (permission: ManagementPermission) => (req: Request, res: Response, next: NextFunction) => {
    const role = (req as any).user?.role;
    if (!hasManagementPermission(role, permission)) {
      return res.status(403).json({ success: false, message: "Insufficient permissions", data: { permission } });
    }
    next();
  };
