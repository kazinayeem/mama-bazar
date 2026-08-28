"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.getAuditLogs = exports.logAuditEvent = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const logAuditEvent = async (input) => {
    try {
        const detailsStr = typeof input.details === "object" && input.details !== null
            ? JSON.stringify(input.details)
            : input.details || null;
        await db_1.db.insert(schema_1.adminAuditLogs).values({
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
    }
    catch (err) {
        console.error("Failed to write audit log:", err);
    }
};
exports.logAuditEvent = logAuditEvent;
const getAuditLogs = async (limit = 100) => {
    return db_1.db
        .select()
        .from(schema_1.adminAuditLogs)
        .orderBy((0, drizzle_orm_1.desc)(schema_1.adminAuditLogs.createdAt))
        .limit(limit);
};
exports.getAuditLogs = getAuditLogs;
//# sourceMappingURL=audit.service.js.map