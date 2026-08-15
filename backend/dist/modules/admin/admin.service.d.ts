export interface DashboardQuery {
    range?: string;
}
export declare const getDashboard: (query: DashboardQuery) => Promise<{
    kpis: {
        totalRevenue: number;
        totalOrders: number;
        avgOrderValue: number;
        totalCustomers: number;
        totalProducts: number;
        todayOrders: number;
        periodRevenue: number;
        periodOrders: number;
        deliveredThisPeriod: number;
        cancelledThisPeriod: number;
        lowStock: number;
        outOfStock: number;
        conversionRate: number;
        periodVisitors: number;
    };
    revenueChart: {
        date: string;
        revenue: number;
        orders: number;
    }[];
    statusBreakdown: Record<string, number>;
    paymentBreakdown: {
        method: string;
        count: number;
        revenue: number;
    }[];
    recentOrders: {
        id: number;
        status: "pending" | "payment_pending" | "payment_verification" | "refunded" | "confirmed" | "processing" | "packed" | "shipped" | "out_for_delivery" | "delivered" | "returned" | "cancelled";
        createdAt: Date;
        discount: string | null;
        tax: string | null;
        phone: string;
        email: string | null;
        address: string;
        shippingCost: string;
        orderId: string;
        userId: number | null;
        customerName: string;
        alternativePhone: string | null;
        country: string | null;
        division: string | null;
        district: string | null;
        upazila: string | null;
        area: string | null;
        apartment: string | null;
        postalCode: string | null;
        shippingMethodId: number | null;
        shippingMethodName: string | null;
        subtotal: string | null;
        couponCode: string | null;
        orderNote: string | null;
        checkoutNotes: string | null;
        adminNotes: string | null;
        totalPrice: string;
        paymentMethod: string;
        transactionId: string | null;
        senderNumber: string | null;
        paymentScreenshot: string | null;
        paymentDate: Date | null;
        amountSent: string | null;
        paymentInstructions: string | null;
        courierTrackingNumber: string | null;
        paymentStatus: "pending" | "payment_pending" | "payment_verification" | "verified" | "success" | "failed" | "rejected" | "refunded";
    }[];
    topProducts: {
        id: number;
        title: string | null;
        slug: string | null;
        image: string | null;
        quantity: number;
        revenue: number;
    }[];
    topCategories: {
        id: number | null;
        name: string | null;
        count: number;
    }[];
    lowStockProducts: {
        id: number;
        title: string;
        slug: string;
        stock: number;
        image: string | null;
        price: string;
    }[];
}>;
//# sourceMappingURL=admin.service.d.ts.map