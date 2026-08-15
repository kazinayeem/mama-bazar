import { z } from "zod";
export declare const createPaymentMethodSchema: z.ZodObject<{
    body: z.ZodObject<{
        code: z.ZodString;
        name: z.ZodString;
        type: z.ZodEnum<["cod", "mobile_banking", "bank", "online"]>;
        enabled: z.ZodOptional<z.ZodBoolean>;
        sortOrder: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        maintenanceMode: z.ZodOptional<z.ZodBoolean>;
        config: z.ZodOptional<z.ZodObject<{
            merchantNumber: z.ZodOptional<z.ZodString>;
            merchantName: z.ZodOptional<z.ZodString>;
            bankName: z.ZodOptional<z.ZodString>;
            accountName: z.ZodOptional<z.ZodString>;
            accountNumber: z.ZodOptional<z.ZodString>;
            routingNumber: z.ZodOptional<z.ZodString>;
            branch: z.ZodOptional<z.ZodString>;
            instructions: z.ZodOptional<z.ZodString>;
            qrCode: z.ZodOptional<z.ZodString>;
            minAmount: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
            maxAmount: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
            extraFee: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
            extraFeePercent: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        }, "strip", z.ZodTypeAny, {
            merchantNumber?: string | undefined;
            merchantName?: string | undefined;
            bankName?: string | undefined;
            accountName?: string | undefined;
            accountNumber?: string | undefined;
            routingNumber?: string | undefined;
            branch?: string | undefined;
            instructions?: string | undefined;
            qrCode?: string | undefined;
            minAmount?: string | number | undefined;
            maxAmount?: string | number | undefined;
            extraFee?: string | number | undefined;
            extraFeePercent?: string | number | undefined;
        }, {
            merchantNumber?: string | undefined;
            merchantName?: string | undefined;
            bankName?: string | undefined;
            accountName?: string | undefined;
            accountNumber?: string | undefined;
            routingNumber?: string | undefined;
            branch?: string | undefined;
            instructions?: string | undefined;
            qrCode?: string | undefined;
            minAmount?: string | number | undefined;
            maxAmount?: string | number | undefined;
            extraFee?: string | number | undefined;
            extraFeePercent?: string | number | undefined;
        }>>;
    }, "strip", z.ZodTypeAny, {
        name: string;
        type: "cod" | "online" | "mobile_banking" | "bank";
        code: string;
        sortOrder?: string | number | undefined;
        enabled?: boolean | undefined;
        maintenanceMode?: boolean | undefined;
        config?: {
            merchantNumber?: string | undefined;
            merchantName?: string | undefined;
            bankName?: string | undefined;
            accountName?: string | undefined;
            accountNumber?: string | undefined;
            routingNumber?: string | undefined;
            branch?: string | undefined;
            instructions?: string | undefined;
            qrCode?: string | undefined;
            minAmount?: string | number | undefined;
            maxAmount?: string | number | undefined;
            extraFee?: string | number | undefined;
            extraFeePercent?: string | number | undefined;
        } | undefined;
    }, {
        name: string;
        type: "cod" | "online" | "mobile_banking" | "bank";
        code: string;
        sortOrder?: string | number | undefined;
        enabled?: boolean | undefined;
        maintenanceMode?: boolean | undefined;
        config?: {
            merchantNumber?: string | undefined;
            merchantName?: string | undefined;
            bankName?: string | undefined;
            accountName?: string | undefined;
            accountNumber?: string | undefined;
            routingNumber?: string | undefined;
            branch?: string | undefined;
            instructions?: string | undefined;
            qrCode?: string | undefined;
            minAmount?: string | number | undefined;
            maxAmount?: string | number | undefined;
            extraFee?: string | number | undefined;
            extraFeePercent?: string | number | undefined;
        } | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name: string;
        type: "cod" | "online" | "mobile_banking" | "bank";
        code: string;
        sortOrder?: string | number | undefined;
        enabled?: boolean | undefined;
        maintenanceMode?: boolean | undefined;
        config?: {
            merchantNumber?: string | undefined;
            merchantName?: string | undefined;
            bankName?: string | undefined;
            accountName?: string | undefined;
            accountNumber?: string | undefined;
            routingNumber?: string | undefined;
            branch?: string | undefined;
            instructions?: string | undefined;
            qrCode?: string | undefined;
            minAmount?: string | number | undefined;
            maxAmount?: string | number | undefined;
            extraFee?: string | number | undefined;
            extraFeePercent?: string | number | undefined;
        } | undefined;
    };
}, {
    body: {
        name: string;
        type: "cod" | "online" | "mobile_banking" | "bank";
        code: string;
        sortOrder?: string | number | undefined;
        enabled?: boolean | undefined;
        maintenanceMode?: boolean | undefined;
        config?: {
            merchantNumber?: string | undefined;
            merchantName?: string | undefined;
            bankName?: string | undefined;
            accountName?: string | undefined;
            accountNumber?: string | undefined;
            routingNumber?: string | undefined;
            branch?: string | undefined;
            instructions?: string | undefined;
            qrCode?: string | undefined;
            minAmount?: string | number | undefined;
            maxAmount?: string | number | undefined;
            extraFee?: string | number | undefined;
            extraFeePercent?: string | number | undefined;
        } | undefined;
    };
}>;
export declare const updatePaymentMethodSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        code: z.ZodOptional<z.ZodString>;
        name: z.ZodOptional<z.ZodString>;
        type: z.ZodOptional<z.ZodEnum<["cod", "mobile_banking", "bank", "online"]>>;
        enabled: z.ZodOptional<z.ZodBoolean>;
        sortOrder: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        maintenanceMode: z.ZodOptional<z.ZodBoolean>;
        config: z.ZodOptional<z.ZodObject<{
            merchantNumber: z.ZodOptional<z.ZodString>;
            merchantName: z.ZodOptional<z.ZodString>;
            bankName: z.ZodOptional<z.ZodString>;
            accountName: z.ZodOptional<z.ZodString>;
            accountNumber: z.ZodOptional<z.ZodString>;
            routingNumber: z.ZodOptional<z.ZodString>;
            branch: z.ZodOptional<z.ZodString>;
            instructions: z.ZodOptional<z.ZodString>;
            qrCode: z.ZodOptional<z.ZodString>;
            minAmount: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
            maxAmount: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
            extraFee: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
            extraFeePercent: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        }, "strip", z.ZodTypeAny, {
            merchantNumber?: string | undefined;
            merchantName?: string | undefined;
            bankName?: string | undefined;
            accountName?: string | undefined;
            accountNumber?: string | undefined;
            routingNumber?: string | undefined;
            branch?: string | undefined;
            instructions?: string | undefined;
            qrCode?: string | undefined;
            minAmount?: string | number | undefined;
            maxAmount?: string | number | undefined;
            extraFee?: string | number | undefined;
            extraFeePercent?: string | number | undefined;
        }, {
            merchantNumber?: string | undefined;
            merchantName?: string | undefined;
            bankName?: string | undefined;
            accountName?: string | undefined;
            accountNumber?: string | undefined;
            routingNumber?: string | undefined;
            branch?: string | undefined;
            instructions?: string | undefined;
            qrCode?: string | undefined;
            minAmount?: string | number | undefined;
            maxAmount?: string | number | undefined;
            extraFee?: string | number | undefined;
            extraFeePercent?: string | number | undefined;
        }>>;
    }, "strip", z.ZodTypeAny, {
        name?: string | undefined;
        sortOrder?: string | number | undefined;
        type?: "cod" | "online" | "mobile_banking" | "bank" | undefined;
        code?: string | undefined;
        enabled?: boolean | undefined;
        maintenanceMode?: boolean | undefined;
        config?: {
            merchantNumber?: string | undefined;
            merchantName?: string | undefined;
            bankName?: string | undefined;
            accountName?: string | undefined;
            accountNumber?: string | undefined;
            routingNumber?: string | undefined;
            branch?: string | undefined;
            instructions?: string | undefined;
            qrCode?: string | undefined;
            minAmount?: string | number | undefined;
            maxAmount?: string | number | undefined;
            extraFee?: string | number | undefined;
            extraFeePercent?: string | number | undefined;
        } | undefined;
    }, {
        name?: string | undefined;
        sortOrder?: string | number | undefined;
        type?: "cod" | "online" | "mobile_banking" | "bank" | undefined;
        code?: string | undefined;
        enabled?: boolean | undefined;
        maintenanceMode?: boolean | undefined;
        config?: {
            merchantNumber?: string | undefined;
            merchantName?: string | undefined;
            bankName?: string | undefined;
            accountName?: string | undefined;
            accountNumber?: string | undefined;
            routingNumber?: string | undefined;
            branch?: string | undefined;
            instructions?: string | undefined;
            qrCode?: string | undefined;
            minAmount?: string | number | undefined;
            maxAmount?: string | number | undefined;
            extraFee?: string | number | undefined;
            extraFeePercent?: string | number | undefined;
        } | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name?: string | undefined;
        sortOrder?: string | number | undefined;
        type?: "cod" | "online" | "mobile_banking" | "bank" | undefined;
        code?: string | undefined;
        enabled?: boolean | undefined;
        maintenanceMode?: boolean | undefined;
        config?: {
            merchantNumber?: string | undefined;
            merchantName?: string | undefined;
            bankName?: string | undefined;
            accountName?: string | undefined;
            accountNumber?: string | undefined;
            routingNumber?: string | undefined;
            branch?: string | undefined;
            instructions?: string | undefined;
            qrCode?: string | undefined;
            minAmount?: string | number | undefined;
            maxAmount?: string | number | undefined;
            extraFee?: string | number | undefined;
            extraFeePercent?: string | number | undefined;
        } | undefined;
    };
    params: {
        id: string;
    };
}, {
    body: {
        name?: string | undefined;
        sortOrder?: string | number | undefined;
        type?: "cod" | "online" | "mobile_banking" | "bank" | undefined;
        code?: string | undefined;
        enabled?: boolean | undefined;
        maintenanceMode?: boolean | undefined;
        config?: {
            merchantNumber?: string | undefined;
            merchantName?: string | undefined;
            bankName?: string | undefined;
            accountName?: string | undefined;
            accountNumber?: string | undefined;
            routingNumber?: string | undefined;
            branch?: string | undefined;
            instructions?: string | undefined;
            qrCode?: string | undefined;
            minAmount?: string | number | undefined;
            maxAmount?: string | number | undefined;
            extraFee?: string | number | undefined;
            extraFeePercent?: string | number | undefined;
        } | undefined;
    };
    params: {
        id: string;
    };
}>;
export declare const paymentMethodIdSchema: z.ZodObject<{
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
export declare const paymentMethodsStatusSchema: z.ZodObject<{
    body: z.ZodObject<{
        ids: z.ZodArray<z.ZodNumber, "many">;
        enabled: z.ZodBoolean;
    }, "strip", z.ZodTypeAny, {
        enabled: boolean;
        ids: number[];
    }, {
        enabled: boolean;
        ids: number[];
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        enabled: boolean;
        ids: number[];
    };
}, {
    body: {
        enabled: boolean;
        ids: number[];
    };
}>;
export type CreatePaymentMethodInput = {
    code: string;
    name: string;
    type: "cod" | "mobile_banking" | "bank" | "online";
    enabled?: boolean;
    sortOrder?: number;
    maintenanceMode?: boolean;
    config?: Record<string, unknown>;
};
export type UpdatePaymentMethodInput = Partial<CreatePaymentMethodInput>;
//# sourceMappingURL=payment.schema.d.ts.map