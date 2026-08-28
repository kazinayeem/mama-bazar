/**
 * BackupStorage — cloud-agnostic storage abstraction for backup archives.
 *
 * Production (Vercel):  Cloudinary (reuses existing CLOUDINARY_* credentials)
 * Development (local):  /tmp/backups or project-local storage/backups
 *
 * The backup service only calls: upload / download / delete.
 * Downloads ALWAYS go through the backend (no client-side cross-origin signed URLs).
 */
export interface BackupUploadResult {
    /** Stable identifier — Cloudinary public_id or absolute local filesystem path */
    storageKey: string;
    /** Byte size of the stored archive */
    size: number;
}
export declare const backupStorage: {
    readonly provider: "cloudinary" | "local";
    isCloudinary(): boolean;
    upload(buffer: Buffer, filename: string): Promise<BackupUploadResult>;
    /** Always returns the raw binary ZIP buffer — the controller streams it to the client. */
    download(storageKey: string): Promise<Buffer>;
    delete(storageKey: string): Promise<void>;
};
//# sourceMappingURL=backup.storage.d.ts.map