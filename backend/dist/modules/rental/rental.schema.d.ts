import { z } from "zod";
export declare const rentalListSchema: z.ZodObject<{
    query: z.ZodObject<{
        page: z.ZodOptional<z.ZodString>;
        limit: z.ZodOptional<z.ZodString>;
        search: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodEnum<["reserved", "rented", "returned", "overdue", "cancelled"]>>;
        paymentStatus: z.ZodOptional<z.ZodEnum<["pending", "partial", "paid", "refunded"]>>;
    }, "strip", z.ZodTypeAny, {
        status?: "returned" | "cancelled" | "reserved" | "rented" | "overdue" | undefined;
        search?: string | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }, {
        status?: "returned" | "cancelled" | "reserved" | "rented" | "overdue" | undefined;
        search?: string | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        status?: "returned" | "cancelled" | "reserved" | "rented" | "overdue" | undefined;
        search?: string | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}, {
    query: {
        status?: "returned" | "cancelled" | "reserved" | "rented" | "overdue" | undefined;
        search?: string | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}>;
export declare const rentalIdSchema: z.ZodObject<{
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
export declare const rentalCreateSchema: z.ZodObject<{
    body: z.ZodObject<{
        rentalItem: z.ZodString;
        productId: z.ZodNullable<z.ZodOptional<z.ZodNumber>>;
        customerName: z.ZodString;
        phone: z.ZodString;
        email: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        userId: z.ZodNullable<z.ZodOptional<z.ZodNumber>>;
        quantity: z.ZodDefault<z.ZodOptional<z.ZodNumber>>;
        startDate: z.ZodString;
        endDate: z.ZodString;
        returnDate: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        rateType: z.ZodOptional<z.ZodEnum<["daily", "weekly", "monthly"]>>;
        dailyRate: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        weeklyRate: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        monthlyRate: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        rate: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        durationUnits: z.ZodDefault<z.ZodOptional<z.ZodNumber>>;
        securityDeposit: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        discount: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        additionalCharge: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        totalAmount: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        paymentStatus: z.ZodOptional<z.ZodEnum<["pending", "partial", "paid", "refunded"]>>;
        status: z.ZodOptional<z.ZodEnum<["reserved", "rented", "returned", "overdue", "cancelled"]>>;
        notes: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        attachmentUrl: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        createdById: z.ZodNullable<z.ZodOptional<z.ZodNumber>>;
    }, "strip", z.ZodTypeAny, {
        startDate: string;
        endDate: string;
        phone: string;
        customerName: string;
        quantity: number;
        rentalItem: string;
        durationUnits: number;
        status?: "returned" | "cancelled" | "reserved" | "rented" | "overdue" | undefined;
        discount?: string | number | undefined;
        email?: string | null | undefined;
        notes?: string | null | undefined;
        productId?: number | null | undefined;
        userId?: number | null | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        attachmentUrl?: string | null | undefined;
        createdById?: number | null | undefined;
        totalAmount?: string | number | undefined;
        returnDate?: string | null | undefined;
        rateType?: "daily" | "weekly" | "monthly" | undefined;
        dailyRate?: string | number | undefined;
        weeklyRate?: string | number | undefined;
        monthlyRate?: string | number | undefined;
        rate?: string | number | undefined;
        securityDeposit?: string | number | undefined;
        additionalCharge?: string | number | undefined;
    }, {
        startDate: string;
        endDate: string;
        phone: string;
        customerName: string;
        rentalItem: string;
        status?: "returned" | "cancelled" | "reserved" | "rented" | "overdue" | undefined;
        discount?: string | number | undefined;
        email?: string | null | undefined;
        notes?: string | null | undefined;
        productId?: number | null | undefined;
        userId?: number | null | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        quantity?: number | undefined;
        attachmentUrl?: string | null | undefined;
        createdById?: number | null | undefined;
        totalAmount?: string | number | undefined;
        returnDate?: string | null | undefined;
        rateType?: "daily" | "weekly" | "monthly" | undefined;
        dailyRate?: string | number | undefined;
        weeklyRate?: string | number | undefined;
        monthlyRate?: string | number | undefined;
        rate?: string | number | undefined;
        durationUnits?: number | undefined;
        securityDeposit?: string | number | undefined;
        additionalCharge?: string | number | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        startDate: string;
        endDate: string;
        phone: string;
        customerName: string;
        quantity: number;
        rentalItem: string;
        durationUnits: number;
        status?: "returned" | "cancelled" | "reserved" | "rented" | "overdue" | undefined;
        discount?: string | number | undefined;
        email?: string | null | undefined;
        notes?: string | null | undefined;
        productId?: number | null | undefined;
        userId?: number | null | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        attachmentUrl?: string | null | undefined;
        createdById?: number | null | undefined;
        totalAmount?: string | number | undefined;
        returnDate?: string | null | undefined;
        rateType?: "daily" | "weekly" | "monthly" | undefined;
        dailyRate?: string | number | undefined;
        weeklyRate?: string | number | undefined;
        monthlyRate?: string | number | undefined;
        rate?: string | number | undefined;
        securityDeposit?: string | number | undefined;
        additionalCharge?: string | number | undefined;
    };
}, {
    body: {
        startDate: string;
        endDate: string;
        phone: string;
        customerName: string;
        rentalItem: string;
        status?: "returned" | "cancelled" | "reserved" | "rented" | "overdue" | undefined;
        discount?: string | number | undefined;
        email?: string | null | undefined;
        notes?: string | null | undefined;
        productId?: number | null | undefined;
        userId?: number | null | undefined;
        paymentStatus?: "pending" | "refunded" | "partial" | "paid" | undefined;
        quantity?: number | undefined;
        attachmentUrl?: string | null | undefined;
        createdById?: number | null | undefined;
        totalAmount?: string | number | undefined;
        returnDate?: string | null | undefined;
        rateType?: "daily" | "weekly" | "monthly" | undefined;
        dailyRate?: string | number | undefined;
        weeklyRate?: string | number | undefined;
        monthlyRate?: string | number | undefined;
        rate?: string | number | undefined;
        durationUnits?: number | undefined;
        securityDeposit?: string | number | undefined;
        additionalCharge?: string | number | undefined;
    };
}>;
export declare const rentalUpdateSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodEffects<z.ZodString, string, string>;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        rentalItem: z.ZodOptional<z.ZodString>;
        productId: z.ZodNullable<z.ZodOptional<z.ZodNumber>>;
        customerName: z.ZodOptional<z.ZodString>;
        phone: z.ZodOptional<z.ZodString>;
        email: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        userId: z.ZodNullable<z.ZodOptional<z.ZodNumber>>;
        quantity: z.ZodOptional<z.ZodNumber>;
        startDate: z.ZodOptional<z.ZodString>;
        endDate: z.ZodOptional<z.ZodString>;
        returnDate: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        rateType: z.ZodOptional<z.ZodEnum<["daily", "weekly", "monthly"]>>;
        dailyRate: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        weeklyRate: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        monthlyRate: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        rate: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        durationUnits: z.ZodOptional<z.ZodNumber>;
        securityDeposit: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        discount: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        additionalCharge: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        totalAmount: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        paymentStatus: z.ZodOptional<z.ZodEnum<["pending", "partial", "paid", "refunded"]>>;
        status: z.ZodOptional<z.ZodEnum<["reserved", "rented", "returned", "overdue", "cancelled"]>>;
        notes: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        attachmentUrl: z.ZodNullable<z.ZodOptional<z.ZodString>>;
        createdById: z.ZodNullable<z.ZodOptional<z.ZodNumber>>;
    }, "strip", z.ZodTypeAny, {
        status?: "returned" | "cancelled" | "reserved" | "rented" | "overdue" | undefined;
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
        createdById?: number | null | undefined;
        totalAmount?: string | number | undefined;
        rentalItem?: string | undefined;
        returnDate?: string | null | undefined;
        rateType?: "daily" | "weekly" | "monthly" | undefined;
        dailyRate?: string | number | undefined;
        weeklyRate?: string | number | undefined;
        monthlyRate?: string | number | undefined;
        rate?: string | number | undefined;
        durationUnits?: number | undefined;
        securityDeposit?: string | number | undefined;
        additionalCharge?: string | number | undefined;
    }, {
        status?: "returned" | "cancelled" | "reserved" | "rented" | "overdue" | undefined;
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
        createdById?: number | null | undefined;
        totalAmount?: string | number | undefined;
        rentalItem?: string | undefined;
        returnDate?: string | null | undefined;
        rateType?: "daily" | "weekly" | "monthly" | undefined;
        dailyRate?: string | number | undefined;
        weeklyRate?: string | number | undefined;
        monthlyRate?: string | number | undefined;
        rate?: string | number | undefined;
        durationUnits?: number | undefined;
        securityDeposit?: string | number | undefined;
        additionalCharge?: string | number | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        status?: "returned" | "cancelled" | "reserved" | "rented" | "overdue" | undefined;
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
        createdById?: number | null | undefined;
        totalAmount?: string | number | undefined;
        rentalItem?: string | undefined;
        returnDate?: string | null | undefined;
        rateType?: "daily" | "weekly" | "monthly" | undefined;
        dailyRate?: string | number | undefined;
        weeklyRate?: string | number | undefined;
        monthlyRate?: string | number | undefined;
        rate?: string | number | undefined;
        durationUnits?: number | undefined;
        securityDeposit?: string | number | undefined;
        additionalCharge?: string | number | undefined;
    };
    params: {
        id: string;
    };
}, {
    body: {
        status?: "returned" | "cancelled" | "reserved" | "rented" | "overdue" | undefined;
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
        createdById?: number | null | undefined;
        totalAmount?: string | number | undefined;
        rentalItem?: string | undefined;
        returnDate?: string | null | undefined;
        rateType?: "daily" | "weekly" | "monthly" | undefined;
        dailyRate?: string | number | undefined;
        weeklyRate?: string | number | undefined;
        monthlyRate?: string | number | undefined;
        rate?: string | number | undefined;
        durationUnits?: number | undefined;
        securityDeposit?: string | number | undefined;
        additionalCharge?: string | number | undefined;
    };
    params: {
        id: string;
    };
}>;
//# sourceMappingURL=rental.schema.d.ts.map