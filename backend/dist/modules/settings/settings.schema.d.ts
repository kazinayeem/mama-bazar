import { z } from "zod";
export declare const setSettingSchema: z.ZodObject<{
    body: z.ZodObject<{
        key: z.ZodString;
        value: z.ZodAny;
    }, "strip", z.ZodTypeAny, {
        key: string;
        value?: any;
    }, {
        key: string;
        value?: any;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        key: string;
        value?: any;
    };
}, {
    body: {
        key: string;
        value?: any;
    };
}>;
export declare const getSettingSchema: z.ZodObject<{
    params: z.ZodObject<{
        key: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        key: string;
    }, {
        key: string;
    }>;
}, "strip", z.ZodTypeAny, {
    params: {
        key: string;
    };
}, {
    params: {
        key: string;
    };
}>;
//# sourceMappingURL=settings.schema.d.ts.map