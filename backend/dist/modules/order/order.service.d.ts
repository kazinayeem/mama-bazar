import { CreateOrderInput, UpdateOrderStatusInput, VerifyPaymentInput } from "./order.interface";
export declare const create: (input: CreateOrderInput) => Promise<{
    order: any;
    auth: null;
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
export declare const trackOrder: (orderId?: string, phone?: string) => Promise<{
    orderId: any;
    createdAt: any;
    status: any;
    paymentStatus: any;
    paymentMethod: any;
    shippingMethodName: any;
    courierTrackingNumber: any;
    subtotal: any;
    shippingCost: any;
    discount: any;
    tax: any;
    totalPrice: any;
    items: any;
    statusHistory: any;
}[]>;
export declare const getInvoice: (id: number) => Promise<any>;
export declare const getCustomerInvoice: (id: number, userId: number) => Promise<any>;
export declare const remove: (id: number) => Promise<{
    success: boolean;
}>;
export declare const getStats: () => Promise<{
    totalOrders: number;
    totalRevenue: number;
    pendingOrders: number;
    pendingPayments: number;
}>;
//# sourceMappingURL=order.service.d.ts.map