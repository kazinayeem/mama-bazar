interface PurchaseEventData {
    eventId: string;
    currency?: string;
    value: number;
    contentIds: string[];
    contentType?: string;
    userIp: string;
    userAgent: string;
    fbp?: string;
    fbc?: string;
    email?: string;
    phone?: string;
}
export declare const sendFacebookPurchaseEvent: (data: PurchaseEventData) => Promise<{
    sent: boolean;
    reason: string;
    result?: undefined;
    error?: undefined;
} | {
    sent: boolean;
    result: unknown;
    reason?: undefined;
    error?: undefined;
} | {
    sent: boolean;
    error: any;
    reason?: undefined;
    result?: undefined;
}>;
export {};
//# sourceMappingURL=analytics.service.d.ts.map