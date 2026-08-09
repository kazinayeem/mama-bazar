export interface CreateMediaInput {
    buffer: Buffer;
    filename: string;
    mimeType: string;
    size: number;
    folder: string;
    alt?: string;
    uploaderId?: number;
}
export interface MediaQuery {
    page?: number;
    limit?: number;
    folder?: string;
    search?: string;
}
//# sourceMappingURL=media.interface.d.ts.map