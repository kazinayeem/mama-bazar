export declare const TABLE_RESTORE_ORDER: string[];
export declare const listBackups: () => Promise<{
    id: number;
    createdAt: Date;
    type: "manual" | "safety_auto";
    size: number;
    filename: string;
    createdById: number | null;
    filepath: string;
    tableCount: number;
    recordCount: number;
}[]>;
export declare const getBackupById: (id: number) => Promise<{
    id: number;
    createdAt: Date;
    type: "manual" | "safety_auto";
    size: number;
    filename: string;
    createdById: number | null;
    filepath: string;
    tableCount: number;
    recordCount: number;
}>;
export declare const createBackup: (options?: {
    type?: "manual" | "safety_auto";
    createdById?: number | null;
    actorName?: string;
    actorEmail?: string;
    ip?: string;
    userAgent?: string;
}) => Promise<{
    id: any;
    filename: string;
    size: number;
    type: "manual" | "safety_auto";
    tableCount: number;
    recordCount: number;
    createdAt: Date;
}>;
export declare const restoreBackup: (fileBuffer: Buffer, actor?: {
    id?: number;
    name?: string;
    email?: string;
    ip?: string;
    userAgent?: string;
}) => Promise<{
    success: boolean;
    restoredTablesCount: number;
    restoredRecordsCount: number;
    safetyBackupFilename: string;
}>;
export declare const deleteBackup: (id: number, actor?: {
    id?: number;
    name?: string;
    email?: string;
    ip?: string;
    userAgent?: string;
}) => Promise<{
    success: boolean;
}>;
//# sourceMappingURL=backup.service.d.ts.map