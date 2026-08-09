export interface CreateReviewInput {
    productId: number;
    userId?: number | null;
    customerName?: string | null;
    rating: number;
    title?: string | null;
    comment: string;
}
export interface ReviewQuery {
    page?: number;
    limit?: number;
    productId?: number;
    status?: "pending" | "approved" | "rejected";
    search?: string;
}
export declare const getAll: (query: ReviewQuery) => Promise<{
    data: {
        id: number;
        productId: number;
        userId: number | null;
        customerName: string | null;
        rating: number;
        title: string | null;
        comment: string;
        status: "pending" | "rejected" | "approved";
        createdAt: Date;
        productTitle: string | null;
        productSlug: string | null;
        productImage: string | null;
    }[];
    pagination: {
        page: number;
        limit: number;
        total: number;
        totalPages: number;
    };
}>;
export declare const getById: (id: number) => Promise<{
    id: number;
    productId: number;
    userId: number | null;
    customerName: string | null;
    rating: number;
    title: string | null;
    comment: string;
    status: "pending" | "rejected" | "approved";
    createdAt: Date;
    productTitle: string | null;
    productSlug: string | null;
    customerPhone: string | null;
    customerRole: "admin" | "manager" | "user" | null;
}>;
export declare const create: (data: CreateReviewInput) => Promise<{
    id: number;
    productId: number;
    userId: number | null;
    customerName: string | null;
    rating: number;
    title: string | null;
    comment: string;
    status: "pending" | "rejected" | "approved";
    createdAt: Date;
    productTitle: string | null;
    productSlug: string | null;
    customerPhone: string | null;
    customerRole: "admin" | "manager" | "user" | null;
}>;
export declare const updateStatus: (id: number, status: "pending" | "approved" | "rejected") => Promise<{
    id: number;
    productId: number;
    userId: number | null;
    customerName: string | null;
    rating: number;
    title: string | null;
    comment: string;
    status: "pending" | "rejected" | "approved";
    createdAt: Date;
    productTitle: string | null;
    productSlug: string | null;
    customerPhone: string | null;
    customerRole: "admin" | "manager" | "user" | null;
}>;
export declare const remove: (id: number) => Promise<{
    success: boolean;
}>;
//# sourceMappingURL=review.service.d.ts.map