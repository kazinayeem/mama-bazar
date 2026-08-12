import { z } from "zod";
export declare const createReviewSchema: z.ZodObject<{
    body: z.ZodObject<{
        productId: z.ZodPipeline<z.ZodUnion<[z.ZodString, z.ZodNumber]>, z.ZodNumber>;
        rating: z.ZodPipeline<z.ZodUnion<[z.ZodString, z.ZodNumber]>, z.ZodNumber>;
        title: z.ZodOptional<z.ZodString>;
        comment: z.ZodString;
        customerName: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        productId: number;
        rating: number;
        comment: string;
        title?: string | undefined;
        customerName?: string | undefined;
    }, {
        productId: string | number;
        rating: string | number;
        comment: string;
        title?: string | undefined;
        customerName?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        productId: number;
        rating: number;
        comment: string;
        title?: string | undefined;
        customerName?: string | undefined;
    };
}, {
    body: {
        productId: string | number;
        rating: string | number;
        comment: string;
        title?: string | undefined;
        customerName?: string | undefined;
    };
}>;
export declare const updateReviewStatusSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        status: z.ZodEnum<["pending", "approved", "rejected"]>;
    }, "strip", z.ZodTypeAny, {
        status: "pending" | "rejected" | "approved";
    }, {
        status: "pending" | "rejected" | "approved";
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        status: "pending" | "rejected" | "approved";
    };
    params: {
        id: string;
    };
}, {
    body: {
        status: "pending" | "rejected" | "approved";
    };
    params: {
        id: string;
    };
}>;
export declare const reviewIdSchema: z.ZodObject<{
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
//# sourceMappingURL=review.schema.d.ts.map