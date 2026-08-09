import { z } from "zod";
export declare const registerSchema: z.ZodObject<{
    body: z.ZodObject<{
        name: z.ZodString;
        phone: z.ZodString;
        password: z.ZodString;
        role: z.ZodOptional<z.ZodEnum<["admin", "manager", "user"]>>;
    }, "strip", z.ZodTypeAny, {
        name: string;
        phone: string;
        password: string;
        role?: "admin" | "manager" | "user" | undefined;
    }, {
        name: string;
        phone: string;
        password: string;
        role?: "admin" | "manager" | "user" | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name: string;
        phone: string;
        password: string;
        role?: "admin" | "manager" | "user" | undefined;
    };
}, {
    body: {
        name: string;
        phone: string;
        password: string;
        role?: "admin" | "manager" | "user" | undefined;
    };
}>;
export declare const loginSchema: z.ZodObject<{
    body: z.ZodObject<{
        phone: z.ZodString;
        password: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        phone: string;
        password: string;
    }, {
        phone: string;
        password: string;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        phone: string;
        password: string;
    };
}, {
    body: {
        phone: string;
        password: string;
    };
}>;
export declare const passwordResetRequestSchema: z.ZodObject<{
    body: z.ZodObject<{
        phone: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        phone: string;
    }, {
        phone: string;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        phone: string;
    };
}, {
    body: {
        phone: string;
    };
}>;
export declare const passwordResetSchema: z.ZodObject<{
    body: z.ZodObject<{
        token: z.ZodString;
        newPassword: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        token: string;
        newPassword: string;
    }, {
        token: string;
        newPassword: string;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        token: string;
        newPassword: string;
    };
}, {
    body: {
        token: string;
        newPassword: string;
    };
}>;
export declare const changePasswordSchema: z.ZodObject<{
    body: z.ZodObject<{
        oldPassword: z.ZodString;
        newPassword: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        newPassword: string;
        oldPassword: string;
    }, {
        newPassword: string;
        oldPassword: string;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        newPassword: string;
        oldPassword: string;
    };
}, {
    body: {
        newPassword: string;
        oldPassword: string;
    };
}>;
export declare const updateProfileSchema: z.ZodObject<{
    body: z.ZodObject<{
        name: z.ZodOptional<z.ZodString>;
        phone: z.ZodOptional<z.ZodString>;
        shippingArea: z.ZodOptional<z.ZodString>;
        shippingAddress: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        name?: string | undefined;
        phone?: string | undefined;
        shippingArea?: string | undefined;
        shippingAddress?: string | undefined;
    }, {
        name?: string | undefined;
        phone?: string | undefined;
        shippingArea?: string | undefined;
        shippingAddress?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name?: string | undefined;
        phone?: string | undefined;
        shippingArea?: string | undefined;
        shippingAddress?: string | undefined;
    };
}, {
    body: {
        name?: string | undefined;
        phone?: string | undefined;
        shippingArea?: string | undefined;
        shippingAddress?: string | undefined;
    };
}>;
export declare const createAddressSchema: z.ZodObject<{
    body: z.ZodObject<{
        recipientName: z.ZodString;
        phone: z.ZodString;
        shippingArea: z.ZodString;
        address: z.ZodString;
        isDefault: z.ZodOptional<z.ZodBoolean>;
    }, "strip", z.ZodTypeAny, {
        phone: string;
        address: string;
        shippingArea: string;
        recipientName: string;
        isDefault?: boolean | undefined;
    }, {
        phone: string;
        address: string;
        shippingArea: string;
        recipientName: string;
        isDefault?: boolean | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        phone: string;
        address: string;
        shippingArea: string;
        recipientName: string;
        isDefault?: boolean | undefined;
    };
}, {
    body: {
        phone: string;
        address: string;
        shippingArea: string;
        recipientName: string;
        isDefault?: boolean | undefined;
    };
}>;
export declare const updateAddressSchema: z.ZodObject<{
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
    body: z.ZodObject<{
        recipientName: z.ZodOptional<z.ZodString>;
        phone: z.ZodOptional<z.ZodString>;
        shippingArea: z.ZodOptional<z.ZodString>;
        address: z.ZodOptional<z.ZodString>;
        isDefault: z.ZodOptional<z.ZodBoolean>;
    }, "strip", z.ZodTypeAny, {
        phone?: string | undefined;
        address?: string | undefined;
        shippingArea?: string | undefined;
        recipientName?: string | undefined;
        isDefault?: boolean | undefined;
    }, {
        phone?: string | undefined;
        address?: string | undefined;
        shippingArea?: string | undefined;
        recipientName?: string | undefined;
        isDefault?: boolean | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    params: {
        id: string;
    };
    body: {
        phone?: string | undefined;
        address?: string | undefined;
        shippingArea?: string | undefined;
        recipientName?: string | undefined;
        isDefault?: boolean | undefined;
    };
}, {
    params: {
        id: string;
    };
    body: {
        phone?: string | undefined;
        address?: string | undefined;
        shippingArea?: string | undefined;
        recipientName?: string | undefined;
        isDefault?: boolean | undefined;
    };
}>;
export declare const addressIdSchema: z.ZodObject<{
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
//# sourceMappingURL=user.schema.d.ts.map