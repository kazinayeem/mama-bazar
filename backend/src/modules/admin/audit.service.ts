import { db } from "../../config/db";
import { adminAuditLogs } from "../../config/schema";
import { desc } from "drizzle-orm";

export interface AuditLogInput {
  actorId?: number | null;
  actorName: string;
  actorEmail?: string | null;
  action: string;
  targetType?: string | null;
  targetId?: string | null;
  details?: Record<string, unknown> | string | null;
  ipAddress?: string | null;
  userAgent?: string | null;
  status?: "success" | "failure";
}

export const logAuditEvent = async (input: AuditLogInput) => {
  try {
    const detailsStr =
      typeof input.details === "object" && input.details !== null
        ? JSON.stringify(input.details)
        : (input.details as string) || null;

    await db.insert(adminAuditLogs).values({
      actorId: input.actorId || null,
      actorName: input.actorName || "System",
      actorEmail: input.actorEmail || null,
      action: input.action,
      targetType: input.targetType || null,
      targetId: input.targetId ? String(input.targetId) : null,
      details: detailsStr,
      ipAddress: input.ipAddress || null,
      userAgent: input.userAgent ? input.userAgent.slice(0, 500) : null,
      status: input.status || "success",
    });
  } catch (err) {
    console.error("Failed to write audit log:", err);
  }
};

export const getAuditLogs = async (limit = 100) => {
  return db
    .select()
    .from(adminAuditLogs)
    .orderBy(desc(adminAuditLogs.createdAt))
    .limit(limit);
};
