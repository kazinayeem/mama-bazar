import { CreateCheckoutNoticeInput, UpdateCheckoutNoticeInput } from "./checkout-notice.schema";
export declare const getActive: () => Promise<{
    id: number;
    icon: string | null;
    text: string;
    status: "active" | "inactive";
    createdAt: Date;
    priority: number;
    backgroundColor: string | null;
    textColor: string | null;
}[]>;
export declare const getAll: () => Promise<{
    id: number;
    icon: string | null;
    text: string;
    status: "active" | "inactive";
    createdAt: Date;
    priority: number;
    backgroundColor: string | null;
    textColor: string | null;
}[]>;
export declare const getById: (id: number) => Promise<{
    id: number;
    icon: string | null;
    text: string;
    status: "active" | "inactive";
    createdAt: Date;
    priority: number;
    backgroundColor: string | null;
    textColor: string | null;
}>;
export declare const create: (data: CreateCheckoutNoticeInput) => Promise<{
    id: number;
    icon: string | null;
    text: string;
    status: "active" | "inactive";
    createdAt: Date;
    priority: number;
    backgroundColor: string | null;
    textColor: string | null;
}>;
export declare const update: (id: number, data: UpdateCheckoutNoticeInput) => Promise<{
    id: number;
    icon: string | null;
    text: string;
    status: "active" | "inactive";
    createdAt: Date;
    priority: number;
    backgroundColor: string | null;
    textColor: string | null;
}>;
export declare const remove: (id: number) => Promise<{
    success: boolean;
}>;
//# sourceMappingURL=checkout-notice.service.d.ts.map