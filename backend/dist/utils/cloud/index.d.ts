export declare const cloudinaryConfigured: boolean;
export interface UploadedFileResult {
    url: string;
    publicId?: string;
    provider: "cloudinary" | "local";
    width?: number;
    height?: number;
}
/**
 * Upload a buffer to Cloudinary. Falls back to saving locally when
 * Cloudinary is not configured.
 */
export declare const uploadBuffer: (buffer: Buffer, opts: {
    folder: string;
    filename: string;
    mimeType: string;
}) => Promise<UploadedFileResult>;
/**
 * Delete an asset by Cloudinary public id, or a local file path.
 */
export declare const deleteAsset: (publicId?: string, url?: string) => Promise<{
    deleted: boolean;
    provider: string;
} | {
    deleted: boolean;
    provider?: undefined;
}>;
export declare const cloudinaryConfig: {
    configured: boolean;
    cloudName: string | null;
};
//# sourceMappingURL=index.d.ts.map