export interface CostQuery {
    page?: number;
    limit?: number;
    search?: string;
    costType?: string;
}
export declare const listCosts: (query: CostQuery) => Promise<{
    data: {
        id: number;
        title: string;
        costType: string;
        quantity: string;
        unitCost: string;
        totalCost: string;
        supplierName: string | null;
        productName: string | null;
        orderOrderId: number | null;
        bookingId: number | null;
        costDate: Date;
        paymentMethod: string;
        notes: string | null;
        attachmentUrl: string | null;
        createdAt: Date;
    }[];
    pagination: {
        page: number;
        limit: number;
        total: number;
        totalPages: number;
    };
}>;
export declare const getCost: (id: number) => Promise<{
    id: number;
    title: string;
    costType: string;
    quantity: string;
    unitCost: string;
    totalCost: string;
    supplierId: number | null;
    supplierName: string | null;
    productId: number | null;
    productName: string | null;
    orderId: number | null;
    bookingId: number | null;
    costDate: Date;
    paymentMethod: string;
    notes: string | null;
    attachmentUrl: string | null;
    createdAt: Date;
    updatedAt: Date;
}>;
export declare const createCost: (input: {
    title: string;
    costType?: string;
    quantity?: string | number;
    unitCost?: string | number;
    totalCost?: string | number;
    supplierId?: number | null;
    productId?: number | null;
    orderId?: number | null;
    bookingId?: number | null;
    costDate: string;
    paymentMethod?: string;
    notes?: string | null;
    attachmentUrl?: string | null;
}) => Promise<import("mysql2").ResultSetHeader>;
export declare const updateCost: (id: number, input: Record<string, unknown>) => Promise<{
    id: number;
    title: string;
    costType: string;
    quantity: string;
    unitCost: string;
    totalCost: string;
    supplierId: number | null;
    supplierName: string | null;
    productId: number | null;
    productName: string | null;
    orderId: number | null;
    bookingId: number | null;
    costDate: Date;
    paymentMethod: string;
    notes: string | null;
    attachmentUrl: string | null;
    createdAt: Date;
    updatedAt: Date;
}>;
export declare const deleteCost: (id: number) => Promise<{
    success: boolean;
}>;
//# sourceMappingURL=cost.service.d.ts.map