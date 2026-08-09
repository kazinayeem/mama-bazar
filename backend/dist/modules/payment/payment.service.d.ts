import { CreatePaymentMethodInput, UpdatePaymentMethodInput } from "./payment.schema";
export declare const getActive: () => Promise<{
    id: any;
    code: any;
    name: any;
    type: any;
    config: any;
}[]>;
export declare const getByCode: (code: string) => Promise<{
    id: number;
    name: string;
    sortOrder: number;
    createdAt: Date;
    type: "cod" | "online" | "bank" | "mobile_banking";
    updatedAt: Date;
    code: string;
    enabled: boolean;
    maintenanceMode: boolean;
    config: Record<string, unknown> | null;
}>;
export declare const getAll: () => Promise<{
    config: any;
    id: number;
    name: string;
    sortOrder: number;
    createdAt: Date;
    type: "cod" | "online" | "bank" | "mobile_banking";
    updatedAt: Date;
    code: string;
    enabled: boolean;
    maintenanceMode: boolean;
}[]>;
export declare const getById: (id: number) => Promise<{
    id: number;
    name: string;
    sortOrder: number;
    createdAt: Date;
    type: "cod" | "online" | "bank" | "mobile_banking";
    updatedAt: Date;
    code: string;
    enabled: boolean;
    maintenanceMode: boolean;
    config: Record<string, unknown> | null;
}>;
export declare const create: (data: CreatePaymentMethodInput) => Promise<{
    id: number;
    name: string;
    sortOrder: number;
    createdAt: Date;
    type: "cod" | "online" | "bank" | "mobile_banking";
    updatedAt: Date;
    code: string;
    enabled: boolean;
    maintenanceMode: boolean;
    config: Record<string, unknown> | null;
}>;
export declare const update: (id: number, data: UpdatePaymentMethodInput) => Promise<{
    id: number;
    name: string;
    sortOrder: number;
    createdAt: Date;
    type: "cod" | "online" | "bank" | "mobile_banking";
    updatedAt: Date;
    code: string;
    enabled: boolean;
    maintenanceMode: boolean;
    config: Record<string, unknown> | null;
}>;
export declare const setStatuses: (ids: number[], enabled: boolean) => Promise<{
    success: boolean;
}>;
export declare const remove: (id: number) => Promise<{
    success: boolean;
}>;
//# sourceMappingURL=payment.service.d.ts.map