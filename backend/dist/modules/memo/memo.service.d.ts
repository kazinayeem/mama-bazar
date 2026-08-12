export interface MemoQuery {
    page?: number;
    limit?: number;
    search?: string;
    entityType?: string;
    folder?: string;
}
export declare const listMemos: (query: MemoQuery) => Promise<{
    data: {
        uploadedByName: string | null;
        id: number;
        title: string | null;
        entityType: string;
        entityId: number | null;
        url: string;
        publicId: string | null;
        filename: string;
        mimeType: string;
        size: number;
        folder: string;
        notes: string | null;
        uploadedById: number | null;
        createdAt: Date;
    }[];
    pagination: {
        page: number;
        limit: number;
        total: number;
        totalPages: number;
    };
}>;
export declare const getMemo: (id: number) => Promise<{
    uploadedByName: string | null;
    id: number;
    title: string | null;
    entityType: string;
    entityId: number | null;
    url: string;
    publicId: string | null;
    filename: string;
    mimeType: string;
    size: number;
    folder: string;
    notes: string | null;
    uploadedById: number | null;
    createdAt: Date;
}>;
export declare const createMemo: (input: {
    title: string;
    entityType: string;
    entityId?: number | null;
    url: string;
    publicId: string;
    filename: string;
    mimeType: string;
    size?: number;
    folder?: string;
    notes?: string | null;
    uploadedById?: number | null;
}) => Promise<import("mysql2").ResultSetHeader>;
export declare const deleteMemo: (id: number) => Promise<{
    success: boolean;
}>;
export declare const deleteManyMemos: (ids: number[]) => Promise<{
    success: boolean;
    deleted: number;
}>;
//# sourceMappingURL=memo.service.d.ts.map