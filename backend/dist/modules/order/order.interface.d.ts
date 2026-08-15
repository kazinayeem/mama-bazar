export declare const ORDER_STATUSES: readonly ["pending", "payment_pending", "payment_verification", "confirmed", "processing", "packed", "shipped", "out_for_delivery", "delivered", "returned", "cancelled", "refunded"];
export declare const PAYMENT_STATUSES: readonly ["pending", "payment_pending", "payment_verification", "verified", "success", "failed", "rejected", "refunded"];
export declare const PAYMENT_METHODS: readonly ["cod", "bkash", "nagad", "rocket", "bank", "stripe", "sslcommerz", "paypal"];
export type OrderStatus = (typeof ORDER_STATUSES)[number];
export type PaymentStatus = (typeof PAYMENT_STATUSES)[number];
export type PaymentMethod = string;
export interface IOrder {
    id: number;
    orderId: string;
    userId?: number | null;
    customerName: string;
    phone: string;
    alternativePhone?: string | null;
    email?: string | null;
    country?: string | null;
    division?: string | null;
    district?: string | null;
    upazila?: string | null;
    area?: string | null;
    address: string;
    apartment?: string | null;
    postalCode?: string | null;
    shippingMethodId?: number | null;
    shippingMethodName?: string | null;
    shippingCost: string;
    subtotal: string;
    couponCode?: string | null;
    discount: string;
    tax: string;
    orderNote?: string | null;
    checkoutNotes?: string | null;
    adminNotes?: string | null;
    totalPrice: string;
    paymentMethod: PaymentMethod;
    transactionId?: string | null;
    senderNumber?: string | null;
    paymentScreenshot?: string | null;
    paymentDate?: Date | null;
    amountSent?: string | null;
    paymentInstructions?: string | null;
    courierTrackingNumber?: string | null;
    paymentStatus: PaymentStatus;
    status: OrderStatus;
    createdAt: Date;
}
export interface IOrderItem {
    id: number;
    orderId: number;
    productId: number;
    variantId?: number | null;
    size?: string | null;
    color?: string | null;
    quantity: number;
    price: string;
}
export interface CreateOrderInput {
    userId?: number;
    name: string;
    phone: string;
    alternativePhone?: string;
    email?: string;
    country?: string;
    division?: string;
    district?: string;
    upazila?: string;
    area?: string;
    apartment?: string;
    postalCode?: string;
    address: string;
    shippingArea: string;
    shippingCost?: number;
    shippingMethodId?: number;
    couponCode?: string;
    orderNote?: string;
    checkoutNotes?: string;
    paymentMethod: PaymentMethod;
    transactionId?: string;
    senderNumber?: string;
    paymentScreenshot?: string;
    amountSent?: number;
    paymentInstructions?: string;
    taxAmount?: number;
    items: {
        productId: number;
        variantId?: number;
        quantity: number;
        size?: string;
        color?: string;
    }[];
}
export interface UpdateOrderStatusInput {
    status: OrderStatus;
    note?: string;
    trackingNumber?: string;
    userId?: number;
}
export interface VerifyPaymentInput {
    action: "verified" | "rejected";
    note?: string;
    userId?: number;
}
//# sourceMappingURL=order.interface.d.ts.map