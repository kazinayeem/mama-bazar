import { z } from "zod";
export declare const chatMessageSchema: z.ZodObject<{
    body: z.ZodObject<{
        message: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        message: string;
    }, {
        message: string;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        message: string;
    };
}, {
    body: {
        message: string;
    };
}>;
//# sourceMappingURL=chat.schema.d.ts.map