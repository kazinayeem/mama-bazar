export declare const getPublishedBySlug: (slug: string) => Promise<{
    id: number;
    slug: string;
    title: string;
    content: string;
    status: "published";
    lastUpdated: number;
    createdAt: Date;
} | null>;
export declare const getAll: () => Promise<{
    id: number;
    slug: string;
    title: string;
    status: "draft" | "published";
    lastUpdated: number;
    createdAt: Date;
}[]>;
export declare const create: (data: {
    slug: string;
    title: string;
    content: string;
    status: "published" | "draft";
    updatedBy: number;
}) => Promise<{
    id: number;
}>;
export declare const update: (id: number, payload: {
    title?: string;
    content?: string;
    status?: "published" | "draft";
    updatedBy: number;
}) => Promise<{
    id: number;
    lastUpdated: number;
}>;
export declare const remove: (id: number) => Promise<{
    success: boolean;
}>;
export declare const createContactMessage: (payload: {
    name: string;
    phone: string;
    email?: string;
    message: string;
}) => Promise<{
    success: boolean;
    id: number;
}>;
export declare const getContactMessages: () => Promise<{
    id: number;
    name: string;
    status: "archived" | "new" | "read";
    createdAt: Date;
    phone: string;
    email: string | null;
    message: string;
}[]>;
export declare const setContactMessageStatus: (id: number, status: "new" | "read" | "archived") => Promise<{
    success: boolean;
}>;
//# sourceMappingURL=pages.service.d.ts.map