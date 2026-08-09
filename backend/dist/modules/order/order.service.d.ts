import { CreateOrderInput, UpdateOrderStatusInput, VerifyPaymentInput } from "./order.interface";
type CheckoutAuth = {
    token: string;
    user: {
        id: number;
        name: string;
        phone: string;
        role: "admin" | "manager" | "user";
    };
};
export declare const create: (input: CreateOrderInput) => Promise<{
    order: any;
    auth: CheckoutAuth | null;
}>;
export declare const getAll: (page?: number, limit?: number, status?: string, search?: string) => Promise<{
    data: any[];
    pagination: {
        page: number;
        limit: number;
        total: number;
        totalPages: number;
    };
}>;
export declare const getById: (id: number) => Promise<any>;
export declare const updateStatus: (id: number, input: UpdateOrderStatusInput) => Promise<any>;
export declare const verifyPayment: (id: number, input: VerifyPaymentInput) => Promise<any>;
export declare const updateAdminNotes: (id: number, note: string, userId?: number) => Promise<any>;
export declare const getMyOrders: (userId: number) => Promise<any[]>;
export declare const trackOrder: (orderId: string, phone: string) => Promise<any>;
export declare const getInvoice: (id: number) => Promise<any>;
export declare const remove: (id: number) => Promise<{
    success: boolean;
}>;
export declare const getStats: () => Promise<{
    totalOrders: number;
    totalRevenue: number;
    pendingOrders: number;
    pendingPayments: number;
}>;
export {};
//# sourceMappingURL=order.service.d.ts.map