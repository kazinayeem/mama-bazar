export type CategoryStatus = "active" | "inactive" | "archived";
export interface ICategory {
    id: number;
    name: string;
    slug: string;
    parentId?: number | null;
    image?: string | null;
    icon?: string | null;
    banner?: string | null;
    thumbnail?: string | null;
    description?: string | null;
    featured: boolean;
    homepageVisibility: boolean;
    sortOrder: number;
    seoTitle?: string | null;
    seoDescription?: string | null;
    seoKeywords?: string | null;
    status: CategoryStatus;
    createdAt: Date;
}
export interface CreateCategoryInput {
    name: string;
    slug: string;
    parentId?: number | null;
    image?: string;
    icon?: string;
    banner?: string;
    thumbnail?: string;
    description?: string;
    featured?: boolean;
    homepageVisibility?: boolean;
    sortOrder?: number;
    seoTitle?: string;
    seoDescription?: string;
    seoKeywords?: string;
    status?: CategoryStatus;
}
export interface UpdateCategoryInput {
    name?: string;
    slug?: string;
    parentId?: number | null;
    image?: string;
    icon?: string;
    banner?: string;
    thumbnail?: string;
    description?: string;
    featured?: boolean;
    homepageVisibility?: boolean;
    sortOrder?: number;
    seoTitle?: string;
    seoDescription?: string;
    seoKeywords?: string;
    status?: CategoryStatus;
}
export interface CategoryUsage {
    products: number;
    subCategories: number;
}
export interface CategoryTreeNode extends ICategory {
    children: CategoryTreeNode[];
}
//# sourceMappingURL=category.interface.d.ts.map