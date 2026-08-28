import { db, pool } from "../../config/db";
import { users, userPermissions } from "../../config/schema";
import { eq, or, and, ne, sql, desc } from "drizzle-orm";
import bcrypt from "bcryptjs";
import { AppError } from "../../utils/AppError";
import { ALL_PERMISSIONS, ROLE_PRESETS } from "../../config/initRbac";
import { invalidateUserPermissionCache, resolveUserPermissions } from "../../middleware/auth";
import { logAuditEvent } from "../admin/audit.service";
import { RowDataPacket } from "mysql2";

const SALT_ROUNDS = 10;

export interface CreateMemberInput {
  name: string;
  phone: string;
  email?: string;
  password: string;
  role: string;
  status?: "active" | "inactive";
  permissions?: string[];
}

export interface UpdateMemberInput {
  name?: string;
  phone?: string;
  email?: string;
  password?: string;
  role?: string;
  status?: "active" | "inactive";
  permissions?: string[];
}

export const getRolesAndPermissions = async () => {
  return {
    permissions: ALL_PERMISSIONS,
    roles: Object.entries(ROLE_PRESETS).map(([name, preset]) => ({
      name,
      displayName: preset.displayName,
      description: preset.description,
      permissions: preset.permissions,
    })),
  };
};

export const listMembers = async () => {
  const members = await db
    .select({
      id: users.id,
      name: users.name,
      phone: users.phone,
      email: users.email,
      role: users.role,
      customRole: users.customRole,
      status: users.status,
      permissionsJson: users.permissionsJson,
      lastLoginAt: users.lastLoginAt,
      createdAt: users.createdAt,
    })
    .from(users)
    .where(or(ne(users.role, "user"), sql`${users.customRole} IS NOT NULL`))
    .orderBy(desc(users.createdAt));

  const result = await Promise.all(
    members.map(async (m) => {
      const { permissions, customRole } = await resolveUserPermissions(
        m.id,
        m.role,
        m.customRole || undefined
      );
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
    })
  );

  return result;
};

export const countActiveSuperAdmins = async (excludeId?: number): Promise<number> => {
  const connection = await pool.getConnection();
  try {
    let query = "SELECT COUNT(*) as count FROM `users` WHERE (`custom_role` = 'SUPER_ADMIN' OR `role` = 'admin' OR `id` = 240011) AND `status` = 'active'";
    const params: any[] = [];
    if (excludeId) {
      query += " AND `id` != ?";
      params.push(excludeId);
    }
    const [rows] = await connection.query<RowDataPacket[]>(query, params);
    return Number(rows[0]?.count || 0);
  } finally {
    connection.release();
  }
};

