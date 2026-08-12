import { z } from "zod";
export declare const memoListSchema: z.ZodObject<{
    query: z.ZodObject<{
        page: z.ZodOptional<z.ZodString>;
        limit: z.ZodOptional<z.ZodString>;
        search: z.ZodOptional<z.ZodString>;
        entityType: z.ZodOptional<z.ZodString>;
        folder: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        search?: string | undefined;
        folder?: string | undefined;
        entityType?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }, {
        search?: string | undefined;
        folder?: string | undefined;
        entityType?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        search?: string | undefined;
        folder?: string | undefined;
        entityType?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}, {
    query: {
        search?: string | undefined;
        folder?: string | undefined;
        entityType?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}>;
export declare const memoIdSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodEffects<z.ZodString, string, string>;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
}, "strip", z.ZodTypeAny, {
    params: {
        id: string;
    };
}, {
    params: {
        id: string;
    };
}>;
export declare const memoCreateSchema: z.ZodObject<{
    body: z.ZodObject<{
        title: z.ZodString;
        entityType: z.ZodString;
        entityId: z.ZodNullable<z.ZodOptional<z.ZodNumber>>;
        url: z.ZodString;
        publicId: z.ZodString;
        filename: z.ZodString;
        mimeType: z.ZodString;
        size: z.ZodDefault<z.ZodOptional<z.ZodNumber>>;
        folder: z.ZodOptional<z.ZodString>;
        notes: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        uploadedById: z.ZodNullable<z.ZodOptional<z.ZodNumber>>;
    }, "strip", z.ZodTypeAny, {
        title: string;
        size: number;
        url: string;
        publicId: string;
        filename: string;
        mimeType: string;
        entityType: string;
        notes?: string | null | undefined;
        folder?: string | undefined;
        entityId?: number | null | undefined;
        uploadedById?: number | null | undefined;
    }, {
        title: string;
        url: string;
        publicId: string;
        filename: string;
        mimeType: string;
        entityType: string;
        notes?: string | null | undefined;
        size?: number | undefined;
        folder?: string | undefined;
        entityId?: number | null | undefined;
        uploadedById?: number | null | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        title: string;
        size: number;
        url: string;
        publicId: string;
        filename: string;
        mimeType: string;
        entityType: string;
        notes?: string | null | undefined;
        folder?: string | undefined;
        entityId?: number | null | undefined;
        uploadedById?: number | null | undefined;
    };
}, {
    body: {
        title: string;
        url: string;
        publicId: string;
        filename: string;
        mimeType: string;
        entityType: string;
        notes?: string | null | undefined;
        size?: number | undefined;
        folder?: string | undefined;
        entityId?: number | null | undefined;
        uploadedById?: number | null | undefined;
    };
}>;
export declare const memoDeleteManySchema: z.ZodObject<{
    body: z.ZodObject<{
        ids: z.ZodArray<z.ZodNumber, "many">;
    }, "strip", z.ZodTypeAny, {
        ids: number[];
    }, {
        ids: number[];
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        ids: number[];
    };
}, {
    body: {
        ids: number[];
    };
}>;
//# sourceMappingURL=memo.schema.d.ts.map