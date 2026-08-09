import { z } from "zod";
export declare const createCheckoutNoticeSchema: z.ZodObject<{
    body: z.ZodObject<{
        text: z.ZodString;
        priority: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        backgroundColor: z.ZodOptional<z.ZodString>;
        textColor: z.ZodOptional<z.ZodString>;
        icon: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive"]>>;
    }, "strip", z.ZodTypeAny, {
        text: string;
        icon?: string | undefined;
        status?: "active" | "inactive" | undefined;
        priority?: string | number | undefined;
        backgroundColor?: string | undefined;
        textColor?: string | undefined;
    }, {
        text: string;
        icon?: string | undefined;
        status?: "active" | "inactive" | undefined;
        priority?: string | number | undefined;
        backgroundColor?: string | undefined;
        textColor?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        text: string;
        icon?: string | undefined;
        status?: "active" | "inactive" | undefined;
        priority?: string | number | undefined;
        backgroundColor?: string | undefined;
        textColor?: string | undefined;
    };
}, {
    body: {
        text: string;
        icon?: string | undefined;
        status?: "active" | "inactive" | undefined;
        priority?: string | number | undefined;
        backgroundColor?: string | undefined;
        textColor?: string | undefined;
    };
}>;
export declare const updateCheckoutNoticeSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        text: z.ZodOptional<z.ZodString>;
        priority: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        backgroundColor: z.ZodOptional<z.ZodString>;
        textColor: z.ZodOptional<z.ZodString>;
        icon: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive"]>>;
    }, "strip", z.ZodTypeAny, {
        icon?: string | undefined;
        text?: string | undefined;
        status?: "active" | "inactive" | undefined;
        priority?: string | number | undefined;
        backgroundColor?: string | undefined;
        textColor?: string | undefined;
    }, {
        icon?: string | undefined;
        text?: string | undefined;
        status?: "active" | "inactive" | undefined;
        priority?: string | number | undefined;
        backgroundColor?: string | undefined;
        textColor?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    params: {
        id: string;
    };
    body: {
        icon?: string | undefined;
        text?: string | undefined;
        status?: "active" | "inactive" | undefined;
        priority?: string | number | undefined;
        backgroundColor?: string | undefined;
        textColor?: string | undefined;
    };
}, {
    params: {
        id: string;
    };
    body: {
        icon?: string | undefined;
        text?: string | undefined;
        status?: "active" | "inactive" | undefined;
        priority?: string | number | undefined;
        backgroundColor?: string | undefined;
        textColor?: string | undefined;
    };
}>;
export declare const checkoutNoticeIdSchema: z.ZodObject<{
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
export type CreateCheckoutNoticeInput = {
    text: string;
    priority?: number;
    backgroundColor?: string;
    textColor?: string;
    icon?: string;
    status?: "active" | "inactive";
};
export type UpdateCheckoutNoticeInput = Partial<CreateCheckoutNoticeInput>;
//# sourceMappingURL=checkout-notice.schema.d.ts.map