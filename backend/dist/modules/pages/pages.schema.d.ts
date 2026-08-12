import { z } from "zod";
export declare const pageSlugSchema: z.ZodObject<{
    params: z.ZodObject<{
        slug: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        slug: string;
    }, {
        slug: string;
    }>;
}, "strip", z.ZodTypeAny, {
    params: {
        slug: string;
    };
}, {
    params: {
        slug: string;
    };
}>;
export declare const createPageSchema: z.ZodObject<{
    body: z.ZodObject<{
        slug: z.ZodString;
        title: z.ZodString;
        content: z.ZodString;
        status: z.ZodDefault<z.ZodEnum<["published", "draft"]>>;
    }, "strip", z.ZodTypeAny, {
        slug: string;
        status: "draft" | "published";
        title: string;
        content: string;
    }, {
        slug: string;
        title: string;
        content: string;
        status?: "draft" | "published" | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        slug: string;
        status: "draft" | "published";
        title: string;
        content: string;
    };
}, {
    body: {
        slug: string;
        title: string;
        content: string;
        status?: "draft" | "published" | undefined;
    };
}>;
export declare const updatePageSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        title: z.ZodOptional<z.ZodString>;
        content: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodEnum<["published", "draft"]>>;
    }, "strip", z.ZodTypeAny, {
        status?: "draft" | "published" | undefined;
        title?: string | undefined;
        content?: string | undefined;
    }, {
        status?: "draft" | "published" | undefined;
        title?: string | undefined;
        content?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        status?: "draft" | "published" | undefined;
        title?: string | undefined;
        content?: string | undefined;
    };
    params: {
        id: string;
    };
}, {
    body: {
        status?: "draft" | "published" | undefined;
        title?: string | undefined;
        content?: string | undefined;
    };
    params: {
        id: string;
    };
}>;
export declare const pageIdSchema: z.ZodObject<{
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
export declare const contactMessageSchema: z.ZodObject<{
    body: z.ZodObject<{
        name: z.ZodString;
        phone: z.ZodString;
        email: z.ZodUnion<[z.ZodOptional<z.ZodString>, z.ZodLiteral<"">]>;
        message: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        name: string;
        phone: string;
        message: string;
        email?: string | undefined;
    }, {
        name: string;
        phone: string;
        message: string;
        email?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name: string;
        phone: string;
        message: string;
        email?: string | undefined;
    };
}, {
    body: {
        name: string;
        phone: string;
        message: string;
        email?: string | undefined;
    };
}>;
//# sourceMappingURL=pages.schema.d.ts.map