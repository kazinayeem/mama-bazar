export interface ExpenseQuery {
    page?: number;
    limit?: number;
    search?: string;
    status?: string;
    memberId?: string;
    categoryId?: string;
    paymentMethod?: string;
    dateFrom?: string;
    dateTo?: string;
    amountMin?: string;
    amountMax?: string;
}
export declare const listExpenses: (query: ExpenseQuery) => Promise<{
    data: {
        id: number;
        title: string;
        description: string | null;
        categoryId: number | null;
        categoryName: string | null;
        amount: string;
        paymentMethod: string;
        vendor: string | null;
        memberId: number | null;
        memberName: string | null;
        expenseDate: Date;
        referenceNumber: string | null;
        attachmentUrl: string | null;
        notes: string | null;
        status: "pending" | "rejected" | "approved";
        createdById: number | null;
        createdByName: string | null;
        createdAt: Date;
        updatedAt: Date;
    }[];
    pagination: {
        page: number;
        limit: number;
        total: number;
        totalPages: number;
    };
}>;
export declare const getExpense: (id: number) => Promise<{
    id: number;
    title: string;
    description: string | null;
    categoryId: number | null;
    categoryName: string | null;
    amount: string;
    paymentMethod: string;
    vendor: string | null;
    memberId: number | null;
    memberName: string | null;
    expenseDate: Date;
    referenceNumber: string | null;
    attachmentUrl: string | null;
    notes: string | null;
    status: "pending" | "rejected" | "approved";
    createdById: number | null;
    createdByName: string | null;
    createdAt: Date;
    updatedAt: Date;
}>;
export declare const createExpense: (input: {
    title: string;
    description?: string | null;
    categoryId?: number | null;
    amount: string | number;
    paymentMethod?: string;
    vendor?: string | null;
    memberId?: number | null;
    memberName?: string | null;
    expenseDate: string;
    referenceNumber?: string | null;
    attachmentUrl?: string | null;
    notes?: string | null;
    status?: string;
}, authUser?: {
    id?: number;
    name?: string;
}) => Promise<{
    id: number;
    title: string;
    description: string | null;
    categoryId: number | null;
    categoryName: string | null;
    amount: string;
    paymentMethod: string;
    vendor: string | null;
    memberId: number | null;
    memberName: string | null;
    expenseDate: Date;
    referenceNumber: string | null;
    attachmentUrl: string | null;
    notes: string | null;
    status: "pending" | "rejected" | "approved";
    createdById: number | null;
    createdByName: string | null;
    createdAt: Date;
    updatedAt: Date;
}>;
export declare const updateExpense: (id: number, input: Record<string, unknown>, authUser?: {
    id?: number;
    name?: string;
}) => Promise<{
    id: number;
    title: string;
    description: string | null;
    categoryId: number | null;
    categoryName: string | null;
    amount: string;
    paymentMethod: string;
    vendor: string | null;
    memberId: number | null;
    memberName: string | null;
    expenseDate: Date;
    referenceNumber: string | null;
    attachmentUrl: string | null;
    notes: string | null;
    status: "pending" | "rejected" | "approved";
    createdById: number | null;
    createdByName: string | null;
    createdAt: Date;
    updatedAt: Date;
}>;
export declare const deleteExpense: (id: number) => Promise<{
    success: boolean;
}>;
export declare const listExpenseCategories: () => Promise<{
    id: number;
    name: string;
    description: string | null;
    status: "active" | "inactive";
    sortOrder: number;
    createdAt: Date;
}[]>;
export declare const createExpenseCategory: (input: {
    name: string;
    description?: string | null;
    sortOrder?: number;
    status?: string;
}) => Promise<{
    id: number;
    name: string;
    description: string | null;
    sortOrder: number;
    status: "active" | "inactive";
    createdAt: Date;
}>;
export declare const updateExpenseCategory: (id: number, input: Record<string, unknown>) => Promise<{
    id: number;
    name: string;
    description: string | null;
    sortOrder: number;
    status: "active" | "inactive";
    createdAt: Date;
}>;
export declare const deleteExpenseCategory: (id: number) => Promise<{
    success: boolean;
    usageCount: number;
    message: string;
} | {
    success: boolean;
    usageCount: number;
    message?: undefined;
}>;
export declare const listMembers: () => Promise<{
    id: number;
    name: string;
    phone: string;
    role: "admin" | "manager" | "user";
}[]>;
export declare const getSummary: (params?: {
    dateFrom?: string;
    dateTo?: string;
}) => Promise<{
    total: number;
    totalCount: number;
    thisMonth: number;
    thisMonthCount: number;
    thisWeek: number;
    thisWeekCount: number;
    today: number;
    todayCount: number;
}>;
export declare const getExpenseByMember: (params: {
    memberId?: string;
    dateFrom?: string;
    dateTo?: string;
}) => Promise<{
    memberId: number | null;
    memberName: string;
    total: number;
    count: number;
}[]>;
export declare const getExpenseByCategory: (params: {
    dateFrom?: string;
    dateTo?: string;
    memberId?: string;
}) => Promise<{
    categoryId: number | null;
    categoryName: string;
    total: number;
    count: number;
}[]>;
export declare const getMonthlyReport: (params: {
    year?: string;
    month?: string;
    memberId?: string;
    categoryId?: string;
    page?: number;
    limit?: number;
}) => Promise<{
    year: number;
    month: number;
    total: number;
    count: number;
    average: number;
    highest: number;
    lowest: number;
    byMember: {
        memberId: number | null;
        memberName: string;
        total: number;
        count: number;
    }[];
    byCategory: {
        categoryId: number | null;
        categoryName: string;
        total: number;
        count: number;
    }[];
    expenses: {
        id: number;
        title: string;
        description: string | null;
        categoryId: number | null;
        categoryName: string | null;
        amount: string;
        paymentMethod: string;
        vendor: string | null;
        memberId: number | null;
        memberName: string | null;
        expenseDate: Date;
        referenceNumber: string | null;
        attachmentUrl: string | null;
        notes: string | null;
        status: "pending" | "rejected" | "approved";
        createdById: number | null;
        createdByName: string | null;
        createdAt: Date;
        updatedAt: Date;
    }[];
    pagination: {
        page: number;
        limit: number;
        total: number;
        totalPages: number;
    };
}>;
export declare const getMonthlyTrend: (params: {
    year?: string;
}) => Promise<{
    year: number;
    data: {
        month: number;
        label: string;
        total: number;
        count: number;
    }[];
}>;
export declare const getRangeReport: (params: {
    dateFrom?: string;
    dateTo?: string;
    memberId?: string;
    categoryId?: string;
    status?: string;
}) => Promise<{
    dateFrom: string | null;
    dateTo: string | null;
    total: number;
    count: number;
    byMember: {
        memberId: number | null;
        memberName: string;
        total: number;
        count: number;
    }[];
    byCategory: {
        categoryId: number | null;
        categoryName: string;
        total: number;
        count: number;
    }[];
    expenses: {
        id: number;
        title: string;
        description: string | null;
        categoryId: number | null;
        categoryName: string | null;
        amount: string;
        paymentMethod: string;
        vendor: string | null;
        memberId: number | null;
        memberName: string | null;
        expenseDate: Date;
        referenceNumber: string | null;
        attachmentUrl: string | null;
        notes: string | null;
        status: "pending" | "rejected" | "approved";
        createdById: number | null;
        createdByName: string | null;
        createdAt: Date;
        updatedAt: Date;
    }[];
}>;
export declare const getProfitOverview: (params: {
    year?: string;
    month?: string;
}) => Promise<{
    year: number;
    month: number;
    revenue: number;
    productCost: number;
    operatingExpenses: number;
    netProfit: number;
    hasRevenueData: boolean;
}>;
export declare const exportExpensesCsv: (query: ExpenseQuery) => Promise<{
    csv: string;
    count: number;
}>;
//# sourceMappingURL=expense.service.d.ts.map