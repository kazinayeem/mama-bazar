import { z } from "zod";
export declare const createShippingMethodSchema: z.ZodObject<{
    body: z.ZodObject<{
        name: z.ZodString;
        charge: z.ZodPipeline<z.ZodUnion<[z.ZodString, z.ZodNumber]>, z.ZodNumber>;
        estimatedDelivery: z.ZodOptional<z.ZodString>;
        description: z.ZodOptional<z.ZodString>;
        priority: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        freeShippingMinAmount: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        codAvailable: z.ZodOptional<z.ZodBoolean>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive"]>>;
    }, "strip", z.ZodTypeAny, {
        name: string;
        charge: number;
        description?: string | undefined;
        status?: "active" | "inactive" | undefined;
        priority?: string | number | undefined;
        estimatedDelivery?: string | undefined;
        freeShippingMinAmount?: string | number | undefined;
        codAvailable?: boolean | undefined;
    }, {
        name: string;
        charge: string | number;
        description?: string | undefined;
        status?: "active" | "inactive" | undefined;
        priority?: string | number | undefined;
        estimatedDelivery?: string | undefined;
        freeShippingMinAmount?: string | number | undefined;
        codAvailable?: boolean | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name: string;
        charge: number;
        description?: string | undefined;
        status?: "active" | "inactive" | undefined;
        priority?: string | number | undefined;
        estimatedDelivery?: string | undefined;
        freeShippingMinAmount?: string | number | undefined;
        codAvailable?: boolean | undefined;
    };
}, {
    body: {
        name: string;
        charge: string | number;
        description?: string | undefined;
        status?: "active" | "inactive" | undefined;
        priority?: string | number | undefined;
        estimatedDelivery?: string | undefined;
        freeShippingMinAmount?: string | number | undefined;
        codAvailable?: boolean | undefined;
    };
}>;
export declare const updateShippingMethodSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        name: z.ZodOptional<z.ZodString>;
        charge: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        estimatedDelivery: z.ZodOptional<z.ZodString>;
        description: z.ZodOptional<z.ZodString>;
        priority: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        freeShippingMinAmount: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        codAvailable: z.ZodOptional<z.ZodBoolean>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive"]>>;
    }, "strip", z.ZodTypeAny, {
        name?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | undefined;
        priority?: string | number | undefined;
        charge?: string | number | undefined;
        estimatedDelivery?: string | undefined;
        freeShippingMinAmount?: string | number | undefined;
        codAvailable?: boolean | undefined;
    }, {
        name?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | undefined;
        priority?: string | number | undefined;
        charge?: string | number | undefined;
        estimatedDelivery?: string | undefined;
        freeShippingMinAmount?: string | number | undefined;
        codAvailable?: boolean | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | undefined;
        priority?: string | number | undefined;
        charge?: string | number | undefined;
        estimatedDelivery?: string | undefined;
        freeShippingMinAmount?: string | number | undefined;
        codAvailable?: boolean | undefined;
    };
    params: {
        id: string;
    };
}, {
    body: {
        name?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | undefined;
        priority?: string | number | undefined;
        charge?: string | number | undefined;
        estimatedDelivery?: string | undefined;
        freeShippingMinAmount?: string | number | undefined;
        codAvailable?: boolean | undefined;
    };
    params: {
        id: string;
    };
}>;
export declare const shippingMethodIdSchema: z.ZodObject<{
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
export declare const estimateShippingSchema: z.ZodObject<{
    body: z.ZodObject<{
        subtotal: z.ZodPipeline<z.ZodUnion<[z.ZodString, z.ZodNumber]>, z.ZodNumber>;
    }, "strip", z.ZodTypeAny, {
        subtotal: number;
    }, {
        subtotal: string | number;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        subtotal: number;
    };
}, {
    body: {
        subtotal: string | number;
    };
}>;
export type CreateShippingMethodInput = {
    name: string;
    charge: string;
    estimatedDelivery?: string;
    description?: string;
    priority?: number;
    freeShippingMinAmount?: string;
    codAvailable?: boolean;
    status?: "active" | "inactive";
};
export type UpdateShippingMethodInput = Partial<CreateShippingMethodInput>;
//# sourceMappingURL=shipping.schema.d.ts.map