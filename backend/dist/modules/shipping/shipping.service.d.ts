import { CreateShippingMethodInput, UpdateShippingMethodInput } from "./shipping.schema";
export declare const getActive: () => Promise<{
    charge: number;
    freeShippingMinAmount: number | null;
    id: number;
    name: string;
    description: string | null;
    status: "active" | "inactive";
    createdAt: Date;
    priority: number;
    estimatedDelivery: string | null;
    codAvailable: boolean;
}[]>;
export declare const estimate: (subtotal: number) => Promise<{
    estimatedCost: number;
    charge: number;
    freeShippingMinAmount: number | null;
    id: number;
    name: string;
    description: string | null;
    status: "active" | "inactive";
    createdAt: Date;
    priority: number;
    estimatedDelivery: string | null;
    codAvailable: boolean;
}[]>;
export declare const getAll: () => Promise<{
    id: number;
    name: string;
    description: string | null;
    status: "active" | "inactive";
    createdAt: Date;
    priority: number;
    charge: string;
    estimatedDelivery: string | null;
    freeShippingMinAmount: string | null;
    codAvailable: boolean;
}[]>;
export declare const getById: (id: number) => Promise<{
    id: number;
    name: string;
    description: string | null;
    status: "active" | "inactive";
    createdAt: Date;
    priority: number;
    charge: string;
    estimatedDelivery: string | null;
    freeShippingMinAmount: string | null;
    codAvailable: boolean;
}>;
export declare const create: (data: CreateShippingMethodInput) => Promise<{
    id: number;
    name: string;
    description: string | null;
    status: "active" | "inactive";
    createdAt: Date;
    priority: number;
    charge: string;
    estimatedDelivery: string | null;
    freeShippingMinAmount: string | null;
    codAvailable: boolean;
}>;
export declare const update: (id: number, data: UpdateShippingMethodInput) => Promise<{
    id: number;
    name: string;
    description: string | null;
    status: "active" | "inactive";
    createdAt: Date;
    priority: number;
    charge: string;
    estimatedDelivery: string | null;
    freeShippingMinAmount: string | null;
    codAvailable: boolean;
}>;
export declare const remove: (id: number) => Promise<{
    success: boolean;
}>;
//# sourceMappingURL=shipping.service.d.ts.map