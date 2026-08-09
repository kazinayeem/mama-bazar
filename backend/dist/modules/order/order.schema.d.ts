import { z } from "zod";
export declare const createOrderSchema: z.ZodObject<{
    body: z.ZodObject<{
        name: z.ZodString;
        phone: z.ZodString;
        alternativePhone: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        email: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        country: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        division: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        district: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        upazila: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        area: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        apartment: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        postalCode: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        address: z.ZodString;
        shippingArea: z.ZodString;
        shippingCost: z.ZodNullable<z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>>;
        shippingMethodId: z.ZodNullable<z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>>;
        couponCode: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        orderNote: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        checkoutNotes: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        paymentMethod: z.ZodOptional<z.ZodEnum<["cod", "bkash", "nagad", "rocket", "bank", "stripe", "sslcommerz", "paypal"]>>;
        transactionId: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        senderNumber: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        paymentScreenshot: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        amountSent: z.ZodNullable<z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>>;
        paymentInstructions: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        taxAmount: z.ZodNullable<z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>>;
        items: z.ZodArray<z.ZodObject<{
            productId: z.ZodNumber;
            quantity: z.ZodNumber;
            size: z.ZodNullable<z.ZodOptional<z.ZodString>>;
            color: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        }, "strip", z.ZodTypeAny, {
            productId: number;
            quantity: number;
            size?: string | null | undefined;
            color?: string | null | undefined;
        }, {
            productId: number;
            quantity: number;
            size?: string | null | undefined;
            color?: string | null | undefined;
        }>, "many">;
    }, "strip", z.ZodTypeAny, {
        name: string;
        phone: string;
        address: string;
        shippingArea: string;
        items: {
            productId: number;
            quantity: number;
            size?: string | null | undefined;
            color?: string | null | undefined;
        }[];
        email?: string | null | undefined;
        shippingCost?: string | number | null | undefined;
        alternativePhone?: string | null | undefined;
        country?: string | null | undefined;
        division?: string | null | undefined;
        district?: string | null | undefined;
        upazila?: string | null | undefined;
        area?: string | null | undefined;
        apartment?: string | null | undefined;
        postalCode?: string | null | undefined;
        shippingMethodId?: string | number | null | undefined;
        couponCode?: string | null | undefined;
        orderNote?: string | null | undefined;
        checkoutNotes?: string | null | undefined;
        paymentMethod?: "cod" | "bkash" | "nagad" | "rocket" | "bank" | "stripe" | "sslcommerz" | "paypal" | undefined;
        transactionId?: string | null | undefined;
        senderNumber?: string | null | undefined;
        paymentScreenshot?: string | null | undefined;
        amountSent?: string | number | null | undefined;
        paymentInstructions?: string | null | undefined;
        taxAmount?: string | number | null | undefined;
    }, {
        name: string;
        phone: string;
        address: string;
        shippingArea: string;
        items: {
            productId: number;
            quantity: number;
            size?: string | null | undefined;
            color?: string | null | undefined;
        }[];
        email?: string | null | undefined;
        shippingCost?: string | number | null | undefined;
        alternativePhone?: string | null | undefined;
        country?: string | null | undefined;
        division?: string | null | undefined;
        district?: string | null | undefined;
        upazila?: string | null | undefined;
        area?: string | null | undefined;
        apartment?: string | null | undefined;
        postalCode?: string | null | undefined;
        shippingMethodId?: string | number | null | undefined;
        couponCode?: string | null | undefined;
        orderNote?: string | null | undefined;
        checkoutNotes?: string | null | undefined;
        paymentMethod?: "cod" | "bkash" | "nagad" | "rocket" | "bank" | "stripe" | "sslcommerz" | "paypal" | undefined;
        transactionId?: string | null | undefined;
        senderNumber?: string | null | undefined;
        paymentScreenshot?: string | null | undefined;
        amountSent?: string | number | null | undefined;
        paymentInstructions?: string | null | undefined;
        taxAmount?: string | number | null | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name: string;
        phone: string;
        address: string;
        shippingArea: string;
        items: {
            productId: number;
            quantity: number;
            size?: string | null | undefined;
            color?: string | null | undefined;
        }[];
        email?: string | null | undefined;
        shippingCost?: string | number | null | undefined;
        alternativePhone?: string | null | undefined;
        country?: string | null | undefined;
        division?: string | null | undefined;
        district?: string | null | undefined;
        upazila?: string | null | undefined;
        area?: string | null | undefined;
        apartment?: string | null | undefined;
        postalCode?: string | null | undefined;
        shippingMethodId?: string | number | null | undefined;
        couponCode?: string | null | undefined;
        orderNote?: string | null | undefined;
        checkoutNotes?: string | null | undefined;
        paymentMethod?: "cod" | "bkash" | "nagad" | "rocket" | "bank" | "stripe" | "sslcommerz" | "paypal" | undefined;
        transactionId?: string | null | undefined;
        senderNumber?: string | null | undefined;
        paymentScreenshot?: string | null | undefined;
        amountSent?: string | number | null | undefined;
        paymentInstructions?: string | null | undefined;
        taxAmount?: string | number | null | undefined;
    };
}, {
    body: {
        name: string;
        phone: string;
        address: string;
        shippingArea: string;
        items: {
            productId: number;
            quantity: number;
            size?: string | null | undefined;
            color?: string | null | undefined;
        }[];
        email?: string | null | undefined;
        shippingCost?: string | number | null | undefined;
        alternativePhone?: string | null | undefined;
        country?: string | null | undefined;
        division?: string | null | undefined;
        district?: string | null | undefined;
        upazila?: string | null | undefined;
        area?: string | null | undefined;
        apartment?: string | null | undefined;
        postalCode?: string | null | undefined;
        shippingMethodId?: string | number | null | undefined;
        couponCode?: string | null | undefined;
        orderNote?: string | null | undefined;
        checkoutNotes?: string | null | undefined;
        paymentMethod?: "cod" | "bkash" | "nagad" | "rocket" | "bank" | "stripe" | "sslcommerz" | "paypal" | undefined;
        transactionId?: string | null | undefined;
        senderNumber?: string | null | undefined;
        paymentScreenshot?: string | null | undefined;
        amountSent?: string | number | null | undefined;
        paymentInstructions?: string | null | undefined;
        taxAmount?: string | number | null | undefined;
    };
}>;
export declare const updateOrderStatusSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        status: z.ZodEnum<["pending", "payment_pending", "payment_verification", "confirmed", "processing", "packed", "shipped", "out_for_delivery", "delivered", "returned", "cancelled", "refunded"]>;
        note: z.ZodOptional<z.ZodString>;
        trackingNumber: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        status: "pending" | "payment_pending" | "payment_verification" | "refunded" | "confirmed" | "processing" | "packed" | "shipped" | "out_for_delivery" | "delivered" | "returned" | "cancelled";
        note?: string | undefined;
        trackingNumber?: string | undefined;
    }, {
        status: "pending" | "payment_pending" | "payment_verification" | "refunded" | "confirmed" | "processing" | "packed" | "shipped" | "out_for_delivery" | "delivered" | "returned" | "cancelled";
        note?: string | undefined;
        trackingNumber?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    params: {
        id: string;
    };
    body: {
        status: "pending" | "payment_pending" | "payment_verification" | "refunded" | "confirmed" | "processing" | "packed" | "shipped" | "out_for_delivery" | "delivered" | "returned" | "cancelled";
        note?: string | undefined;
        trackingNumber?: string | undefined;
    };
}, {
    params: {
        id: string;
    };
    body: {
        status: "pending" | "payment_pending" | "payment_verification" | "refunded" | "confirmed" | "processing" | "packed" | "shipped" | "out_for_delivery" | "delivered" | "returned" | "cancelled";
        note?: string | undefined;
        trackingNumber?: string | undefined;
    };
}>;
export declare const verifyPaymentSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        action: z.ZodEnum<["verified", "rejected"]>;
        note: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        action: "verified" | "rejected";
        note?: string | undefined;
    }, {
        action: "verified" | "rejected";
        note?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    params: {
        id: string;
    };
    body: {
        action: "verified" | "rejected";
        note?: string | undefined;
    };
}, {
    params: {
        id: string;
    };
    body: {
        action: "verified" | "rejected";
        note?: string | undefined;
    };
}>;
export declare const adminNoteSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        note: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        note: string;
    }, {
        note: string;
    }>;
}, "strip", z.ZodTypeAny, {
    params: {
        id: string;
    };
    body: {
        note: string;
    };
}, {
    params: {
        id: string;
    };
    body: {
        note: string;
    };
}>;
export declare const orderIdSchema: z.ZodObject<{
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
export declare const orderListSchema: z.ZodObject<{
    query: z.ZodObject<{
        page: z.ZodOptional<z.ZodString>;
        limit: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodString>;
        search: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        status?: string | undefined;
        search?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }, {
        status?: string | undefined;
        search?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        status?: string | undefined;
        search?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}, {
    query: {
        status?: string | undefined;
        search?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}>;
export declare const trackOrderSchema: z.ZodObject<{
    body: z.ZodObject<{
        orderId: z.ZodString;
        phone: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        phone: string;
        orderId: string;
    }, {
        phone: string;
        orderId: string;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        phone: string;
        orderId: string;
    };
}, {
    body: {
        phone: string;
        orderId: string;
    };
}>;
//# sourceMappingURL=order.schema.d.ts.map