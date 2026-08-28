"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.deleteMember = exports.updateMember = exports.createMember = exports.countActiveSuperAdmins = exports.listMembers = exports.getRolesAndPermissions = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const bcryptjs_1 = __importDefault(require("bcryptjs"));
const AppError_1 = require("../../utils/AppError");
const initRbac_1 = require("../../config/initRbac");
const auth_1 = require("../../middleware/auth");
const audit_service_1 = require("../admin/audit.service");
const SALT_ROUNDS = 10;
const getRolesAndPermissions = async () => {
    return {
        permissions: initRbac_1.ALL_PERMISSIONS,
        roles: Object.entries(initRbac_1.ROLE_PRESETS).map(([name, preset]) => ({
            name,
            displayName: preset.displayName,
            description: preset.description,
            permissions: preset.permissions,
        })),
    };
};
exports.getRolesAndPermissions = getRolesAndPermissions;
const listMembers = async () => {
    const members = await db_1.db
        .select({
        id: schema_1.users.id,
        name: schema_1.users.name,
        phone: schema_1.users.phone,
        email: schema_1.users.email,
        role: schema_1.users.role,
        customRole: schema_1.users.customRole,
        status: schema_1.users.status,
        permissionsJson: schema_1.users.permissionsJson,
        lastLoginAt: schema_1.users.lastLoginAt,
        createdAt: schema_1.users.createdAt,
    })
        .from(schema_1.users)
        .where((0, drizzle_orm_1.or)((0, drizzle_orm_1.ne)(schema_1.users.role, "user"), (0, drizzle_orm_1.sql) `${schema_1.users.customRole} IS NOT NULL`))
        .orderBy((0, drizzle_orm_1.desc)(schema_1.users.createdAt));
    const result = await Promise.all(members.map(async (m) => {
        const { permissions, customRole } = await (0, auth_1.resolveUserPermissions)(m.id, m.role, m.customRole || undefined);
        return {
            id: m.id,
            name: m.name,
            phone: m.phone,
            email: m.email,
            role: m.role,
            customRole,
            status: m.status,
            permissions,
            lastLoginAt: m.lastLoginAt,
            createdAt: m.createdAt,
        };
    }));
    return result;
};
exports.listMembers = listMembers;
const countActiveSuperAdmins = async (excludeId) => {
    const connection = await db_1.pool.getConnection();
    try {
        let query = "SELECT COUNT(*) as count FROM `users` WHERE (`custom_role` = 'SUPER_ADMIN' OR `role` = 'admin' OR `id` = 240011) AND `status` = 'active'";
        const params = [];
        if (excludeId) {
            query += " AND `id` != ?";
            params.push(excludeId);
        }
        const [rows] = await connection.query(query, params);
        return Number(rows[0]?.count || 0);
    }
    finally {
        connection.release();
    }
};
exports.countActiveSuperAdmins = countActiveSuperAdmins;
const createMember = async (input, actor) => {
    // Validate phone
    const existingByPhone = await db_1.db.select().from(schema_1.users).where((0, drizzle_orm_1.eq)(schema_1.users.phone, input.phone)).limit(1);
    if (existingByPhone[0]) {
        throw new AppError_1.AppError(409, "A user with this phone number already exists");
    }
    // Validate email if provided
    if (input.email) {
        const existingByEmail = await db_1.db.select().from(schema_1.users).where((0, drizzle_orm_1.eq)(schema_1.users.email, input.email)).limit(1);
        if (existingByEmail[0]) {
            throw new AppError_1.AppError(409, "A user with this email address already exists");
        }
    }
    const hashedPassword = await bcryptjs_1.default.hash(input.password, SALT_ROUNDS);
    const normalizedRole = input.role.toUpperCase();
    const legacyRole = normalizedRole === "SUPER_ADMIN" || normalizedRole === "ADMIN" ? "admin" : "manager";
    const permissionsJson = input.permissions ? JSON.stringify(input.permissions) : null;
    const result = await db_1.db.insert(schema_1.users).values({
        name: input.name,
        phone: input.phone,
        email: input.email || null,
        password: hashedPassword,
        role: legacyRole,
        customRole: normalizedRole,
        permissionsJson,
        status: input.status || "active",
    });
    const memberId = result[0].insertId;
    // Insert user permissions if provided
    if (input.permissions && input.permissions.length > 0) {
        for (const perm of input.permissions) {
            await db_1.db.insert(schema_1.userPermissions).values({
                userId: memberId,
                permissionCode: perm,
                granted: true,
            });
        }
    }
    (0, auth_1.invalidateUserPermissionCache)(memberId);
    await (0, audit_service_1.logAuditEvent)({
        actorId: actor?.id || null,
        actorName: actor?.name || "Super Admin",
        actorEmail: actor?.email || null,
        action: "MEMBER_CREATED",
        targetType: "User",
        targetId: String(memberId),
        details: { name: input.name, phone: input.phone, role: normalizedRole },
        ipAddress: actor?.ip,
        userAgent: actor?.userAgent,
    });
    const { permissions, customRole } = await (0, auth_1.resolveUserPermissions)(memberId, legacyRole, normalizedRole);
    return {
        id: memberId,
        name: input.name,
        phone: input.phone,
        email: input.email,
        role: legacyRole,
        customRole,
        status: input.status || "active",
        permissions,
    };
};
exports.createMember = createMember;
const updateMember = async (id, input, actor) => {
    const existingRows = await db_1.db.select().from(schema_1.users).where((0, drizzle_orm_1.eq)(schema_1.users.id, id)).limit(1);
    const existing = existingRows[0];
    if (!existing) {
        throw new AppError_1.AppError(404, "Member not found");
    }
    const isSuperAdmin = existing.customRole === "SUPER_ADMIN" || existing.role === "admin" || existing.id === 240011;
    // Safety check: Prevent deactivating or demoting the last Super Admin
    if (isSuperAdmin) {
        const isDemoting = input.role && input.role.toUpperCase() !== "SUPER_ADMIN";
        const isDeactivating = input.status === "inactive";
        if (isDemoting || isDeactivating) {
            const remainingSuperAdmins = await (0, exports.countActiveSuperAdmins)(id);
            if (remainingSuperAdmins === 0) {
                throw new AppError_1.AppError(400, "Action blocked: Cannot deactivate or demote the only active Super Admin");
            }
        }
    }
    // Check phone uniqueness
    if (input.phone && input.phone !== existing.phone) {
        const phoneCheck = await db_1.db.select().from(schema_1.users).where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.users.phone, input.phone), (0, drizzle_orm_1.ne)(schema_1.users.id, id))).limit(1);
        if (phoneCheck[0])
            throw new AppError_1.AppError(409, "Phone number is already in use by another account");
    }
    // Check email uniqueness
    if (input.email && input.email !== existing.email) {
        const emailCheck = await db_1.db.select().from(schema_1.users).where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.users.email, input.email), (0, drizzle_orm_1.ne)(schema_1.users.id, id))).limit(1);
        if (emailCheck[0])
            throw new AppError_1.AppError(409, "Email address is already in use by another account");
    }
    const updateData = {};
    if (input.name)
        updateData.name = input.name;
    if (input.phone)
        updateData.phone = input.phone;
    if (input.email !== undefined)
        updateData.email = input.email || null;
    if (input.status)
        updateData.status = input.status;
    if (input.role) {
        const normalizedRole = input.role.toUpperCase();
        updateData.customRole = normalizedRole;
        updateData.role = normalizedRole === "SUPER_ADMIN" || normalizedRole === "ADMIN" ? "admin" : "manager";
    }
    if (input.password && input.password.trim()) {
        updateData.password = await bcryptjs_1.default.hash(input.password, SALT_ROUNDS);
    }
    if (input.permissions !== undefined) {
        updateData.permissionsJson = JSON.stringify(input.permissions);
        // Sync direct user permissions table
        await db_1.db.delete(schema_1.userPermissions).where((0, drizzle_orm_1.eq)(schema_1.userPermissions.userId, id));
        for (const perm of input.permissions) {
            await db_1.db.insert(schema_1.userPermissions).values({
                userId: id,
                permissionCode: perm,
                granted: true,
            });
        }
    }
    await db_1.db.update(schema_1.users).set(updateData).where((0, drizzle_orm_1.eq)(schema_1.users.id, id));
    (0, auth_1.invalidateUserPermissionCache)(id);
    await (0, audit_service_1.logAuditEvent)({
        actorId: actor?.id || null,
        actorName: actor?.name || "Super Admin",
        actorEmail: actor?.email || null,
        action: "MEMBER_UPDATED",
        targetType: "User",
        targetId: String(id),
        details: { name: input.name || existing.name, role: input.role || existing.customRole, status: input.status || existing.status },
        ipAddress: actor?.ip,
        userAgent: actor?.userAgent,
    });
    const updatedRows = await db_1.db.select().from(schema_1.users).where((0, drizzle_orm_1.eq)(schema_1.users.id, id)).limit(1);
    const updated = updatedRows[0];
    const { permissions, customRole } = await (0, auth_1.resolveUserPermissions)(id, updated.role, updated.customRole || undefined);
    return {
        id: updated.id,
        name: updated.name,
        phone: updated.phone,
        email: updated.email,
        role: updated.role,
        customRole,
        status: updated.status,
        permissions,
        lastLoginAt: updated.lastLoginAt,
        createdAt: updated.createdAt,
    };
};
exports.updateMember = updateMember;
const deleteMember = async (id, actor) => {
    const existingRows = await db_1.db.select().from(schema_1.users).where((0, drizzle_orm_1.eq)(schema_1.users.id, id)).limit(1);
    const existing = existingRows[0];
    if (!existing) {
        throw new AppError_1.AppError(404, "Member not found");
    }
    const isSuperAdmin = existing.customRole === "SUPER_ADMIN" || existing.role === "admin" || existing.id === 240011;
    // Safety check: Prevent deleting the only Super Admin
    if (isSuperAdmin) {
        const remainingSuperAdmins = await (0, exports.countActiveSuperAdmins)(id);
        if (remainingSuperAdmins === 0) {
            throw new AppError_1.AppError(400, "Action blocked: Cannot delete the only active Super Admin");
        }
    }
    // Prevent deleting oneself
    if (actor?.id && actor.id === id) {
        throw new AppError_1.AppError(400, "Action blocked: You cannot delete your own active account");
    }
    await db_1.db.delete(schema_1.userPermissions).where((0, drizzle_orm_1.eq)(schema_1.userPermissions.userId, id));
    await db_1.db.delete(schema_1.users).where((0, drizzle_orm_1.eq)(schema_1.users.id, id));
    (0, auth_1.invalidateUserPermissionCache)(id);
    await (0, audit_service_1.logAuditEvent)({
        actorId: actor?.id || null,
        actorName: actor?.name || "Super Admin",
        actorEmail: actor?.email || null,
        action: "MEMBER_DELETED",
        targetType: "User",
        targetId: String(id),
        details: { name: existing.name, phone: existing.phone },
        ipAddress: actor?.ip,
        userAgent: actor?.userAgent,
    });
    return { success: true };
};
exports.deleteMember = deleteMember;
//# sourceMappingURL=member.service.js.map