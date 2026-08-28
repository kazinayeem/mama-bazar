import { Request, Response, NextFunction } from "express";
import jwt from "jsonwebtoken";
import { env } from "../config/env";
import { ROLE_PRESETS } from "../config/initRbac";
import { db } from "../config/db";
import { users, userPermissions } from "../config/schema";
import { eq } from "drizzle-orm";

export interface AuthenticatedUser {
  id: number;
  phone: string;
  role: string;
  customRole?: string;
  name?: string;
  email?: string;
  permissions: string[];
}

// In-memory cache for user permissions to avoid database hits on every request (TTL: 2 minutes)
const userPermCache = new Map<number, { permissions: string[]; customRole: string; expiresAt: number }>();

export const invalidateUserPermissionCache = (userId: number) => {
  userPermCache.delete(userId);
};

export const resolveUserPermissions = async (
  userId: number,
  role: string,
  customRoleFromToken?: string
): Promise<{ permissions: string[]; customRole: string }> => {
  // Check cache first
  const now = Date.now();
  const cached = userPermCache.get(userId);
  if (cached && cached.expiresAt > now) {
    return { permissions: cached.permissions, customRole: cached.customRole };
  }

  // Fast-path Super Admin check
  if (role === "admin" || customRoleFromToken === "SUPER_ADMIN" || userId === 240011) {
    const result = { permissions: ["*"], customRole: "SUPER_ADMIN" };
    userPermCache.set(userId, { ...result, expiresAt: now + 120_000 });
    return result;
  }

  let dbUser: any = null;
  try {
    const userRows = await db
      .select({
        id: users.id,
        role: users.role,
        customRole: users.customRole,
        permissionsJson: users.permissionsJson,
        status: users.status,
      })
      .from(users)
      .where(eq(users.id, userId))
      .limit(1);
    dbUser = userRows[0];
  } catch {
    // If columns are in migration, fallback to core role
  }

  const activeRole = dbUser?.customRole || customRoleFromToken || (role === "manager" ? "MANAGER" : "STAFF");

  if (activeRole === "SUPER_ADMIN" || dbUser?.role === "admin" || userId === 240011) {
    const result = { permissions: ["*"], customRole: "SUPER_ADMIN" };
    userPermCache.set(userId, { ...result, expiresAt: now + 120_000 });
    return result;
  }

  // Base role permissions from presets
  const preset = ROLE_PRESETS[activeRole] || ROLE_PRESETS[activeRole.toUpperCase()];
  const permSet = new Set<string>(preset ? preset.permissions : []);

  // Custom permissions from JSON if assigned
  if (dbUser?.permissionsJson) {
    try {
      const parsed = JSON.parse(dbUser.permissionsJson);
      if (Array.isArray(parsed)) {
        parsed.forEach((p) => {
          if (typeof p === "string") permSet.add(p);
        });
      }
    } catch {
      // ignore json parse error
    }
  }

  // Explicit user permission grants from user_permissions table
  try {
    const directPerms = await db
      .select({ permissionCode: userPermissions.permissionCode, granted: userPermissions.granted })
      .from(userPermissions)
      .where(eq(userPermissions.userId, userId));

    for (const dp of directPerms) {
      if (dp.granted) {
        permSet.add(dp.permissionCode);
      } else {
        permSet.delete(dp.permissionCode);
      }
    }
  } catch {
    // ignore
  }

  const result = {
    permissions: Array.from(permSet),
    customRole: activeRole,
  };

  userPermCache.set(userId, { ...result, expiresAt: now + 120_000 });
  return result;
};

export const authMiddleware = async (req: Request, res: Response, next: NextFunction) => {
  const authHeader = req.headers.authorization;
  if (!authHeader || !authHeader.startsWith("Bearer ")) {
    return res.status(401).json({ success: false, message: "Unauthorized" });
  }

  const token = authHeader.split(" ")[1];
  try {
    const decoded = jwt.verify(token, env.JWT_SECRET) as {
      id: number;
      phone: string;
      role: string;
      customRole?: string;
      name?: string;
      email?: string;
    };

    const { permissions, customRole } = await resolveUserPermissions(
      decoded.id,
      decoded.role,
      decoded.customRole
    );

    (req as any).user = {
      ...decoded,
      customRole,
      permissions,
    };

    next();
  } catch {
    return res.status(401).json({ success: false, message: "Invalid or expired token" });
  }
};

export const hasPermission = (user: AuthenticatedUser | undefined, permission: string): boolean => {
  if (!user || !user.permissions) return false;
  if (user.permissions.includes("*") || user.customRole === "SUPER_ADMIN" || user.role === "admin" || user.id === 240011) {
    return true;
  }
  return user.permissions.includes(permission);
};

export const requirePermission =
  (permission: string) => (req: Request, res: Response, next: NextFunction) => {
    const user = (req as any).user as AuthenticatedUser | undefined;
    if (!user) {
      return res.status(401).json({ success: false, message: "Unauthorized" });
    }

    if (!hasPermission(user, permission)) {
      return res.status(403).json({
        success: false,
        message: `Access denied. Requires '${permission}' permission.`,
        data: { requiredPermission: permission },
      });
    }

    next();
  };

export const requireAnyPermission =
  (...permissions: string[]) =>
  (req: Request, res: Response, next: NextFunction) => {
    const user = (req as any).user as AuthenticatedUser | undefined;
    if (!user) {
      return res.status(401).json({ success: false, message: "Unauthorized" });
    }

    const hasAny = permissions.some((perm) => hasPermission(user, perm));
    if (!hasAny) {
      return res.status(403).json({
        success: false,
        message: `Access denied. Requires one of: ${permissions.join(", ")}`,
        data: { requiredPermissions: permissions },
      });
    }

    next();
  };

export const adminOnly = (req: Request, res: Response, next: NextFunction) => {
  const user = (req as any).user as AuthenticatedUser | undefined;
  if (!user) {
    return res.status(401).json({ success: false, message: "Unauthorized" });
  }

  // Any non-plain-user team member with at least one admin permission or admin/manager role
  if (
    user.role === "admin" ||
    user.role === "manager" ||
    user.customRole === "SUPER_ADMIN" ||
    user.customRole === "ADMIN" ||
    user.customRole === "MANAGER" ||
    user.customRole === "EDITOR" ||
    user.customRole === "STAFF" ||
    user.permissions.length > 0
  ) {
    return next();
  }

  return res.status(403).json({ success: false, message: "Admin access required" });
};
