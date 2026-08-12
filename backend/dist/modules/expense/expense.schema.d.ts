import { z } from "zod";
export declare const expenseListSchema: z.ZodObject<{
    query: z.ZodObject<{
        page: z.ZodOptional<z.ZodString>;
        limit: z.ZodOptional<z.ZodString>;
        search: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodEnum<["pending", "approved", "rejected"]>>;
        memberId: z.ZodOptional<z.ZodString>;
        categoryId: z.ZodOptional<z.ZodString>;
        paymentMethod: z.ZodOptional<z.ZodString>;
        dateFrom: z.ZodOptional<z.ZodEffects<z.ZodString, string, string>>;
        dateTo: z.ZodOptional<z.ZodEffects<z.ZodString, string, string>>;
        amountMin: z.ZodOptional<z.ZodString>;
        amountMax: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        status?: "pending" | "rejected" | "approved" | undefined;
        search?: string | undefined;
        categoryId?: string | undefined;
        paymentMethod?: string | undefined;
        memberId?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
        amountMin?: string | undefined;
        amountMax?: string | undefined;
    }, {
        status?: "pending" | "rejected" | "approved" | undefined;
        search?: string | undefined;
        categoryId?: string | undefined;
        paymentMethod?: string | undefined;
        memberId?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
        amountMin?: string | undefined;
        amountMax?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        status?: "pending" | "rejected" | "approved" | undefined;
        search?: string | undefined;
        categoryId?: string | undefined;
        paymentMethod?: string | undefined;
        memberId?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
        amountMin?: string | undefined;
        amountMax?: string | undefined;
    };
}, {
    query: {
        status?: "pending" | "rejected" | "approved" | undefined;
        search?: string | undefined;
        categoryId?: string | undefined;
        paymentMethod?: string | undefined;
        memberId?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
        amountMin?: string | undefined;
        amountMax?: string | undefined;
    };
}>;
export declare const expenseIdSchema: z.ZodObject<{
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
export declare const expenseCreateSchema: z.ZodObject<{
    body: z.ZodObject<{
        title: z.ZodString;
        description: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        categoryId: z.ZodOptional<z.ZodNullable<z.ZodNumber>>;
        amount: z.ZodEffects<z.ZodUnion<[z.ZodString, z.ZodNumber]>, string | number, string | number>;
        paymentMethod: z.ZodOptional<z.ZodString>;
        vendor: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        memberId: z.ZodOptional<z.ZodNullable<z.ZodNumber>>;
        expenseDate: z.ZodEffects<z.ZodString, string, string>;
        referenceNumber: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        attachmentUrl: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        notes: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        status: z.ZodOptional<z.ZodEnum<["pending", "approved", "rejected"]>>;
    }, "strip", z.ZodTypeAny, {
        title: string;
        amount: string | number;
        expenseDate: string;
        description?: string | null | undefined;
        status?: "pending" | "rejected" | "approved" | undefined;
        categoryId?: number | null | undefined;
        notes?: string | null | undefined;
        vendor?: string | null | undefined;
        paymentMethod?: string | undefined;
        memberId?: number | null | undefined;
        referenceNumber?: string | null | undefined;
        attachmentUrl?: string | null | undefined;
    }, {
        title: string;
        amount: string | number;
        expenseDate: string;
        description?: string | null | undefined;
        status?: "pending" | "rejected" | "approved" | undefined;
        categoryId?: number | null | undefined;
        notes?: string | null | undefined;
        vendor?: string | null | undefined;
        paymentMethod?: string | undefined;
        memberId?: number | null | undefined;
        referenceNumber?: string | null | undefined;
        attachmentUrl?: string | null | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        title: string;
        amount: string | number;
        expenseDate: string;
        description?: string | null | undefined;
        status?: "pending" | "rejected" | "approved" | undefined;
        categoryId?: number | null | undefined;
        notes?: string | null | undefined;
        vendor?: string | null | undefined;
        paymentMethod?: string | undefined;
        memberId?: number | null | undefined;
        referenceNumber?: string | null | undefined;
        attachmentUrl?: string | null | undefined;
    };
}, {
    body: {
        title: string;
        amount: string | number;
        expenseDate: string;
        description?: string | null | undefined;
        status?: "pending" | "rejected" | "approved" | undefined;
        categoryId?: number | null | undefined;
        notes?: string | null | undefined;
        vendor?: string | null | undefined;
        paymentMethod?: string | undefined;
        memberId?: number | null | undefined;
        referenceNumber?: string | null | undefined;
        attachmentUrl?: string | null | undefined;
    };
}>;
export declare const expenseUpdateSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodEffects<z.ZodString, string, string>;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        title: z.ZodOptional<z.ZodString>;
        description: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        categoryId: z.ZodOptional<z.ZodNullable<z.ZodNumber>>;
        amount: z.ZodOptional<z.ZodEffects<z.ZodUnion<[z.ZodString, z.ZodNumber]>, string | number, string | number>>;
        paymentMethod: z.ZodOptional<z.ZodString>;
        vendor: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        memberId: z.ZodOptional<z.ZodNullable<z.ZodNumber>>;
        expenseDate: z.ZodOptional<z.ZodEffects<z.ZodString, string, string>>;
        referenceNumber: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        attachmentUrl: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        notes: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        status: z.ZodOptional<z.ZodEnum<["pending", "approved", "rejected"]>>;
    }, "strip", z.ZodTypeAny, {
        description?: string | null | undefined;
        status?: "pending" | "rejected" | "approved" | undefined;
        title?: string | undefined;
        categoryId?: number | null | undefined;
        notes?: string | null | undefined;
        vendor?: string | null | undefined;
        paymentMethod?: string | undefined;
        amount?: string | number | undefined;
        memberId?: number | null | undefined;
        expenseDate?: string | undefined;
        referenceNumber?: string | null | undefined;
        attachmentUrl?: string | null | undefined;
    }, {
        description?: string | null | undefined;
        status?: "pending" | "rejected" | "approved" | undefined;
        title?: string | undefined;
        categoryId?: number | null | undefined;
        notes?: string | null | undefined;
        vendor?: string | null | undefined;
        paymentMethod?: string | undefined;
        amount?: string | number | undefined;
        memberId?: number | null | undefined;
        expenseDate?: string | undefined;
        referenceNumber?: string | null | undefined;
        attachmentUrl?: string | null | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        description?: string | null | undefined;
        status?: "pending" | "rejected" | "approved" | undefined;
        title?: string | undefined;
        categoryId?: number | null | undefined;
        notes?: string | null | undefined;
        vendor?: string | null | undefined;
        paymentMethod?: string | undefined;
        amount?: string | number | undefined;
        memberId?: number | null | undefined;
        expenseDate?: string | undefined;
        referenceNumber?: string | null | undefined;
        attachmentUrl?: string | null | undefined;
    };
    params: {
        id: string;
    };
}, {
    body: {
        description?: string | null | undefined;
        status?: "pending" | "rejected" | "approved" | undefined;
        title?: string | undefined;
        categoryId?: number | null | undefined;
        notes?: string | null | undefined;
        vendor?: string | null | undefined;
        paymentMethod?: string | undefined;
        amount?: string | number | undefined;
        memberId?: number | null | undefined;
        expenseDate?: string | undefined;
        referenceNumber?: string | null | undefined;
        attachmentUrl?: string | null | undefined;
    };
    params: {
        id: string;
    };
}>;
export declare const expenseSummarySchema: z.ZodObject<{
    query: z.ZodObject<{
        dateFrom: z.ZodOptional<z.ZodEffects<z.ZodString, string, string>>;
        dateTo: z.ZodOptional<z.ZodEffects<z.ZodString, string, string>>;
    }, "strip", z.ZodTypeAny, {
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
    }, {
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
    };
}, {
    query: {
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
    };
}>;
export declare const expenseMemberSchema: z.ZodObject<{
    query: z.ZodObject<{
        memberId: z.ZodOptional<z.ZodString>;
        dateFrom: z.ZodOptional<z.ZodEffects<z.ZodString, string, string>>;
        dateTo: z.ZodOptional<z.ZodEffects<z.ZodString, string, string>>;
    }, "strip", z.ZodTypeAny, {
        memberId?: string | undefined;
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
    }, {
        memberId?: string | undefined;
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        memberId?: string | undefined;
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
    };
}, {
    query: {
        memberId?: string | undefined;
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
    };
}>;
export declare const expenseByCategorySchema: z.ZodObject<{
    query: z.ZodObject<{
        dateFrom: z.ZodOptional<z.ZodEffects<z.ZodString, string, string>>;
        dateTo: z.ZodOptional<z.ZodEffects<z.ZodString, string, string>>;
    }, "strip", z.ZodTypeAny, {
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
    }, {
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
    };
}, {
    query: {
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
    };
}>;
export declare const expenseMonthlyReportSchema: z.ZodObject<{
    query: z.ZodObject<{
        year: z.ZodOptional<z.ZodString>;
        month: z.ZodOptional<z.ZodString>;
        memberId: z.ZodOptional<z.ZodString>;
        categoryId: z.ZodOptional<z.ZodString>;
        page: z.ZodOptional<z.ZodString>;
        limit: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        categoryId?: string | undefined;
        memberId?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
        year?: string | undefined;
        month?: string | undefined;
    }, {
        categoryId?: string | undefined;
        memberId?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
        year?: string | undefined;
        month?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        categoryId?: string | undefined;
        memberId?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
        year?: string | undefined;
        month?: string | undefined;
    };
}, {
    query: {
        categoryId?: string | undefined;
        memberId?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
        year?: string | undefined;
        month?: string | undefined;
    };
}>;
export declare const expenseTrendSchema: z.ZodObject<{
    query: z.ZodObject<{
        year: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        year?: string | undefined;
    }, {
        year?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        year?: string | undefined;
    };
}, {
    query: {
        year?: string | undefined;
    };
}>;
export declare const expenseRangeReportSchema: z.ZodObject<{
    query: z.ZodObject<{
        dateFrom: z.ZodOptional<z.ZodEffects<z.ZodString, string, string>>;
        dateTo: z.ZodOptional<z.ZodEffects<z.ZodString, string, string>>;
        memberId: z.ZodOptional<z.ZodString>;
        categoryId: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodEnum<["pending", "approved", "rejected"]>>;
    }, "strip", z.ZodTypeAny, {
        status?: "pending" | "rejected" | "approved" | undefined;
        categoryId?: string | undefined;
        memberId?: string | undefined;
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
    }, {
        status?: "pending" | "rejected" | "approved" | undefined;
        categoryId?: string | undefined;
        memberId?: string | undefined;
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        status?: "pending" | "rejected" | "approved" | undefined;
        categoryId?: string | undefined;
        memberId?: string | undefined;
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
    };
}, {
    query: {
        status?: "pending" | "rejected" | "approved" | undefined;
        categoryId?: string | undefined;
        memberId?: string | undefined;
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
    };
}>;
export declare const expenseProfitSchema: z.ZodObject<{
    query: z.ZodObject<{
        year: z.ZodOptional<z.ZodString>;
        month: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        year?: string | undefined;
        month?: string | undefined;
    }, {
        year?: string | undefined;
        month?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        year?: string | undefined;
        month?: string | undefined;
    };
}, {
    query: {
        year?: string | undefined;
        month?: string | undefined;
    };
}>;
export declare const expenseExportSchema: z.ZodObject<{
    query: z.ZodObject<{
        page: z.ZodOptional<z.ZodString>;
        limit: z.ZodOptional<z.ZodString>;
        search: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodEnum<["pending", "approved", "rejected"]>>;
        memberId: z.ZodOptional<z.ZodString>;
        categoryId: z.ZodOptional<z.ZodString>;
        paymentMethod: z.ZodOptional<z.ZodString>;
        dateFrom: z.ZodOptional<z.ZodEffects<z.ZodString, string, string>>;
        dateTo: z.ZodOptional<z.ZodEffects<z.ZodString, string, string>>;
        amountMin: z.ZodOptional<z.ZodString>;
        amountMax: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        status?: "pending" | "rejected" | "approved" | undefined;
        search?: string | undefined;
        categoryId?: string | undefined;
        paymentMethod?: string | undefined;
        memberId?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
        amountMin?: string | undefined;
        amountMax?: string | undefined;
    }, {
        status?: "pending" | "rejected" | "approved" | undefined;
        search?: string | undefined;
        categoryId?: string | undefined;
        paymentMethod?: string | undefined;
        memberId?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
        amountMin?: string | undefined;
        amountMax?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        status?: "pending" | "rejected" | "approved" | undefined;
        search?: string | undefined;
        categoryId?: string | undefined;
        paymentMethod?: string | undefined;
        memberId?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
        amountMin?: string | undefined;
        amountMax?: string | undefined;
    };
}, {
    query: {
        status?: "pending" | "rejected" | "approved" | undefined;
        search?: string | undefined;
        categoryId?: string | undefined;
        paymentMethod?: string | undefined;
        memberId?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
        dateFrom?: string | undefined;
        dateTo?: string | undefined;
        amountMin?: string | undefined;
        amountMax?: string | undefined;
    };
}>;
export declare const expenseCategoryCreateSchema: z.ZodObject<{
    body: z.ZodObject<{
        name: z.ZodString;
        description: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        sortOrder: z.ZodOptional<z.ZodNumber>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive"]>>;
    }, "strip", z.ZodTypeAny, {
        name: string;
        description?: string | null | undefined;
        sortOrder?: number | undefined;
        status?: "active" | "inactive" | undefined;
    }, {
        name: string;
        description?: string | null | undefined;
        sortOrder?: number | undefined;
        status?: "active" | "inactive" | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name: string;
        description?: string | null | undefined;
        sortOrder?: number | undefined;
        status?: "active" | "inactive" | undefined;
    };
}, {
    body: {
        name: string;
        description?: string | null | undefined;
        sortOrder?: number | undefined;
        status?: "active" | "inactive" | undefined;
    };
}>;
export declare const expenseCategoryUpdateSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodEffects<z.ZodString, string, string>;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        name: z.ZodOptional<z.ZodString>;
        description: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        sortOrder: z.ZodOptional<z.ZodNumber>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive"]>>;
    }, "strip", z.ZodTypeAny, {
        name?: string | undefined;
        description?: string | null | undefined;
        sortOrder?: number | undefined;
        status?: "active" | "inactive" | undefined;
    }, {
        name?: string | undefined;
        description?: string | null | undefined;
        sortOrder?: number | undefined;
        status?: "active" | "inactive" | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name?: string | undefined;
        description?: string | null | undefined;
        sortOrder?: number | undefined;
        status?: "active" | "inactive" | undefined;
    };
    params: {
        id: string;
    };
}, {
    body: {
        name?: string | undefined;
        description?: string | null | undefined;
        sortOrder?: number | undefined;
        status?: "active" | "inactive" | undefined;
    };
    params: {
        id: string;
    };
}>;
export declare const expenseCategoryIdSchema: z.ZodObject<{
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
//# sourceMappingURL=expense.schema.d.ts.map