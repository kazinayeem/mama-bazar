import { z } from "zod";
export declare const createBrandSchema: z.ZodObject<{
    body: z.ZodObject<{
        name: z.ZodString;
        slug: z.ZodOptional<z.ZodString>;
        logo: z.ZodOptional<z.ZodString>;
        bannerImage: z.ZodOptional<z.ZodString>;
        description: z.ZodOptional<z.ZodString>;
        website: z.ZodOptional<z.ZodString>;
        countryOfOrigin: z.ZodOptional<z.ZodString>;
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
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        seoTitle?: string | undefined;
        seoDescription?: string | undefined;
        seoKeywords?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | undefined;
        countryOfOrigin?: string | undefined;
        bannerImage?: string | undefined;
        website?: string | undefined;
    }, {
        name: string;
        slug?: string | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        seoTitle?: string | undefined;
        seoDescription?: string | undefined;
        seoKeywords?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | undefined;
        countryOfOrigin?: string | undefined;
        bannerImage?: string | undefined;
        website?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name: string;
        slug?: string | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        seoTitle?: string | undefined;
        seoDescription?: string | undefined;
        seoKeywords?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | undefined;
        countryOfOrigin?: string | undefined;
        bannerImage?: string | undefined;
        website?: string | undefined;
    };
}, {
    body: {
        name: string;
        slug?: string | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        seoTitle?: string | undefined;
        seoDescription?: string | undefined;
        seoKeywords?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | undefined;
        countryOfOrigin?: string | undefined;
        bannerImage?: string | undefined;
        website?: string | undefined;
    };
}>;
export declare const updateBrandSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        name: z.ZodOptional<z.ZodString>;
        slug: z.ZodOptional<z.ZodString>;
        logo: z.ZodOptional<z.ZodString>;
        bannerImage: z.ZodOptional<z.ZodString>;
        description: z.ZodOptional<z.ZodString>;
        website: z.ZodOptional<z.ZodString>;
        countryOfOrigin: z.ZodOptional<z.ZodString>;
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
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        seoTitle?: string | undefined;
        seoDescription?: string | undefined;
        seoKeywords?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | undefined;
        countryOfOrigin?: string | undefined;
        bannerImage?: string | undefined;
        website?: string | undefined;
    }, {
        name?: string | undefined;
        slug?: string | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        seoTitle?: string | undefined;
        seoDescription?: string | undefined;
        seoKeywords?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | undefined;
        countryOfOrigin?: string | undefined;
        bannerImage?: string | undefined;
        website?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name?: string | undefined;
        slug?: string | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        seoTitle?: string | undefined;
        seoDescription?: string | undefined;
        seoKeywords?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | undefined;
        countryOfOrigin?: string | undefined;
        bannerImage?: string | undefined;
        website?: string | undefined;
    };
    params: {
        id: string;
    };
}, {
    body: {
        name?: string | undefined;
        slug?: string | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        seoTitle?: string | undefined;
        seoDescription?: string | undefined;
        seoKeywords?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | undefined;
        countryOfOrigin?: string | undefined;
        bannerImage?: string | undefined;
        website?: string | undefined;
    };
    params: {
        id: string;
    };
}>;
export declare const brandIdSchema: z.ZodObject<{
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
export declare const brandListSchema: z.ZodObject<{
    query: z.ZodObject<{
        page: z.ZodOptional<z.ZodString>;
        limit: z.ZodOptional<z.ZodString>;
        search: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodString>;
        featured: z.ZodOptional<z.ZodString>;
        sort: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        featured?: string | undefined;
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }, {
        featured?: string | undefined;
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        featured?: string | undefined;
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}, {
    query: {
        featured?: string | undefined;
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}>;
export declare const brandMoveSchema: z.ZodObject<{
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
    body: {
        targetId: string | number | null;
    };
    params: {
        id: string;
    };
}, {
    body: {
        targetId: string | number | null;
    };
    params: {
        id: string;
    };
}>;
//# sourceMappingURL=brand.schema.d.ts.map