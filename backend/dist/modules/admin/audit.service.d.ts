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
export declare const logAuditEvent: (input: AuditLogInput) => Promise<void>;
export declare const getAuditLogs: (limit?: number) => Promise<{
    id: number;
    status: "success" | "failure";
    createdAt: Date;
    actorId: number | null;
    actorName: string;
    actorEmail: string | null;
    action: string;
    targetType: string | null;
    targetId: string | null;
    details: string | null;
    ipAddress: string | null;
    userAgent: string | null;
}[]>;
//# sourceMappingURL=audit.service.d.ts.map