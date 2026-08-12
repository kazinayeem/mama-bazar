import { z } from "zod";
export declare const createTrackingSchema: z.ZodObject<{
    body: z.ZodObject<{
        name: z.ZodString;
        type: z.ZodString;
        pixelId: z.ZodOptional<z.ZodString>;
        scriptCode: z.ZodOptional<z.ZodString>;
        accessToken: z.ZodOptional<z.ZodString>;
        testEventCode: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive"]>>;
    }, "strip", z.ZodTypeAny, {
        name: string;
        type: string;
        status?: "active" | "inactive" | undefined;
        pixelId?: string | undefined;
        scriptCode?: string | undefined;
        accessToken?: string | undefined;
        testEventCode?: string | undefined;
    }, {
        name: string;
        type: string;
        status?: "active" | "inactive" | undefined;
        pixelId?: string | undefined;
        scriptCode?: string | undefined;
        accessToken?: string | undefined;
        testEventCode?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name: string;
        type: string;
        status?: "active" | "inactive" | undefined;
        pixelId?: string | undefined;
        scriptCode?: string | undefined;
        accessToken?: string | undefined;
        testEventCode?: string | undefined;
    };
}, {
    body: {
        name: string;
        type: string;
        status?: "active" | "inactive" | undefined;
        pixelId?: string | undefined;
        scriptCode?: string | undefined;
        accessToken?: string | undefined;
        testEventCode?: string | undefined;
    };
}>;
export declare const updateTrackingSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        name: z.ZodOptional<z.ZodString>;
        type: z.ZodOptional<z.ZodString>;
        pixelId: z.ZodOptional<z.ZodString>;
        scriptCode: z.ZodOptional<z.ZodString>;
        accessToken: z.ZodOptional<z.ZodString>;
        testEventCode: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive"]>>;
    }, "strip", z.ZodTypeAny, {
        name?: string | undefined;
        status?: "active" | "inactive" | undefined;
        type?: string | undefined;
        pixelId?: string | undefined;
        scriptCode?: string | undefined;
        accessToken?: string | undefined;
        testEventCode?: string | undefined;
    }, {
        name?: string | undefined;
        status?: "active" | "inactive" | undefined;
        type?: string | undefined;
        pixelId?: string | undefined;
        scriptCode?: string | undefined;
        accessToken?: string | undefined;
        testEventCode?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name?: string | undefined;
        status?: "active" | "inactive" | undefined;
        type?: string | undefined;
        pixelId?: string | undefined;
        scriptCode?: string | undefined;
        accessToken?: string | undefined;
        testEventCode?: string | undefined;
    };
    params: {
        id: string;
    };
}, {
    body: {
        name?: string | undefined;
        status?: "active" | "inactive" | undefined;
        type?: string | undefined;
        pixelId?: string | undefined;
        scriptCode?: string | undefined;
        accessToken?: string | undefined;
        testEventCode?: string | undefined;
    };
    params: {
        id: string;
    };
}>;
export declare const trackingIdSchema: z.ZodObject<{
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
//# sourceMappingURL=tracking.schema.d.ts.map