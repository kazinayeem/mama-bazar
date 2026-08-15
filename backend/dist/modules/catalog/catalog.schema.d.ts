import { z } from "zod";
export declare const colorCreateSchema: z.ZodObject<{
    body: z.ZodObject<{
        displayName: z.ZodOptional<z.ZodString>;
        hex: z.ZodString;
        name: z.ZodString;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive", "archived"]>>;
        sortOrder: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
    }, "strip", z.ZodTypeAny, {
        name: string;
        hex: string;
        sortOrder?: string | number | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        displayName?: string | undefined;
    }, {
        name: string;
        hex: string;
        sortOrder?: string | number | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        displayName?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name: string;
        hex: string;
        sortOrder?: string | number | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        displayName?: string | undefined;
    };
}, {
    body: {
        name: string;
        hex: string;
        sortOrder?: string | number | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        displayName?: string | undefined;
    };
}>;
export declare const colorUpdateSchema: z.ZodObject<{
    body: z.ZodObject<{
        name: z.ZodOptional<z.ZodString>;
        displayName: z.ZodOptional<z.ZodString>;
        hex: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive", "archived"]>>;
        sortOrder: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
    }, "strip", z.ZodTypeAny, {
        name?: string | undefined;
        sortOrder?: string | number | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        displayName?: string | undefined;
        hex?: string | undefined;
    }, {
        name?: string | undefined;
        sortOrder?: string | number | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        displayName?: string | undefined;
        hex?: string | undefined;
    }>;
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name?: string | undefined;
        sortOrder?: string | number | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        displayName?: string | undefined;
        hex?: string | undefined;
    };
    params: {
        id: string;
    };
}, {
    body: {
        name?: string | undefined;
        sortOrder?: string | number | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        displayName?: string | undefined;
        hex?: string | undefined;
    };
    params: {
        id: string;
    };
}>;
export declare const colorListSchema: z.ZodObject<{
    query: z.ZodObject<{
        page: z.ZodOptional<z.ZodString>;
        limit: z.ZodOptional<z.ZodString>;
        search: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodString>;
        sort: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }, {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}, {
    query: {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}>;
export declare const colorMoveSchema: z.ZodObject<{
    body: z.ZodObject<{
        targetId: z.ZodUnion<[z.ZodString, z.ZodNumber, z.ZodNull]>;
    }, "strip", z.ZodTypeAny, {
        targetId: string | number | null;
    }, {
        targetId: string | number | null;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        targetId: string | number | null;
    };
}, {
    body: {
        targetId: string | number | null;
    };
}>;
export declare const sizeCreateSchema: z.ZodObject<{
    body: z.ZodObject<{
        type: z.ZodOptional<z.ZodEnum<["clothing", "shoes", "general", "custom"]>>;
        name: z.ZodString;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive", "archived"]>>;
        sortOrder: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
    }, "strip", z.ZodTypeAny, {
        name: string;
        sortOrder?: string | number | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        type?: "custom" | "clothing" | "shoes" | "general" | undefined;
    }, {
        name: string;
        sortOrder?: string | number | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        type?: "custom" | "clothing" | "shoes" | "general" | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name: string;
        sortOrder?: string | number | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        type?: "custom" | "clothing" | "shoes" | "general" | undefined;
    };
}, {
    body: {
        name: string;
        sortOrder?: string | number | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        type?: "custom" | "clothing" | "shoes" | "general" | undefined;
    };
}>;
export declare const sizeUpdateSchema: z.ZodObject<{
    body: z.ZodObject<{
        name: z.ZodOptional<z.ZodString>;
        type: z.ZodOptional<z.ZodEnum<["clothing", "shoes", "general", "custom"]>>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive", "archived"]>>;
        sortOrder: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
    }, "strip", z.ZodTypeAny, {
        name?: string | undefined;
        sortOrder?: string | number | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        type?: "custom" | "clothing" | "shoes" | "general" | undefined;
    }, {
        name?: string | undefined;
        sortOrder?: string | number | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        type?: "custom" | "clothing" | "shoes" | "general" | undefined;
    }>;
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name?: string | undefined;
        sortOrder?: string | number | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        type?: "custom" | "clothing" | "shoes" | "general" | undefined;
    };
    params: {
        id: string;
    };
}, {
    body: {
        name?: string | undefined;
        sortOrder?: string | number | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        type?: "custom" | "clothing" | "shoes" | "general" | undefined;
    };
    params: {
        id: string;
    };
}>;
export declare const sizeListSchema: z.ZodObject<{
    query: z.ZodObject<{
        page: z.ZodOptional<z.ZodString>;
        limit: z.ZodOptional<z.ZodString>;
        search: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodString>;
        sort: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }, {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}, {
    query: {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}>;
export declare const sizeMoveSchema: z.ZodObject<{
    body: z.ZodObject<{
        targetId: z.ZodUnion<[z.ZodString, z.ZodNumber, z.ZodNull]>;
    }, "strip", z.ZodTypeAny, {
        targetId: string | number | null;
    }, {
        targetId: string | number | null;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        targetId: string | number | null;
    };
}, {
    body: {
        targetId: string | number | null;
    };
}>;
export declare const collectionCreateSchema: z.ZodObject<{
    body: z.ZodObject<{
        name: z.ZodString;
        slug: z.ZodOptional<z.ZodString>;
        description: z.ZodOptional<z.ZodString>;
        image: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        banner: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        featured: z.ZodOptional<z.ZodUnion<[z.ZodBoolean, z.ZodString]>>;
        homepageVisibility: z.ZodOptional<z.ZodUnion<[z.ZodBoolean, z.ZodString]>>;
        sortOrder: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        startDate: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNull]>>;
        endDate: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNull]>>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive", "archived"]>>;
    }, "strip", z.ZodTypeAny, {
        name: string;
        slug?: string | undefined;
        image?: string | null | undefined;
        banner?: string | null | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        startDate?: string | null | undefined;
        endDate?: string | null | undefined;
    }, {
        name: string;
        slug?: string | undefined;
        image?: string | null | undefined;
        banner?: string | null | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        startDate?: string | null | undefined;
        endDate?: string | null | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name: string;
        slug?: string | undefined;
        image?: string | null | undefined;
        banner?: string | null | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        startDate?: string | null | undefined;
        endDate?: string | null | undefined;
    };
}, {
    body: {
        name: string;
        slug?: string | undefined;
        image?: string | null | undefined;
        banner?: string | null | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        startDate?: string | null | undefined;
        endDate?: string | null | undefined;
    };
}>;
export declare const collectionUpdateSchema: z.ZodObject<{
    body: z.ZodObject<{
        name: z.ZodOptional<z.ZodString>;
        slug: z.ZodOptional<z.ZodString>;
        description: z.ZodOptional<z.ZodString>;
        image: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        banner: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        featured: z.ZodOptional<z.ZodUnion<[z.ZodBoolean, z.ZodString]>>;
        homepageVisibility: z.ZodOptional<z.ZodUnion<[z.ZodBoolean, z.ZodString]>>;
        sortOrder: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNumber]>>;
        startDate: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNull]>>;
        endDate: z.ZodOptional<z.ZodUnion<[z.ZodString, z.ZodNull]>>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive", "archived"]>>;
    }, "strip", z.ZodTypeAny, {
        name?: string | undefined;
        slug?: string | undefined;
        image?: string | null | undefined;
        banner?: string | null | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        startDate?: string | null | undefined;
        endDate?: string | null | undefined;
    }, {
        name?: string | undefined;
        slug?: string | undefined;
        image?: string | null | undefined;
        banner?: string | null | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        startDate?: string | null | undefined;
        endDate?: string | null | undefined;
    }>;
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name?: string | undefined;
        slug?: string | undefined;
        image?: string | null | undefined;
        banner?: string | null | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        startDate?: string | null | undefined;
        endDate?: string | null | undefined;
    };
    params: {
        id: string;
    };
}, {
    body: {
        name?: string | undefined;
        slug?: string | undefined;
        image?: string | null | undefined;
        banner?: string | null | undefined;
        description?: string | undefined;
        featured?: string | boolean | undefined;
        sortOrder?: string | number | undefined;
        homepageVisibility?: string | boolean | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        startDate?: string | null | undefined;
        endDate?: string | null | undefined;
    };
    params: {
        id: string;
    };
}>;
export declare const collectionListSchema: z.ZodObject<{
    query: z.ZodObject<{
        page: z.ZodOptional<z.ZodString>;
        limit: z.ZodOptional<z.ZodString>;
        search: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodString>;
        sort: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }, {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}, {
    query: {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}>;
export declare const collectionMoveSchema: z.ZodObject<{
    body: z.ZodObject<{
        targetId: z.ZodUnion<[z.ZodString, z.ZodNumber, z.ZodNull]>;
    }, "strip", z.ZodTypeAny, {
        targetId: string | number | null;
    }, {
        targetId: string | number | null;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        targetId: string | number | null;
    };
}, {
    body: {
        targetId: string | number | null;
    };
}>;
export declare const vendorCreateSchema: z.ZodObject<{
    body: z.ZodObject<{
        logo: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        name: z.ZodString;
        slug: z.ZodOptional<z.ZodString>;
        description: z.ZodOptional<z.ZodString>;
        contact: z.ZodOptional<z.ZodString>;
        phone: z.ZodOptional<z.ZodString>;
        email: z.ZodOptional<z.ZodString>;
        address: z.ZodOptional<z.ZodString>;
        notes: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive", "archived"]>>;
    }, "strip", z.ZodTypeAny, {
        name: string;
        slug?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | null | undefined;
        contact?: string | undefined;
        phone?: string | undefined;
        email?: string | undefined;
        address?: string | undefined;
        notes?: string | undefined;
    }, {
        name: string;
        slug?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | null | undefined;
        contact?: string | undefined;
        phone?: string | undefined;
        email?: string | undefined;
        address?: string | undefined;
        notes?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name: string;
        slug?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | null | undefined;
        contact?: string | undefined;
        phone?: string | undefined;
        email?: string | undefined;
        address?: string | undefined;
        notes?: string | undefined;
    };
}, {
    body: {
        name: string;
        slug?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | null | undefined;
        contact?: string | undefined;
        phone?: string | undefined;
        email?: string | undefined;
        address?: string | undefined;
        notes?: string | undefined;
    };
}>;
export declare const vendorUpdateSchema: z.ZodObject<{
    body: z.ZodObject<{
        name: z.ZodOptional<z.ZodString>;
        logo: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        slug: z.ZodOptional<z.ZodString>;
        description: z.ZodOptional<z.ZodString>;
        contact: z.ZodOptional<z.ZodString>;
        phone: z.ZodOptional<z.ZodString>;
        email: z.ZodOptional<z.ZodString>;
        address: z.ZodOptional<z.ZodString>;
        notes: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive", "archived"]>>;
    }, "strip", z.ZodTypeAny, {
        name?: string | undefined;
        slug?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | null | undefined;
        contact?: string | undefined;
        phone?: string | undefined;
        email?: string | undefined;
        address?: string | undefined;
        notes?: string | undefined;
    }, {
        name?: string | undefined;
        slug?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | null | undefined;
        contact?: string | undefined;
        phone?: string | undefined;
        email?: string | undefined;
        address?: string | undefined;
        notes?: string | undefined;
    }>;
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name?: string | undefined;
        slug?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | null | undefined;
        contact?: string | undefined;
        phone?: string | undefined;
        email?: string | undefined;
        address?: string | undefined;
        notes?: string | undefined;
    };
    params: {
        id: string;
    };
}, {
    body: {
        name?: string | undefined;
        slug?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | null | undefined;
        contact?: string | undefined;
        phone?: string | undefined;
        email?: string | undefined;
        address?: string | undefined;
        notes?: string | undefined;
    };
    params: {
        id: string;
    };
}>;
export declare const supplierCreateSchema: z.ZodObject<{
    body: z.ZodObject<{
        logo: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        name: z.ZodString;
        slug: z.ZodOptional<z.ZodString>;
        description: z.ZodOptional<z.ZodString>;
        contact: z.ZodOptional<z.ZodString>;
        phone: z.ZodOptional<z.ZodString>;
        email: z.ZodOptional<z.ZodString>;
        address: z.ZodOptional<z.ZodString>;
        notes: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive", "archived"]>>;
    }, "strip", z.ZodTypeAny, {
        name: string;
        slug?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | null | undefined;
        contact?: string | undefined;
        phone?: string | undefined;
        email?: string | undefined;
        address?: string | undefined;
        notes?: string | undefined;
    }, {
        name: string;
        slug?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | null | undefined;
        contact?: string | undefined;
        phone?: string | undefined;
        email?: string | undefined;
        address?: string | undefined;
        notes?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name: string;
        slug?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | null | undefined;
        contact?: string | undefined;
        phone?: string | undefined;
        email?: string | undefined;
        address?: string | undefined;
        notes?: string | undefined;
    };
}, {
    body: {
        name: string;
        slug?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | null | undefined;
        contact?: string | undefined;
        phone?: string | undefined;
        email?: string | undefined;
        address?: string | undefined;
        notes?: string | undefined;
    };
}>;
export declare const supplierUpdateSchema: z.ZodObject<{
    body: z.ZodObject<{
        name: z.ZodOptional<z.ZodString>;
        logo: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        slug: z.ZodOptional<z.ZodString>;
        description: z.ZodOptional<z.ZodString>;
        contact: z.ZodOptional<z.ZodString>;
        phone: z.ZodOptional<z.ZodString>;
        email: z.ZodOptional<z.ZodString>;
        address: z.ZodOptional<z.ZodString>;
        notes: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodEnum<["active", "inactive", "archived"]>>;
    }, "strip", z.ZodTypeAny, {
        name?: string | undefined;
        slug?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | null | undefined;
        contact?: string | undefined;
        phone?: string | undefined;
        email?: string | undefined;
        address?: string | undefined;
        notes?: string | undefined;
    }, {
        name?: string | undefined;
        slug?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | null | undefined;
        contact?: string | undefined;
        phone?: string | undefined;
        email?: string | undefined;
        address?: string | undefined;
        notes?: string | undefined;
    }>;
    params: z.ZodObject<{
        id: z.ZodString;
    }, "strip", z.ZodTypeAny, {
        id: string;
    }, {
        id: string;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        name?: string | undefined;
        slug?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | null | undefined;
        contact?: string | undefined;
        phone?: string | undefined;
        email?: string | undefined;
        address?: string | undefined;
        notes?: string | undefined;
    };
    params: {
        id: string;
    };
}, {
    body: {
        name?: string | undefined;
        slug?: string | undefined;
        description?: string | undefined;
        status?: "active" | "inactive" | "archived" | undefined;
        logo?: string | null | undefined;
        contact?: string | undefined;
        phone?: string | undefined;
        email?: string | undefined;
        address?: string | undefined;
        notes?: string | undefined;
    };
    params: {
        id: string;
    };
}>;
export declare const vendorListSchema: z.ZodObject<{
    query: z.ZodObject<{
        page: z.ZodOptional<z.ZodString>;
        limit: z.ZodOptional<z.ZodString>;
        search: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodString>;
        sort: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }, {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}, {
    query: {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}>;
export declare const supplierListSchema: z.ZodObject<{
    query: z.ZodObject<{
        page: z.ZodOptional<z.ZodString>;
        limit: z.ZodOptional<z.ZodString>;
        search: z.ZodOptional<z.ZodString>;
        status: z.ZodOptional<z.ZodString>;
        sort: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }, {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    query: {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}, {
    query: {
        status?: string | undefined;
        search?: string | undefined;
        sort?: string | undefined;
        limit?: string | undefined;
        page?: string | undefined;
    };
}>;
export declare const vendorMoveSchema: z.ZodObject<{
    body: z.ZodObject<{
        targetId: z.ZodUnion<[z.ZodString, z.ZodNumber, z.ZodNull]>;
    }, "strip", z.ZodTypeAny, {
        targetId: string | number | null;
    }, {
        targetId: string | number | null;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        targetId: string | number | null;
    };
}, {
    body: {
        targetId: string | number | null;
    };
}>;
export declare const supplierMoveSchema: z.ZodObject<{
    body: z.ZodObject<{
        targetId: z.ZodUnion<[z.ZodString, z.ZodNumber, z.ZodNull]>;
    }, "strip", z.ZodTypeAny, {
        targetId: string | number | null;
    }, {
        targetId: string | number | null;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        targetId: string | number | null;
    };
}, {
    body: {
        targetId: string | number | null;
    };
}>;
export declare const idParamSchema: z.ZodObject<{
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
//# sourceMappingURL=catalog.schema.d.ts.map