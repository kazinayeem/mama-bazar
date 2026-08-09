import { z } from "zod";
export declare const trackPurchaseSchema: z.ZodObject<{
    body: z.ZodObject<{
        value: z.ZodPipeline<z.ZodUnion<[z.ZodString, z.ZodNumber]>, z.ZodNumber>;
        contentIds: z.ZodUnion<[z.ZodString, z.ZodNumber, z.ZodArray<z.ZodUnion<[z.ZodString, z.ZodNumber]>, "many">]>;
        currency: z.ZodOptional<z.ZodString>;
        contentType: z.ZodOptional<z.ZodString>;
        fbp: z.ZodOptional<z.ZodString>;
        fbc: z.ZodOptional<z.ZodString>;
        email: z.ZodOptional<z.ZodString>;
        phone: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        value: number;
        contentIds: string | number | (string | number)[];
        phone?: string | undefined;
        email?: string | undefined;
        currency?: string | undefined;
        contentType?: string | undefined;
        fbp?: string | undefined;
        fbc?: string | undefined;
    }, {
        value: string | number;
        contentIds: string | number | (string | number)[];
        phone?: string | undefined;
        email?: string | undefined;
        currency?: string | undefined;
        contentType?: string | undefined;
        fbp?: string | undefined;
        fbc?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        value: number;
        contentIds: string | number | (string | number)[];
        phone?: string | undefined;
        email?: string | undefined;
        currency?: string | undefined;
        contentType?: string | undefined;
        fbp?: string | undefined;
        fbc?: string | undefined;
    };
}, {
    body: {
        value: string | number;
        contentIds: string | number | (string | number)[];
        phone?: string | undefined;
        email?: string | undefined;
        currency?: string | undefined;
        contentType?: string | undefined;
        fbp?: string | undefined;
        fbc?: string | undefined;
    };
}>;
//# sourceMappingURL=analytics.schema.d.ts.map