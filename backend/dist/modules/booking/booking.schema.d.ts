import { z } from "zod";
export declare const bookingListSchema: z.ZodObject<{
    query: z.ZodObject<{
        page: z.ZodOptional<z.ZodString>;
        limit: z.ZodOptional<z.ZodString>;
        search: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodEnum<["pending", "confirmed", "active", "completed", "cancelled"]>>;
        paymentStatus: z.ZodOptional<z.ZodEnum<["pending", "partial", "paid", "refunded"]>>;
    }, "strip", z.ZodTypeAny, {
        status?: "active" | "pending" | "confirmed" | "cancelled" | "completed" | undefined;
        search?: string | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }, {
        status?: "active" | "pending" | "confirmed" | "cancelled" | "completed" | undefined;
        search?: string | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        status?: "active" | "pending" | "confirmed" | "cancelled" | "completed" | undefined;
        search?: string | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}, {
    query: {
        status?: "active" | "pending" | "confirmed" | "cancelled" | "completed" | undefined;
        search?: string | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}>;
export declare const bookingIdSchema: z.ZodObject<{
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
export declare const bookingCreateSchema: z.ZodObject<{
    body: z.ZodObject<{
        customerName: z.ZodString;
        phone: z.ZodString;
        email: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        userId: z.ZodNullable<z.ZodOptional<z.ZodNumber>>;
        bookingType: z.ZodOptional<z.ZodString>;
        service: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        productId: z.ZodNullable<z.ZodOptional<z.ZodNumber>>;
        startDate: z.ZodString;
        endDate: z.ZodString;
        quantity: z.ZodDefault<z.ZodOptional<z.ZodNumber>>;
        price: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        discount: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        additionalCost: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        totalAmount: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        paymentStatus: z.ZodOptional<z.ZodEnum<["pending", "partial", "paid", "refunded"]>>;
        status: z.ZodOptional<z.ZodEnum<["pending", "confirmed", "active", "completed", "cancelled"]>>;
        notes: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        attachmentUrl: z.ZodNullable<z.ZodOptional<z.ZodString>>;
    }, "strip", z.ZodTypeAny, {
        startDate: string;
        endDate: string;
        phone: string;
        customerName: string;
        quantity: number;
        status?: "active" | "pending" | "confirmed" | "cancelled" | "completed" | undefined;
        price?: string | number | undefined;
        discount?: string | number | undefined;
        email?: string | null | undefined;
        notes?: string | null | undefined;
        productId?: number | null | undefined;
        userId?: number | null | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        attachmentUrl?: string | null | undefined;
        service?: string | null | undefined;
        bookingType?: string | undefined;
        additionalCost?: string | number | undefined;
        totalAmount?: string | number | undefined;
    }, {
        startDate: string;
        endDate: string;
        phone: string;
        customerName: string;
        status?: "active" | "pending" | "confirmed" | "cancelled" | "completed" | undefined;
        price?: string | number | undefined;
        discount?: string | number | undefined;
        email?: string | null | undefined;
        notes?: string | null | undefined;
        productId?: number | null | undefined;
        userId?: number | null | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        quantity?: number | undefined;
        attachmentUrl?: string | null | undefined;
        service?: string | null | undefined;
        bookingType?: string | undefined;
        additionalCost?: string | number | undefined;
        totalAmount?: string | number | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        startDate: string;
        endDate: string;
        phone: string;
        customerName: string;
        quantity: number;
        status?: "active" | "pending" | "confirmed" | "cancelled" | "completed" | undefined;
        price?: string | number | undefined;
        discount?: string | number | undefined;
        email?: string | null | undefined;
        notes?: string | null | undefined;
        productId?: number | null | undefined;
        userId?: number | null | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        attachmentUrl?: string | null | undefined;
        service?: string | null | undefined;
        bookingType?: string | undefined;
        additionalCost?: string | number | undefined;
        totalAmount?: string | number | undefined;
    };
}, {
    body: {
        startDate: string;
        endDate: string;
        phone: string;
        customerName: string;
        status?: "active" | "pending" | "confirmed" | "cancelled" | "completed" | undefined;
        price?: string | number | undefined;
        discount?: string | number | undefined;
        email?: string | null | undefined;
        notes?: string | null | undefined;
        productId?: number | null | undefined;
        userId?: number | null | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        quantity?: number | undefined;
        attachmentUrl?: string | null | undefined;
        service?: string | null | undefined;
        bookingType?: string | undefined;
        additionalCost?: string | number | undefined;
        totalAmount?: string | number | undefined;
    };
}>;
export declare const bookingUpdateSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodEffects<z.ZodString, string, string>;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        customerName: z.ZodOptional<z.ZodString>;
        phone: z.ZodOptional<z.ZodString>;
        email: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        userId: z.ZodNullable<z.ZodOptional<z.ZodNumber>>;
        bookingType: z.ZodOptional<z.ZodString>;
        service: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        productId: z.ZodNullable<z.ZodOptional<z.ZodNumber>>;
        startDate: z.ZodOptional<z.ZodString>;
        endDate: z.ZodOptional<z.ZodString>;
        quantity: z.ZodOptional<z.ZodNumber>;
        price: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        discount: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        additionalCost: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        totalAmount: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        paymentStatus: z.ZodOptional<z.ZodEnum<["pending", "partial", "paid", "refunded"]>>;
        status: z.ZodOptional<z.ZodEnum<["pending", "confirmed", "active", "completed", "cancelled"]>>;
        notes: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        attachmentUrl: z.ZodNullable<z.ZodOptional<z.ZodString>>;
    }, "strip", z.ZodTypeAny, {
        status?: "active" | "pending" | "confirmed" | "cancelled" | "completed" | undefined;
        price?: string | number | undefined;
        discount?: string | number | undefined;
        startDate?: string | undefined;
        endDate?: string | undefined;
        phone?: string | undefined;
        email?: string | null | undefined;
        notes?: string | null | undefined;
        productId?: number | null | undefined;
        userId?: number | null | undefined;
        customerName?: string | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        quantity?: number | undefined;
        attachmentUrl?: string | null | undefined;
        service?: string | null | undefined;
        bookingType?: string | undefined;
        additionalCost?: string | number | undefined;
        totalAmount?: string | number | undefined;
    }, {
        status?: "active" | "pending" | "confirmed" | "cancelled" | "completed" | undefined;
        price?: string | number | undefined;
        discount?: string | number | undefined;
        startDate?: string | undefined;
        endDate?: string | undefined;
        phone?: string | undefined;
        email?: string | null | undefined;
        notes?: string | null | undefined;
        productId?: number | null | undefined;
        userId?: number | null | undefined;
        customerName?: string | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        quantity?: number | undefined;
        attachmentUrl?: string | null | undefined;
        service?: string | null | undefined;
        bookingType?: string | undefined;
        additionalCost?: string | number | undefined;
        totalAmount?: string | number | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        status?: "active" | "pending" | "confirmed" | "cancelled" | "completed" | undefined;
        price?: string | number | undefined;
        discount?: string | number | undefined;
        startDate?: string | undefined;
        endDate?: string | undefined;
        phone?: string | undefined;
        email?: string | null | undefined;
        notes?: string | null | undefined;
        productId?: number | null | undefined;
        userId?: number | null | undefined;
        customerName?: string | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        quantity?: number | undefined;
        attachmentUrl?: string | null | undefined;
        service?: string | null | undefined;
        bookingType?: string | undefined;
        additionalCost?: string | number | undefined;
        totalAmount?: string | number | undefined;
    };
    params: {
        id: string;
    };
}, {
    body: {
        status?: "active" | "pending" | "confirmed" | "cancelled" | "completed" | undefined;
        price?: string | number | undefined;
        discount?: string | number | undefined;
        startDate?: string | undefined;
        endDate?: string | undefined;
        phone?: string | undefined;
        email?: string | null | undefined;
        notes?: string | null | undefined;
        productId?: number | null | undefined;
        userId?: number | null | undefined;
        customerName?: string | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        quantity?: number | undefined;
        attachmentUrl?: string | null | undefined;
        service?: string | null | undefined;
        bookingType?: string | undefined;
        additionalCost?: string | number | undefined;
        totalAmount?: string | number | undefined;
    };
    params: {
        id: string;
    };
}>;
//# sourceMappingURL=booking.schema.d.ts.map