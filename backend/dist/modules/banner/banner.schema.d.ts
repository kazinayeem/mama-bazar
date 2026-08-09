import { z } from "zod";
export declare const createBannerSchema: z.ZodObject<{
    body: z.ZodObject<{
        title: z.ZodOptional<z.ZodString>;
        subtitle: z.ZodOptional<z.ZodString>;
        link: z.ZodOptional<z.ZodString>;
        position: z.ZodOptional<z.ZodEnum<["hero", "banner", "promo", "sidebar"]>>;
        buttonText: z.ZodOptional<z.ZodString>;
        priority: z.ZodEffects<z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>, number, string | number | undefined>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive"]>>;
    }, "strip", z.ZodTypeAny, {
        priority: number;
        status?: "active" | "inactive" | undefined;
        link?: string | undefined;
        title?: string | undefined;
        subtitle?: string | undefined;
        position?: "banner" | "hero" | "promo" | "sidebar" | undefined;
        buttonText?: string | undefined;
    }, {
        status?: "active" | "inactive" | undefined;
        link?: string | undefined;
        title?: string | undefined;
        subtitle?: string | undefined;
        position?: "banner" | "hero" | "promo" | "sidebar" | undefined;
        buttonText?: string | undefined;
        priority?: string | number | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        priority: number;
        status?: "active" | "inactive" | undefined;
        link?: string | undefined;
        title?: string | undefined;
        subtitle?: string | undefined;
        position?: "banner" | "hero" | "promo" | "sidebar" | undefined;
        buttonText?: string | undefined;
    };
}, {
    body: {
        status?: "active" | "inactive" | undefined;
        link?: string | undefined;
        title?: string | undefined;
        subtitle?: string | undefined;
        position?: "banner" | "hero" | "promo" | "sidebar" | undefined;
        buttonText?: string | undefined;
        priority?: string | number | undefined;
    };
}>;
export declare const updateBannerSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        title: z.ZodOptional<z.ZodString>;
        subtitle: z.ZodOptional<z.ZodString>;
        link: z.ZodOptional<z.ZodString>;
        position: z.ZodOptional<z.ZodEnum<["hero", "banner", "promo", "sidebar"]>>;
        buttonText: z.ZodOptional<z.ZodString>;
        priority: z.ZodEffects<z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>, number, string | number | undefined>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive"]>>;
    }, "strip", z.ZodTypeAny, {
        priority: number;
        status?: "active" | "inactive" | undefined;
        link?: string | undefined;
        title?: string | undefined;
        subtitle?: string | undefined;
        position?: "banner" | "hero" | "promo" | "sidebar" | undefined;
        buttonText?: string | undefined;
    }, {
        status?: "active" | "inactive" | undefined;
        link?: string | undefined;
        title?: string | undefined;
        subtitle?: string | undefined;
        position?: "banner" | "hero" | "promo" | "sidebar" | undefined;
        buttonText?: string | undefined;
        priority?: string | number | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    params: {
        id: string;
    };
    body: {
        priority: number;
        status?: "active" | "inactive" | undefined;
        link?: string | undefined;
        title?: string | undefined;
        subtitle?: string | undefined;
        position?: "banner" | "hero" | "promo" | "sidebar" | undefined;
        buttonText?: string | undefined;
    };
}, {
    params: {
        id: string;
    };
    body: {
        status?: "active" | "inactive" | undefined;
        link?: string | undefined;
        title?: string | undefined;
        subtitle?: string | undefined;
        position?: "banner" | "hero" | "promo" | "sidebar" | undefined;
        buttonText?: string | undefined;
        priority?: string | number | undefined;
    };
}>;
export declare const bannerIdSchema: z.ZodObject<{
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
//# sourceMappingURL=banner.schema.d.ts.map