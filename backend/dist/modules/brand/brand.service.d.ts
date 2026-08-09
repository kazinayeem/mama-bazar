import { CreateBrandInput, UpdateBrandInput } from "./brand.interface";
export interface BrandListParams {
    page?: number;
    limit?: number;
    search?: string;
    status?: string;
    featured?: boolean;
    sort?: string;
}
export declare const getAll: (params?: BrandListParams) => Promise<{
    data: {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        featured: boolean;
        sortOrder: number;
        homepageVisibility: boolean;
        seoTitle: string | null;
        seoDescription: string | null;
        seoKeywords: string | null;
        status: "active" | "inactive" | "archived";
        createdAt: Date;
        logo: string | null;
        countryOfOrigin: string | null;
        bannerImage: string | null;
        website: string | null;
    }[];
    pagination: {
        page: number;
        limit: number;
        total: number;
        totalPages: number;
    };
}>;
export declare const getAllActive: () => Promise<{
    id: number;
    name: string;
    slug: string;
    description: string | null;
    featured: boolean;
    sortOrder: number;
    homepageVisibility: boolean;
    seoTitle: string | null;
    seoDescription: string | null;
    seoKeywords: string | null;
    status: "active" | "inactive" | "archived";
    createdAt: Date;
    logo: string | null;
    countryOfOrigin: string | null;
    bannerImage: string | null;
    website: string | null;
}[]>;
export declare const getById: (id: number) => Promise<{
    id: number;
    name: string;
    slug: string;
    description: string | null;
    featured: boolean;
    sortOrder: number;
    homepageVisibility: boolean;
    seoTitle: string | null;
    seoDescription: string | null;
    seoKeywords: string | null;
    status: "active" | "inactive" | "archived";
    createdAt: Date;
    logo: string | null;
    countryOfOrigin: string | null;
    bannerImage: string | null;
    website: string | null;
}>;
export declare const getBySlug: (slug: string) => Promise<{
    id: number;
    name: string;
    slug: string;
    description: string | null;
    featured: boolean;
    sortOrder: number;
    homepageVisibility: boolean;
    seoTitle: string | null;
    seoDescription: string | null;
    seoKeywords: string | null;
    status: "active" | "inactive" | "archived";
    createdAt: Date;
    logo: string | null;
    countryOfOrigin: string | null;
    bannerImage: string | null;
    website: string | null;
}>;
export declare const getUsage: (id: number) => Promise<number>;
export declare const create: (data: CreateBrandInput) => Promise<{
    id: number;
    name: string;
    slug: string;
    description: string | null;
    featured: boolean;
    sortOrder: number;
    homepageVisibility: boolean;
    seoTitle: string | null;
    seoDescription: string | null;
    seoKeywords: string | null;
    status: "active" | "inactive" | "archived";
    createdAt: Date;
    logo: string | null;
    countryOfOrigin: string | null;
    bannerImage: string | null;
    website: string | null;
}>;
export declare const update: (id: number, data: UpdateBrandInput) => Promise<{
    id: number;
    name: string;
    slug: string;
    description: string | null;
    featured: boolean;
    sortOrder: number;
    homepageVisibility: boolean;
    seoTitle: string | null;
    seoDescription: string | null;
    seoKeywords: string | null;
    status: "active" | "inactive" | "archived";
    createdAt: Date;
    logo: string | null;
    countryOfOrigin: string | null;
    bannerImage: string | null;
    website: string | null;
}>;
export declare const moveProducts: (fromId: number, targetId: number | null) => Promise<{
    moved: number;
}>;
export declare const remove: (id: number) => Promise<{
    success: boolean;
}>;
//# sourceMappingURL=brand.service.d.ts.map