export const createMember = async (
  input: CreateMemberInput,
  actor?: { id?: number; name?: string; email?: string; ip?: string; userAgent?: string }
) => {
  // Validate phone
  const existingByPhone = await db.select().from(users).where(eq(users.phone, input.phone)).limit(1);
  if (existingByPhone[0]) {
    throw new AppError(409, "A user with this phone number already exists");
  }

  // Validate email if provided
  if (input.email) {
    const existingByEmail = await db.select().from(users).where(eq(users.email, input.email)).limit(1);
    if (existingByEmail[0]) {
      throw new AppError(409, "A user with this email address already exists");
    }
  }

  const hashedPassword = await bcrypt.hash(input.password, SALT_ROUNDS);
  const normalizedRole = input.role.toUpperCase();
  const legacyRole = normalizedRole === "SUPER_ADMIN" || normalizedRole === "ADMIN" ? "admin" : "manager";
  const permissionsJson = input.permissions ? JSON.stringify(input.permissions) : null;

  const result = await db.insert(users).values({
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
      await db.insert(userPermissions).values({
        userId: memberId,
        permissionCode: perm,
        granted: true,
      });
    }
  }

  invalidateUserPermissionCache(memberId);

  await logAuditEvent({
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

  const { permissions, customRole } = await resolveUserPermissions(memberId, legacyRole, normalizedRole);

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

export const updateMember = async (
  id: number,
  input: UpdateMemberInput,
  actor?: { id?: number; name?: string; email?: string; ip?: string; userAgent?: string }
) => {
  const existingRows = await db.select().from(users).where(eq(users.id, id)).limit(1);
  const existing = existingRows[0];
  if (!existing) {
    throw new AppError(404, "Member not found");
  }

  const isSuperAdmin = existing.customRole === "SUPER_ADMIN" || existing.role === "admin" || existing.id === 240011;

  // Safety check: Prevent deactivating or demoting the last Super Admin
  if (isSuperAdmin) {
    const isDemoting = input.role && input.role.toUpperCase() !== "SUPER_ADMIN";
    const isDeactivating = input.status === "inactive";
    if (isDemoting || isDeactivating) {
      const remainingSuperAdmins = await countActiveSuperAdmins(id);
      if (remainingSuperAdmins === 0) {
        throw new AppError(400, "Action blocked: Cannot deactivate or demote the only active Super Admin");
      }
    }
  }

  // Check phone uniqueness
  if (input.phone && input.phone !== existing.phone) {
    const phoneCheck = await db.select().from(users).where(and(eq(users.phone, input.phone), ne(users.id, id))).limit(1);
    if (phoneCheck[0]) throw new AppError(409, "Phone number is already in use by another account");
  }

  // Check email uniqueness
  if (input.email && input.email !== existing.email) {
    const emailCheck = await db.select().from(users).where(and(eq(users.email, input.email), ne(users.id, id))).limit(1);
    if (emailCheck[0]) throw new AppError(409, "Email address is already in use by another account");
  }

  const updateData: Record<string, any> = {};
  if (input.name) updateData.name = input.name;
  if (input.phone) updateData.phone = input.phone;
  if (input.email !== undefined) updateData.email = input.email || null;
  if (input.status) updateData.status = input.status;

  if (input.role) {
    const normalizedRole = input.role.toUpperCase();
    updateData.customRole = normalizedRole;
    updateData.role = normalizedRole === "SUPER_ADMIN" || normalizedRole === "ADMIN" ? "admin" : "manager";
  }

  if (input.password && input.password.trim()) {
    updateData.password = await bcrypt.hash(input.password, SALT_ROUNDS);
  }

  if (input.permissions !== undefined) {
    updateData.permissionsJson = JSON.stringify(input.permissions);

    // Sync direct user permissions table
    await db.delete(userPermissions).where(eq(userPermissions.userId, id));
    for (const perm of input.permissions) {
      await db.insert(userPermissions).values({
        userId: id,
        permissionCode: perm,
        granted: true,
      });
    }
  }

  await db.update(users).set(updateData).where(eq(users.id, id));
  invalidateUserPermissionCache(id);

  await logAuditEvent({
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

  const updatedRows = await db.select().from(users).where(eq(users.id, id)).limit(1);
  const updated = updatedRows[0];
  const { permissions, customRole } = await resolveUserPermissions(id, updated.role, updated.customRole || undefined);

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

export const deleteMember = async (
  id: number,
  actor?: { id?: number; name?: string; email?: string; ip?: string; userAgent?: string }
) => {
  const existingRows = await db.select().from(users).where(eq(users.id, id)).limit(1);
  const existing = existingRows[0];
  if (!existing) {
    throw new AppError(404, "Member not found");
  }

  const isSuperAdmin = existing.customRole === "SUPER_ADMIN" || existing.role === "admin" || existing.id === 240011;

  // Safety check: Prevent deleting the only Super Admin
  if (isSuperAdmin) {
    const remainingSuperAdmins = await countActiveSuperAdmins(id);
    if (remainingSuperAdmins === 0) {
      throw new AppError(400, "Action blocked: Cannot delete the only active Super Admin");
    }
  }

  // Prevent deleting oneself
  if (actor?.id && actor.id === id) {
    throw new AppError(400, "Action blocked: You cannot delete your own active account");
  }

  await db.delete(userPermissions).where(eq(userPermissions.userId, id));
  await db.delete(users).where(eq(users.id, id));
  invalidateUserPermissionCache(id);

  await logAuditEvent({
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
