import { CreateCouponInput, UpdateCouponInput } from "./coupon.interface";
export declare const getAll: () => Promise<{
    id: number;
    status: "active" | "inactive";
    createdAt: Date;
    code: string;
    discountType: "fixed" | "percentage";
    discountValue: string;
    minOrderAmount: string | null;
    expiryDate: Date | null;
}[]>;
export declare const getById: (id: number) => Promise<{
    id: number;
    status: "active" | "inactive";
    createdAt: Date;
    code: string;
    discountType: "fixed" | "percentage";
    discountValue: string;
    minOrderAmount: string | null;
    expiryDate: Date | null;
}>;
export declare const validate: (code: string, subtotal: number) => Promise<{
    valid: boolean;
    discount: number;
    discountType: "fixed" | "percentage";
    discountValue: string;
}>;
export declare const create: (data: CreateCouponInput) => Promise<{
    id: number;
    status: "active" | "inactive";
    createdAt: Date;
    code: string;
    discountType: "fixed" | "percentage";
    discountValue: string;
    minOrderAmount: string | null;
    expiryDate: Date | null;
}>;
export declare const update: (id: number, data: UpdateCouponInput) => Promise<{
    id: number;
    status: "active" | "inactive";
    createdAt: Date;
    code: string;
    discountType: "fixed" | "percentage";
    discountValue: string;
    minOrderAmount: string | null;
    expiryDate: Date | null;
}>;
export declare const remove: (id: number) => Promise<{
    success: boolean;
}>;
//# sourceMappingURL=coupon.service.d.ts.map