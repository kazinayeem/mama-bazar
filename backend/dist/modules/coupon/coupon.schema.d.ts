import { z } from "zod";
export declare const createCouponSchema: z.ZodObject<{
    body: z.ZodObject<{
        code: z.ZodString;
        discountType: z.ZodEnum<["percentage", "fixed"]>;
        discountValue: z.ZodPipeline<z.ZodUnion<[z.ZodString, z.ZodNumber]>, z.ZodNumber>;
        minOrderAmount: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        expiryDate: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive"]>>;
    }, "strip", z.ZodTypeAny, {
        code: string;
        discountType: "fixed" | "percentage";
        discountValue: number;
        status?: "active" | "inactive" | undefined;
        minOrderAmount?: string | number | undefined;
        expiryDate?: string | undefined;
    }, {
        code: string;
        discountType: "fixed" | "percentage";
        discountValue: string | number;
        status?: "active" | "inactive" | undefined;
        minOrderAmount?: string | number | undefined;
        expiryDate?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        code: string;
        discountType: "fixed" | "percentage";
        discountValue: number;
        status?: "active" | "inactive" | undefined;
        minOrderAmount?: string | number | undefined;
        expiryDate?: string | undefined;
    };
}, {
    body: {
        code: string;
        discountType: "fixed" | "percentage";
        discountValue: string | number;
        status?: "active" | "inactive" | undefined;
        minOrderAmount?: string | number | undefined;
        expiryDate?: string | undefined;
    };
}>;
export declare const updateCouponSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        code: z.ZodOptional<z.ZodString>;
        discountType: z.ZodOptional<z.ZodEnum<["percentage", "fixed"]>>;
        discountValue: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        minOrderAmount: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        expiryDate: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive"]>>;
    }, "strip", z.ZodTypeAny, {
        status?: "active" | "inactive" | undefined;
        code?: string | undefined;
        discountType?: "fixed" | "percentage" | undefined;
        discountValue?: string | number | undefined;
        minOrderAmount?: string | number | undefined;
        expiryDate?: string | undefined;
    }, {
        status?: "active" | "inactive" | undefined;
        code?: string | undefined;
        discountType?: "fixed" | "percentage" | undefined;
        discountValue?: string | number | undefined;
        minOrderAmount?: string | number | undefined;
        expiryDate?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    params: {
        id: string;
    };
    body: {
        status?: "active" | "inactive" | undefined;
        code?: string | undefined;
        discountType?: "fixed" | "percentage" | undefined;
        discountValue?: string | number | undefined;
        minOrderAmount?: string | number | undefined;
        expiryDate?: string | undefined;
    };
}, {
    params: {
        id: string;
    };
    body: {
        status?: "active" | "inactive" | undefined;
        code?: string | undefined;
        discountType?: "fixed" | "percentage" | undefined;
        discountValue?: string | number | undefined;
        minOrderAmount?: string | number | undefined;
        expiryDate?: string | undefined;
    };
}>;
export declare const validateCouponSchema: z.ZodObject<{
    body: z.ZodObject<{
        code: z.ZodString;
        subtotal: z.ZodPipeline<z.ZodUnion<[z.ZodString, z.ZodNumber]>, z.ZodNumber>;
    }, "strip", z.ZodTypeAny, {
        subtotal: number;
        code: string;
    }, {
        subtotal: string | number;
        code: string;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        subtotal: number;
        code: string;
    };
}, {
    body: {
        subtotal: string | number;
        code: string;
    };
}>;
export declare const couponIdSchema: z.ZodObject<{
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
//# sourceMappingURL=coupon.schema.d.ts.map