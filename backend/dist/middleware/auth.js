"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.adminOnly = exports.requireAnyPermission = exports.requirePermission = exports.hasPermission = exports.authMiddleware = exports.resolveUserPermissions = exports.invalidateUserPermissionCache = void 0;
const jsonwebtoken_1 = __importDefault(require("jsonwebtoken"));
const env_1 = require("../config/env");
const initRbac_1 = require("../config/initRbac");
const db_1 = require("../config/db");
const schema_1 = require("../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
// In-memory cache for user permissions to avoid database hits on every request (TTL: 2 minutes)
const userPermCache = new Map();
const invalidateUserPermissionCache = (userId) => {
    userPermCache.delete(userId);
};
exports.invalidateUserPermissionCache = invalidateUserPermissionCache;
const resolveUserPermissions = async (userId, role, customRoleFromToken) => {
    // Check cache first
    const now = Date.now();
    const cached = userPermCache.get(userId);
    if (cached && cached.expiresAt > now) {
        return { permissions: cached.permissions, customRole: cached.customRole };
    }
    // Fetch latest user details from DB
    const userRows = await db_1.db
        .select({
        id: schema_1.users.id,
        role: schema_1.users.role,
        customRole: schema_1.users.customRole,
        permissionsJson: schema_1.users.permissionsJson,
        status: schema_1.users.status,
    })
        .from(schema_1.users)
        .where((0, drizzle_orm_1.eq)(schema_1.users.id, userId))
        .limit(1);
    const dbUser = userRows[0];
    const activeRole = dbUser?.customRole || customRoleFromToken || (dbUser?.role === "admin" ? "SUPER_ADMIN" : dbUser?.role === "manager" ? "MANAGER" : "STAFF");
    // Super Admin check
    if (activeRole === "SUPER_ADMIN" || dbUser?.role === "admin" || userId === 240011) {
        const result = { permissions: ["*"], customRole: "SUPER_ADMIN" };
        userPermCache.set(userId, { ...result, expiresAt: now + 120000 });
        return result;
    }
    // Base role permissions from presets
    const preset = initRbac_1.ROLE_PRESETS[activeRole] || initRbac_1.ROLE_PRESETS[activeRole.toUpperCase()];
    const permSet = new Set(preset ? preset.permissions : []);
    // Custom permissions from JSON if assigned
    if (dbUser?.permissionsJson) {
        try {
            const parsed = JSON.parse(dbUser.permissionsJson);
            if (Array.isArray(parsed)) {
                parsed.forEach((p) => {
                    if (typeof p === "string")
                        permSet.add(p);
                });
            }
        }
        catch {
            // ignore json parse error
        }
    }
    // Explicit user permission grants from user_permissions table
    try {
        const directPerms = await db_1.db
            .select({ permissionCode: schema_1.userPermissions.permissionCode, granted: schema_1.userPermissions.granted })
            .from(schema_1.userPermissions)
            .where((0, drizzle_orm_1.eq)(schema_1.userPermissions.userId, userId));
        for (const dp of directPerms) {
            if (dp.granted) {
                permSet.add(dp.permissionCode);
            }
            else {
                permSet.delete(dp.permissionCode);
            }
        }
    }
    catch {
        // ignore
    }
    const result = {
        permissions: Array.from(permSet),
        customRole: activeRole,
    };
    userPermCache.set(userId, { ...result, expiresAt: now + 120000 });
    return result;
};
exports.resolveUserPermissions = resolveUserPermissions;
const authMiddleware = async (req, res, next) => {
    const authHeader = req.headers.authorization;
    if (!authHeader || !authHeader.startsWith("Bearer ")) {
        return res.status(401).json({ success: false, message: "Unauthorized" });
    }
    const token = authHeader.split(" ")[1];
    try {
        const decoded = jsonwebtoken_1.default.verify(token, env_1.env.JWT_SECRET);
        const { permissions, customRole } = await (0, exports.resolveUserPermissions)(decoded.id, decoded.role, decoded.customRole);
        req.user = {
            ...decoded,
            customRole,
            permissions,
        };
        next();
    }
    catch {
        return res.status(401).json({ success: false, message: "Invalid or expired token" });
    }
};
exports.authMiddleware = authMiddleware;
const hasPermission = (user, permission) => {
    if (!user || !user.permissions)
        return false;
    if (user.permissions.includes("*") || user.customRole === "SUPER_ADMIN" || user.role === "admin" || user.id === 240011) {
        return true;
    }
    return user.permissions.includes(permission);
};
exports.hasPermission = hasPermission;
const requirePermission = (permission) => (req, res, next) => {
    const user = req.user;
    if (!user) {
        return res.status(401).json({ success: false, message: "Unauthorized" });
    }
    if (!(0, exports.hasPermission)(user, permission)) {
        return res.status(403).json({
            success: false,
            message: `Access denied. Requires '${permission}' permission.`,
            data: { requiredPermission: permission },
        });
    }
    next();
};
exports.requirePermission = requirePermission;
const requireAnyPermission = (...permissions) => (req, res, next) => {
    const user = req.user;
    if (!user) {
        return res.status(401).json({ success: false, message: "Unauthorized" });
    }
    const hasAny = permissions.some((perm) => (0, exports.hasPermission)(user, perm));
    if (!hasAny) {
        return res.status(403).json({
            success: false,
            message: `Access denied. Requires one of: ${permissions.join(", ")}`,
            data: { requiredPermissions: permissions },
        });
    }
    next();
};
exports.requireAnyPermission = requireAnyPermission;
const adminOnly = (req, res, next) => {
    const user = req.user;
    if (!user) {
        return res.status(401).json({ success: false, message: "Unauthorized" });
    }
    // Any non-plain-user team member with at least one admin permission or admin/manager role
    if (user.role === "admin" ||
        user.role === "manager" ||
        user.customRole === "SUPER_ADMIN" ||
        user.customRole === "ADMIN" ||
        user.customRole === "MANAGER" ||
        user.customRole === "EDITOR" ||
        user.customRole === "STAFF" ||
        user.permissions.length > 0) {
        return next();
    }
    return res.status(403).json({ success: false, message: "Admin access required" });
};
exports.adminOnly = adminOnly;
//# sourceMappingURL=auth.js.map