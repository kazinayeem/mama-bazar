import { CreateUserInput, LoginInput, PasswordResetRequestInput, PasswordResetInput, ChangePasswordInput, UpdateProfileInput } from "./user.interface";
export declare const register: (data: CreateUserInput) => Promise<{
    id: number;
    name: string;
    phone: string;
}>;
export declare const login: (data: LoginInput) => Promise<{
    token: string;
    user: {
        id: number;
        name: string;
        phone: string;
        role: "admin" | "manager" | "user";
    };
}>;
/**
 * Development-only quick login. It authenticates the seeded development
 * account through the exact same `login` path (DB lookup -> bcrypt verify ->
 * account status -> real JWT), and is never available in production.
 */
export declare const devLogin: (role: string) => Promise<{
    token: string;
    user: {
        id: number;
        name: string;
        phone: string;
        role: "admin" | "manager" | "user";
    };
}>;
export declare const getAll: () => Promise<{
    id: number;
    name: string;
    status: "active" | "inactive";
    createdAt: Date;
    phone: string;
    shippingArea: string | null;
    shippingAddress: string | null;
    role: "admin" | "manager" | "user";
    resetTokenHash: string | null;
    resetTokenExpiresAt: Date | null;
}[]>;
export declare const getById: (id: number) => Promise<{
    id: number;
    name: string;
    status: "active" | "inactive";
    createdAt: Date;
    phone: string;
    shippingArea: string | null;
    shippingAddress: string | null;
    role: "admin" | "manager" | "user";
    resetTokenHash: string | null;
    resetTokenExpiresAt: Date | null;
} | null>;
export declare const remove: (id: number) => Promise<{
    success: boolean;
}>;
export declare const requestPasswordReset: (data: PasswordResetRequestInput) => Promise<{
    success: boolean;
    message: string;
    resetToken?: undefined;
    expiresAt?: undefined;
} | {
    resetToken: string;
    expiresAt: Date;
    success?: undefined;
    message?: undefined;
}>;
export declare const resetPassword: (data: PasswordResetInput) => Promise<{
    success: boolean;
    message: string;
}>;
export declare const changePassword: (userId: number, data: ChangePasswordInput) => Promise<{
    success: boolean;
    message: string;
}>;
export declare const updateProfile: (userId: number, data: UpdateProfileInput) => Promise<{
    id: number;
    name: string;
    status: "active" | "inactive";
    createdAt: Date;
    phone: string;
    shippingArea: string | null;
    shippingAddress: string | null;
    role: "admin" | "manager" | "user";
}>;
export declare const getProfile: (userId: number) => Promise<{
    id: number;
    name: string;
    status: "active" | "inactive";
    createdAt: Date;
    phone: string;
    shippingArea: string | null;
    shippingAddress: string | null;
    role: "admin" | "manager" | "user";
}>;
export declare const getOrderHistory: (userId: number) => Promise<{
    statusHistory: ({
        id: number;
        status: "pending" | "payment_pending" | "payment_verification" | "refunded" | "confirmed" | "processing" | "packed" | "shipped" | "out_for_delivery" | "delivered" | "returned" | "cancelled";
        createdAt: Date;
        orderId: number;
        note: string | null;
        createdByUserId: number | null;
    } | {
        id: number;
        orderId: number;
        status: string;
        note: string;
        createdByUserId: null;
        createdAt: Date;
    })[];
    items: {
        id: number;
        productId: number;
        product: {
            id: number;
            slug: string;
            brand: string | null;
            description: string | null;
            seoTitle: string | null;
            seoDescription: string | null;
            seoKeywords: string | null;
            status: "active" | "inactive";
            createdAt: Date;
            title: string;
            shortDescription: string | null;
            price: string;
            salePrice: string | null;
            discount: string | null;
            costPrice: string | null;
            profitMargin: string | null;
            tax: string | null;
            vat: string | null;
            shippingCharge: string | null;
            codFee: string | null;
            flashSalePrice: string | null;
            wholesalePrice: string | null;
            dealerPrice: string | null;
            categoryId: number | null;
            subCategoryId: number | null;
            childCategoryId: number | null;
            collectionId: number | null;
            brandId: number | null;
            vendorId: number | null;
            supplierId: number | null;
            supplier: string | null;
            countryOfOrigin: string | null;
            sku: string | null;
            barcode: string | null;
            tags: string[] | null;
            warranty: string | null;
            weight: string | null;
            dimensions: string | null;
            features: string[] | null;
            returnPolicy: string | null;
            warehouse: string | null;
            videoUrl: string | null;
            canonicalUrl: string | null;
            ogImage: string | null;
            twitterImage: string | null;
            structuredData: Record<string, unknown> | null;
            draft: Record<string, unknown> | null;
            emiAvailable: boolean;
            isFeatured: boolean;
            isTrending: boolean;
            isFlashSale: boolean;
            isNewArrival: boolean;
            isBestSeller: boolean;
            isLimitedEdition: boolean;
            isOfficial: boolean;
            isHotDeal: boolean;
            isArchived: boolean;
            meta: Record<string, unknown> | null;
            stock: number;
            lowStockAlert: number;
            minOrder: number;
            maxOrder: number | null;
            unlimitedStock: boolean;
            backorder: boolean;
            trackInventory: boolean;
            stockStatus: string | null;
            productStatus: string | null;
            images: string[] | null;
            sizeOptions: string[] | null;
            colorOptions: {
                name: string;
                value?: string;
                image?: string;
            }[] | null;
            paymentMethods: ["cod", "online"] | null;
            paymentPhoneNumber: string | null;
        };
        quantity: number;
        price: string;
    }[];
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
    paymentMethod: "cod" | "bkash" | "nagad" | "rocket" | "bank" | "stripe" | "sslcommerz" | "paypal";
    transactionId: string | null;
    senderNumber: string | null;
    paymentScreenshot: string | null;
    paymentDate: Date | null;
    amountSent: string | null;
    paymentInstructions: string | null;
    courierTrackingNumber: string | null;
    paymentStatus: "pending" | "payment_pending" | "payment_verification" | "verified" | "success" | "failed" | "rejected" | "refunded";
}[]>;
export declare const getAddresses: (userId: number) => Promise<{
    id: number;
    createdAt: Date;
    phone: string;
    email: string | null;
    address: string;
    shippingArea: string;
    userId: number;
    alternativePhone: string | null;
    country: string | null;
    division: string | null;
    district: string | null;
    upazila: string | null;
    area: string | null;
    apartment: string | null;
    postalCode: string | null;
    recipientName: string;
    isDefault: boolean;
}[]>;
export declare const createAddress: (userId: number, data: {
    recipientName: string;
    phone: string;
    shippingArea: string;
    address: string;
    isDefault?: boolean;
}) => Promise<{
    id: number;
    createdAt: Date;
    phone: string;
    email: string | null;
    address: string;
    shippingArea: string;
    userId: number;
    alternativePhone: string | null;
    country: string | null;
    division: string | null;
    district: string | null;
    upazila: string | null;
    area: string | null;
    apartment: string | null;
    postalCode: string | null;
    recipientName: string;
    isDefault: boolean;
}[]>;
export declare const updateAddress: (userId: number, addressId: number, data: {
    recipientName?: string;
    phone?: string;
    shippingArea?: string;
    address?: string;
    isDefault?: boolean;
}) => Promise<{
    id: number;
    createdAt: Date;
    phone: string;
    email: string | null;
    address: string;
    shippingArea: string;
    userId: number;
    alternativePhone: string | null;
    country: string | null;
    division: string | null;
    district: string | null;
    upazila: string | null;
    area: string | null;
    apartment: string | null;
    postalCode: string | null;
    recipientName: string;
    isDefault: boolean;
}[]>;
export declare const deleteAddress: (userId: number, addressId: number) => Promise<{
    id: number;
    createdAt: Date;
    phone: string;
    email: string | null;
    address: string;
    shippingArea: string;
    userId: number;
    alternativePhone: string | null;
    country: string | null;
    division: string | null;
    district: string | null;
    upazila: string | null;
    area: string | null;
    apartment: string | null;
    postalCode: string | null;
    recipientName: string;
    isDefault: boolean;
}[]>;
//# sourceMappingURL=user.service.d.ts.map