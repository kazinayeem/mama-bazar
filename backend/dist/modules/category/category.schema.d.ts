import { z } from "zod";
export declare const createCategorySchema: z.ZodObject<{
    body: z.ZodObject<{
        name: z.ZodString;
        slug: z.ZodOptional<z.ZodString>;
        parentId: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber, z.ZodNull]>>;
        description: z.ZodOptional<z.ZodString>;
        image: z.ZodOptional<z.ZodString>;
        icon: z.ZodOptional<z.ZodString>;
        banner: z.ZodOptional<z.ZodString>;
        thumbnail: z.ZodOptional<z.ZodString>;
        featured: z.ZodOptional<z.ZodUnion<[z.ZodBoolean, z.ZodString]>>;
        homepageVisibility: z.ZodOptional<z.ZodUnion<[z.ZodBoolean, z.ZodString]>>;
        sortOrder: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        seoTitle: z.ZodOptional<z.ZodString>;
        seoDescription: z.ZodOptional<z.ZodString>;
        seoKeywords: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive", "archived"]>>;
    }, "strip", z.ZodTypeAny, {
        name: string;
        slug?: string | undefined;
        parentId?: string | number | null | undefined;
        image?: string | undefined;
        icon?: string | undefined;
        banner?: string | undefined;
        thumbnail?: string | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        seoTitle?: string | undefined;
        seoDescription?: string | undefined;
        seoKeywords?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
    }, {
        name: string;
        slug?: string | undefined;
        parentId?: string | number | null | undefined;
        image?: string | undefined;
        icon?: string | undefined;
        banner?: string | undefined;
        thumbnail?: string | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        seoTitle?: string | undefined;
        seoDescription?: string | undefined;
        seoKeywords?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name: string;
        slug?: string | undefined;
        parentId?: string | number | null | undefined;
        image?: string | undefined;
        icon?: string | undefined;
        banner?: string | undefined;
        thumbnail?: string | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        seoTitle?: string | undefined;
        seoDescription?: string | undefined;
        seoKeywords?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
    };
}, {
    body: {
        name: string;
        slug?: string | undefined;
        parentId?: string | number | null | undefined;
        image?: string | undefined;
        icon?: string | undefined;
        banner?: string | undefined;
        thumbnail?: string | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        seoTitle?: string | undefined;
        seoDescription?: string | undefined;
        seoKeywords?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
    };
}>;
export declare const updateCategorySchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        name: z.ZodOptional<z.ZodString>;
        parentId: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber, z.ZodNull]>>;
        slug: z.ZodOptional<z.ZodString>;
        description: z.ZodOptional<z.ZodString>;
        image: z.ZodOptional<z.ZodString>;
        icon: z.ZodOptional<z.ZodString>;
        banner: z.ZodOptional<z.ZodString>;
        thumbnail: z.ZodOptional<z.ZodString>;
        featured: z.ZodOptional<z.ZodUnion<[z.ZodBoolean, z.ZodString]>>;
        homepageVisibility: z.ZodOptional<z.ZodUnion<[z.ZodBoolean, z.ZodString]>>;
        sortOrder: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        seoTitle: z.ZodOptional<z.ZodString>;
        seoDescription: z.ZodOptional<z.ZodString>;
        seoKeywords: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive", "archived"]>>;
    }, "strip", z.ZodTypeAny, {
        name?: string | undefined;
        slug?: string | undefined;
        parentId?: string | number | null | undefined;
        image?: string | undefined;
        icon?: string | undefined;
        banner?: string | undefined;
        thumbnail?: string | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        seoTitle?: string | undefined;
        seoDescription?: string | undefined;
        seoKeywords?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
    }, {
        name?: string | undefined;
        slug?: string | undefined;
        parentId?: string | number | null | undefined;
        image?: string | undefined;
        icon?: string | undefined;
        banner?: string | undefined;
        thumbnail?: string | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        seoTitle?: string | undefined;
        seoDescription?: string | undefined;
        seoKeywords?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    params: {
        id: string;
    };
    body: {
        name?: string | undefined;
        slug?: string | undefined;
        parentId?: string | number | null | undefined;
        image?: string | undefined;
        icon?: string | undefined;
        banner?: string | undefined;
        thumbnail?: string | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        seoTitle?: string | undefined;
        seoDescription?: string | undefined;
        seoKeywords?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
    };
}, {
    params: {
        id: string;
    };
    body: {
        name?: string | undefined;
        slug?: string | undefined;
        parentId?: string | number | null | undefined;
        image?: string | undefined;
        icon?: string | undefined;
        banner?: string | undefined;
        thumbnail?: string | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        seoTitle?: string | undefined;
        seoDescription?: string | undefined;
        seoKeywords?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
    };
}>;
export declare const categoryIdSchema: z.ZodObject<{
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
export declare const categorySlugSchema: z.ZodObject<{
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
export declare const categoryListSchema: z.ZodObject<{
    query: z.ZodObject<{
        page: z.ZodOptional<z.ZodString>;
        limit: z.ZodOptional<z.ZodString>;
        search: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodString>;
        parentId: z.ZodOptional<z.ZodString>;
        featured: z.ZodOptional<z.ZodString>;
        sort: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        parentId?: string | undefined;
        featured?: string | undefined;
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }, {
        parentId?: string | undefined;
        featured?: string | undefined;
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        parentId?: string | undefined;
        featured?: string | undefined;
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}, {
    query: {
        parentId?: string | undefined;
        featured?: string | undefined;
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}>;
export declare const categoryMoveSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        targetId: z.ZodUnion<[z.ZodString, z.ZodNumber, z.ZodNull]>;
    }, "strip", z.ZodTypeAny, {
        targetId: string | number | null;
    }, {
        targetId: string | number | null;
    }>;
}, "strip", z.ZodTypeAny, {
    params: {
        id: string;
    };
    body: {
        targetId: string | number | null;
    };
}, {
    params: {
        id: string;
    };
    body: {
        targetId: string | number | null;
    };
}>;
//# sourceMappingURL=category.schema.d.ts.map