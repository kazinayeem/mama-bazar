export type ProductStatusValue = "draft" | "published" | "hidden" | "archived" | "coming_soon" | "out_of_stock";
export type StockStatusValue = "in_stock" | "low_stock" | "out_of_stock" | "preorder" | "backorder";
export type ProductRelationType = "frequently_bought_together" | "cross_sell" | "up_sell" | "accessories" | "similar";
export interface ProductVariantInput {
    id?: number;
    name: string;
    options: Record<string, string>;
    price?: string | number;
    salePrice?: string | number;
    discountPrice?: string | number;
    sku?: string;
    barcode?: string;
    stock?: number;
    weight?: string;
    dimensions?: string;
    images?: string[];
    thumbnail?: string;
    status?: "active" | "inactive";
    shippingCost?: string | number;
    warranty?: string;
    availability?: boolean;
}
export interface ProductSpecInput {
    id?: number;
    label: string;
    value: string;
    sortOrder?: number;
}
export interface ProductRelationInput {
    relatedProductId: number;
    type: ProductRelationType;
}
export interface IProduct {
    id: number;
    title: string;
    slug: string;
    description?: string | null;
    shortDescription?: string | null;
    price: string;
    salePrice?: string | null;
    discount?: string | null;
    costPrice?: string | null;
    profitMargin?: string | null;
    tax?: string | null;
    vat?: string | null;
    shippingCharge?: string | null;
    codFee?: string | null;
    flashSalePrice?: string | null;
    wholesalePrice?: string | null;
    dealerPrice?: string | null;
    categoryId?: number | null;
    subCategoryId?: number | null;
    childCategoryId?: number | null;
    collectionId?: number | null;
    brandId?: number | null;
    brand?: string | null;
    vendorId?: number | null;
    supplierId?: number | null;
    supplier?: string | null;
    countryOfOrigin?: string | null;
    sku?: string | null;
    barcode?: string | null;
    tags?: string[] | null;
    warranty?: string | null;
    weight?: string | null;
    dimensions?: string | null;
    features?: string[] | null;
    returnPolicy?: string | null;
    warehouse?: string | null;
    videoUrl?: string | null;
    seoTitle?: string | null;
    seoDescription?: string | null;
    seoKeywords?: string | null;
    canonicalUrl?: string | null;
    ogImage?: string | null;
    twitterImage?: string | null;
    structuredData?: Record<string, unknown> | null;
    emiAvailable?: boolean;
    isFeatured?: boolean;
    isTrending?: boolean;
    isFlashSale?: boolean;
    isNewArrival?: boolean;
    isBestSeller?: boolean;
    isLimitedEdition?: boolean;
    isOfficial?: boolean;
    isHotDeal?: boolean;
    isArchived?: boolean;
    meta?: Record<string, unknown> | null;
    stock: number;
    lowStockAlert?: number;
    minOrder?: number;
    maxOrder?: number | null;
    unlimitedStock?: boolean;
    backorder?: boolean;
    trackInventory?: boolean;
    stockStatus?: string | null;
    productStatus?: string | null;
    images: string[];
    sizeOptions?: string[] | null;
    colorOptions?: Array<{
        name: string;
        value?: string;
        image?: string;
    }> | null;
    paymentMethods?: ["cod", "online"];
    paymentPhoneNumber?: string | null;
    status: "active" | "inactive";
    createdAt: Date;
}
export interface ProductFull extends IProduct {
    category?: {
        id: number;
        name: string;
        slug: string;
        parentId?: number | null;
    } | null;
    subCategory?: {
        id: number;
        name: string;
        slug: string;
    } | null;
    childCategory?: {
        id: number;
        name: string;
        slug: string;
    } | null;
    collection?: {
        id: number;
        name: string;
        slug: string;
        image?: string | null;
    } | null;
    vendor?: {
        id: number;
        name: string;
        slug: string;
        logo?: string | null;
    } | null;
    supplierInfo?: {
        id: number;
        name: string;
        slug: string;
    } | null;
    brandInfo?: {
        id: number;
        name: string;
        logo?: string | null;
        slug: string;
    } | null;
    variants: Array<{
        id: number;
        name: string;
        options: Record<string, string>;
        price: string | null;
        discountPrice: string | null;
        sku: string | null;
        barcode: string | null;
        stock: number;
        weight: string | null;
        dimensions: string | null;
        images: string[] | null;
        thumbnail: string | null;
        status: "active" | "inactive";
        shippingCost: string | null;
        warranty: string | null;
        availability: boolean;
    }>;
    specs: Array<{
        id: number;
        label: string;
        value: string;
        sortOrder: number;
    }>;
    relations: Array<{
        id: number;
        type: ProductRelationType;
        relatedProduct: {
            id: number;
            title: string;
            slug: string;
            price: string;
            discount: string | null;
            images: string[] | null;
        } | null;
    }>;
}
export interface CreateProductInput extends Partial<Omit<IProduct, "id" | "createdAt">> {
    title: string;
    slug: string;
    price: string;
    variants?: ProductVariantInput[];
    specs?: ProductSpecInput[];
    relations?: ProductRelationInput[];
}
export interface UpdateProductInput extends Partial<Omit<IProduct, "id" | "createdAt">> {
    variants?: ProductVariantInput[];
    specs?: ProductSpecInput[];
    relations?: ProductRelationInput[];
}
export interface ProductQuery {
    page?: number;
    limit?: number;
    category?: string;
    search?: string;
    brand?: string;
    supplier?: string;
    vendor?: string;
    collection?: string;
    stock?: "in_stock" | "low_stock" | "out_of_stock";
    minPrice?: number;
    maxPrice?: number;
    dateFrom?: string;
    dateTo?: string;
    sort?: string;
    status?: string;
    productStatus?: string;
    label?: string;
    tags?: string;
    sku?: string;
    barcode?: string;
    inStock?: boolean;
    minRating?: number;
    sale?: boolean;
}
//# sourceMappingURL=product.interface.d.ts.map