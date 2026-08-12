import { z } from "zod";
export declare const costListSchema: z.ZodObject<{
    query: z.ZodObject<{
        page: z.ZodOptional<z.ZodString>;
        limit: z.ZodOptional<z.ZodString>;
        search: z.ZodOptional<z.ZodString>;
        costType: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        search?: string | undefined;
        costType?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }, {
        search?: string | undefined;
        costType?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        search?: string | undefined;
        costType?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}, {
    query: {
        search?: string | undefined;
        costType?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}>;
export declare const costIdSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodEffects<z.ZodString, string, string>;
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
export declare const costCreateSchema: z.ZodObject<{
    body: z.ZodObject<{
        title: z.ZodString;
        costType: z.ZodOptional<z.ZodString>;
        quantity: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        unitCost: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        totalCost: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        supplierId: z.ZodOptional<z.ZodNullable<z.ZodNumber>>;
        productId: z.ZodOptional<z.ZodNullable<z.ZodNumber>>;
        orderId: z.ZodOptional<z.ZodNullable<z.ZodNumber>>;
        bookingId: z.ZodOptional<z.ZodNullable<z.ZodNumber>>;
        costDate: z.ZodString;
        paymentMethod: z.ZodOptional<z.ZodString>;
        notes: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        attachmentUrl: z.ZodOptional<z.ZodNullable<z.ZodString>>;
    }, "strip", z.ZodTypeAny, {
        title: string;
        costDate: string;
        notes?: string | null | undefined;
        supplierId?: number | null | undefined;
        productId?: number | null | undefined;
        orderId?: number | null | undefined;
        paymentMethod?: string | undefined;
        quantity?: string | number | undefined;
        attachmentUrl?: string | null | undefined;
        costType?: string | undefined;
        unitCost?: string | number | undefined;
        totalCost?: string | number | undefined;
        bookingId?: number | null | undefined;
    }, {
        title: string;
        costDate: string;
        notes?: string | null | undefined;
        supplierId?: number | null | undefined;
        productId?: number | null | undefined;
        orderId?: number | null | undefined;
        paymentMethod?: string | undefined;
        quantity?: string | number | undefined;
        attachmentUrl?: string | null | undefined;
        costType?: string | undefined;
        unitCost?: string | number | undefined;
        totalCost?: string | number | undefined;
        bookingId?: number | null | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        title: string;
        costDate: string;
        notes?: string | null | undefined;
        supplierId?: number | null | undefined;
        productId?: number | null | undefined;
        orderId?: number | null | undefined;
        paymentMethod?: string | undefined;
        quantity?: string | number | undefined;
        attachmentUrl?: string | null | undefined;
        costType?: string | undefined;
        unitCost?: string | number | undefined;
        totalCost?: string | number | undefined;
        bookingId?: number | null | undefined;
    };
}, {
    body: {
        title: string;
        costDate: string;
        notes?: string | null | undefined;
        supplierId?: number | null | undefined;
        productId?: number | null | undefined;
        orderId?: number | null | undefined;
        paymentMethod?: string | undefined;
        quantity?: string | number | undefined;
        attachmentUrl?: string | null | undefined;
        costType?: string | undefined;
        unitCost?: string | number | undefined;
        totalCost?: string | number | undefined;
        bookingId?: number | null | undefined;
    };
}>;
export declare const costUpdateSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodEffects<z.ZodString, string, string>;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        title: z.ZodOptional<z.ZodString>;
        costType: z.ZodOptional<z.ZodString>;
        quantity: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        unitCost: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        totalCost: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        supplierId: z.ZodOptional<z.ZodNullable<z.ZodNumber>>;
        productId: z.ZodOptional<z.ZodNullable<z.ZodNumber>>;
        orderId: z.ZodOptional<z.ZodNullable<z.ZodNumber>>;
        bookingId: z.ZodOptional<z.ZodNullable<z.ZodNumber>>;
        costDate: z.ZodOptional<z.ZodString>;
        paymentMethod: z.ZodOptional<z.ZodString>;
        notes: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        attachmentUrl: z.ZodOptional<z.ZodNullable<z.ZodString>>;
    }, "strip", z.ZodTypeAny, {
        title?: string | undefined;
        notes?: string | null | undefined;
        supplierId?: number | null | undefined;
        productId?: number | null | undefined;
        orderId?: number | null | undefined;
        paymentMethod?: string | undefined;
        quantity?: string | number | undefined;
        attachmentUrl?: string | null | undefined;
        costType?: string | undefined;
        unitCost?: string | number | undefined;
        totalCost?: string | number | undefined;
        bookingId?: number | null | undefined;
        costDate?: string | undefined;
    }, {
        title?: string | undefined;
        notes?: string | null | undefined;
        supplierId?: number | null | undefined;
        productId?: number | null | undefined;
        orderId?: number | null | undefined;
        paymentMethod?: string | undefined;
        quantity?: string | number | undefined;
        attachmentUrl?: string | null | undefined;
        costType?: string | undefined;
        unitCost?: string | number | undefined;
        totalCost?: string | number | undefined;
        bookingId?: number | null | undefined;
        costDate?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        title?: string | undefined;
        notes?: string | null | undefined;
        supplierId?: number | null | undefined;
        productId?: number | null | undefined;
        orderId?: number | null | undefined;
        paymentMethod?: string | undefined;
        quantity?: string | number | undefined;
        attachmentUrl?: string | null | undefined;
        costType?: string | undefined;
        unitCost?: string | number | undefined;
        totalCost?: string | number | undefined;
        bookingId?: number | null | undefined;
        costDate?: string | undefined;
    };
    params: {
        id: string;
    };
}, {
    body: {
        title?: string | undefined;
        notes?: string | null | undefined;
        supplierId?: number | null | undefined;
        productId?: number | null | undefined;
        orderId?: number | null | undefined;
        paymentMethod?: string | undefined;
        quantity?: string | number | undefined;
        attachmentUrl?: string | null | undefined;
        costType?: string | undefined;
        unitCost?: string | number | undefined;
        totalCost?: string | number | undefined;
        bookingId?: number | null | undefined;
        costDate?: string | undefined;
    };
    params: {
        id: string;
    };
}>;
//# sourceMappingURL=cost.schema.d.ts.map