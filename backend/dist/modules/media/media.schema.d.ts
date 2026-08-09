import { z } from "zod";
export declare const mediaUploadSchema: z.ZodObject<{
    body: z.ZodObject<{
        folder: z.ZodDefault<z.ZodOptional<z.ZodString>>;
        alt: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        folder: string;
        alt?: string | undefined;
    }, {
        folder?: string | undefined;
        alt?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        folder: string;
        alt?: string | undefined;
    };
}, {
    body: {
        folder?: string | undefined;
        alt?: string | undefined;
    };
}>;
export declare const mediaListSchema: z.ZodObject<{
    query: z.ZodObject<{
        page: z.ZodOptional<z.ZodString>;
        limit: z.ZodOptional<z.ZodString>;
        folder: z.ZodOptional<z.ZodString>;
        search: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        search?: string | undefined;
        folder?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }, {
        search?: string | undefined;
        folder?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        search?: string | undefined;
        folder?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}, {
    query: {
        search?: string | undefined;
        folder?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}>;
export declare const mediaIdSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodString;
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
export declare const mediaUpdateSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        alt: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        alt?: string | undefined;
    }, {
        alt?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    params: {
        id: string;
    };
    body: {
        alt?: string | undefined;
    };
}, {
    params: {
        id: string;
    };
    body: {
        alt?: string | undefined;
    };
}>;
//# sourceMappingURL=media.schema.d.ts.map