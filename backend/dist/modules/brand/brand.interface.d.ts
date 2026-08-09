export type BrandStatus = "active" | "inactive" | "archived";
export interface CreateBrandInput {
    name: string;
    slug: string;
    logo?: string;
    bannerImage?: string;
    description?: string;
    website?: string;
    countryOfOrigin?: string;
    featured?: boolean;
    homepageVisibility?: boolean;
    sortOrder?: number;
    seoTitle?: string;
    seoDescription?: string;
    seoKeywords?: string;
    status?: BrandStatus;
}
export interface UpdateBrandInput {
    name?: string;
    slug?: string;
    logo?: string;
    bannerImage?: string;
    description?: string;
    website?: string;
    countryOfOrigin?: string;
    featured?: boolean;
    homepageVisibility?: boolean;
    sortOrder?: number;
    seoTitle?: string;
    seoDescription?: string;
    seoKeywords?: string;
    status?: BrandStatus;
}
//# sourceMappingURL=brand.interface.d.ts.map