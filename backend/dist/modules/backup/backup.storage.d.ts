/**
 * BackupStorage — cloud-agnostic storage abstraction for backup archives.
 *
 * Production (Vercel):  Cloudinary (reuses existing CLOUDINARY_* credentials)
 * Development (local):  /tmp/backups — only valid within the current process run
 *
 * The backup service never knows which adapter is active; it simply calls
 * upload / download / delete / getDownloadUrl.
 */
export interface BackupUploadResult {
    /** Stable identifier for this backup — Cloudinary public_id or local filename */
    storageKey: string;
    /** Bytes of the stored archive */
    size: number;
}
export declare const backupStorage: {
    readonly provider: "cloudinary" | "local";
    upload(buffer: Buffer, filename: string): Promise<BackupUploadResult>;
    download(storageKey: string): Promise<Buffer>;
    delete(storageKey: string): Promise<void>;
    getDownloadUrl(storageKey: string): string;
    isCloudinary(): boolean;
};
//# sourceMappingURL=backup.storage.d.ts.map