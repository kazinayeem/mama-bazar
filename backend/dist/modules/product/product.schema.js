"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.saveDraftSchema = exports.bulkProductSchema = exports.productListSchema = exports.productSlugSchema = exports.productIdSchema = exports.updateProductSchema = exports.createProductSchema = void 0;
const zod_1 = require("zod");
const numeric = zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional();
const boolish = zod_1.z.union([zod_1.z.boolean(), zod_1.z.string()]).optional();
const refId = (message) => zod_1.z
    .union([zod_1.z.string(), zod_1.z.number()], { errorMap: () => ({ message }) })
    .nullish();
const refNum = (message) => zod_1.z
    .union([zod_1.z.string(), zod_1.z.number()], { errorMap: () => ({ message }) })
    .optional();
const jsonArray = zod_1.z.union([zod_1.z.string(), zod_1.z.array(zod_1.z.any())]).optional();
const variantSchema = zod_1.z.object({
    id: zod_1.z.number().optional(),
    name: zod_1.z.string(),
    options: zod_1.z.record(zod_1.z.string(), zod_1.z.string()).optional(),
    price: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    salePrice: refNum("Invalid sale price"),
    discountPrice: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    sku: zod_1.z.string().optional(),
    barcode: zod_1.z.string().optional(),
    stock: refNum("Invalid stock"),
    weight: zod_1.z.string().optional(),
    dimensions: zod_1.z.string().optional(),
    images: zod_1.z.array(zod_1.z.string()).optional(),
    thumbnail: zod_1.z.string().optional(),
    status: zod_1.z.enum(["active", "inactive"]).optional(),
    shippingCost: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    warranty: zod_1.z.string().optional(),
    availability: zod_1.z.boolean().optional(),
});
const specSchema = zod_1.z.object({
    id: zod_1.z.number().optional(),
    label: zod_1.z.string().min(1),
    value: zod_1.z.string().min(1),
    sortOrder: zod_1.z.number().optional(),
});
const relationSchema = zod_1.z.object({
    relatedProductId: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]),
    type: zod_1.z.enum(["frequently_bought_together", "cross_sell", "up_sell", "accessories", "similar"]),
});
const productFields = {
    title: zod_1.z.string().min(1, "Title is required"),
    slug: zod_1.z
        .string()
        .max(255, "Slug must be 255 characters or fewer")
        .regex(/^[a-z0-9]+(?:-[a-z0-9]+)*$/, "Slug may only contain English letters, numbers and hyphens (e.g. samsung-tv-55). Bangla or Unicode characters are not allowed.")
        .optional(),
    description: zod_1.z.string().optional(),
    shortDescription: zod_1.z.string().optional(),
    price: zod_1.z.union([zod_1.z.string(), zod_1.z.number()], { errorMap: () => ({ message: "Invalid price" }) }).pipe(zod_1.z.coerce.number().positive("Price must be positive")),
    salePrice: refNum("Invalid sale price"),
    discount: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    costPrice: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    profitMargin: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    tax: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    vat: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    shippingCharge: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    codFee: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    flashSalePrice: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    wholesalePrice: refNum("Invalid sale price"),
    dealerPrice: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    categoryId: refId("Invalid category"),
    subCategoryId: refId("Invalid sub-category id"),
    childCategoryId: refId("Invalid child category id"),
    collectionId: refId("Invalid collection id"),
    brandId: refId("Invalid brand id"),
    brand: zod_1.z.string().optional(),
    vendorId: refId("Invalid vendor id"),
    supplierId: refId("Invalid supplier id"),
    supplier: zod_1.z.string().optional(),
    countryOfOrigin: zod_1.z.string().optional(),
    sku: zod_1.z.string().optional(),
    barcode: zod_1.z.string().optional(),
    tags: jsonArray,
    warranty: zod_1.z.string().optional(),
    weight: zod_1.z.string().optional(),
    dimensions: zod_1.z.string().optional(),
    features: jsonArray,
    returnPolicy: zod_1.z.string().optional(),
    warehouse: zod_1.z.string().optional(),
    videoUrl: zod_1.z.string().optional(),
    seoTitle: zod_1.z.string().optional(),
    seoDescription: zod_1.z.string().optional(),
    seoKeywords: zod_1.z.string().optional(),
    canonicalUrl: zod_1.z.string().optional(),
    ogImage: zod_1.z.string().optional(),
    twitterImage: zod_1.z.string().optional(),
    structuredData: zod_1.z.union([zod_1.z.string(), zod_1.z.record(zod_1.z.any(), zod_1.z.any())]).optional(),
    emiAvailable: boolish,
    isFeatured: boolish,
    isTrending: boolish,
    isFlashSale: boolish,
    isNewArrival: boolish,
    isBestSeller: boolish,
    isLimitedEdition: boolish,
    isOfficial: boolish,
    isHotDeal: boolish,
    isArchived: boolish,
    stock: refNum("Invalid stock"),
    lowStockAlert: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    minOrder: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    maxOrder: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    unlimitedStock: boolish,
    backorder: boolish,
    trackInventory: boolish,
    stockStatus: zod_1.z.string().optional(),
    productStatus: zod_1.z.enum(["draft", "published", "hidden", "archived", "coming_soon", "out_of_stock"]).optional(),
    images: zod_1.z.union([zod_1.z.string(), zod_1.z.array(zod_1.z.string())]).optional(),
    sizeOptions: jsonArray,
    colorOptions: zod_1.z
        .union([
        zod_1.z.string(),
        zod_1.z.array(zod_1.z.object({ name: zod_1.z.string(), value: zod_1.z.string().optional(), image: zod_1.z.string().optional() })),
    ])
        .optional(),
    paymentMethods: jsonArray,
    paymentPhoneNumber: zod_1.z.string().optional(),
    status: zod_1.z.enum(["active", "inactive"]).optional(),
    existingImages: zod_1.z.string().optional(),
    variants: zod_1.z.union([zod_1.z.string(), zod_1.z.array(variantSchema)]).optional(),
    specs: zod_1.z.union([zod_1.z.string(), zod_1.z.array(specSchema)]).optional(),
    relations: zod_1.z.union([zod_1.z.string(), zod_1.z.array(relationSchema)]).optional(),
};
const refinePriceSanity = (body, ctx) => {
    if (body.price === undefined || body.salePrice === undefined)
        return;
    const price = Number(body.price);
    const salePrice = Number(body.salePrice);
    if (Number.isFinite(price) && Number.isFinite(salePrice) && salePrice > price) {
        ctx.addIssue({
            code: "custom",
            path: ["salePrice"],
            message: "Sale price must not exceed regular price",
        });
    }
};
exports.createProductSchema = zod_1.z.object({
    body: zod_1.z.object(productFields).superRefine(refinePriceSanity),
});
// NOTE: `price` and `title` must be optional here — the admin UI sends partial
// updates (e.g. `{ isFeatured: true }` from the featured toggle). Requiring
// `price` made every partial PUT fail with "Invalid input".
exports.updateProductSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
    body: zod_1.z.object({
        ...productFields,
        title: zod_1.z.string().min(1).optional(),
        price: zod_1.z
            .union([zod_1.z.string(), zod_1.z.number()])
            .pipe(zod_1.z.coerce.number().positive("Price must be positive"))
            .optional(),
    }).superRefine(refinePriceSanity),
});
exports.productIdSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
});
exports.productSlugSchema = zod_1.z.object({
    params: zod_1.z.object({ slug: zod_1.z.string().min(1) }),
});
exports.productListSchema = zod_1.z.object({
    query: zod_1.z.object({
        page: zod_1.z.string().optional(),
        limit: zod_1.z.string().optional(),
        category: zod_1.z.string().optional(),
        search: zod_1.z.string().optional(),
        brand: zod_1.z.string().optional(),
        supplier: zod_1.z.string().optional(),
        vendor: zod_1.z.string().optional(),
        collection: zod_1.z.string().optional(),
        stock: zod_1.z.enum(["in_stock", "low_stock", "out_of_stock"]).optional(),
        minPrice: zod_1.z.string().optional(),
        maxPrice: zod_1.z.string().optional(),
        dateFrom: zod_1.z.string().optional(),
        dateTo: zod_1.z.string().optional(),
        sort: zod_1.z.string().optional(),
        status: zod_1.z.string().optional(),
        productStatus: zod_1.z.string().optional(),
        label: zod_1.z.string().optional(),
        tags: zod_1.z.string().optional(),
        sku: zod_1.z.string().optional(),
        barcode: zod_1.z.string().optional(),
        inStock: zod_1.z.string().optional(),
        minRating: zod_1.z.string().optional(),
        sale: zod_1.z.string().optional(),
    }),
});
exports.bulkProductSchema = zod_1.z.object({
    body: zod_1.z.object({
        ids: zod_1.z.array(zod_1.z.number()).min(1, "Select at least one product"),
        action: zod_1.z.enum(["delete", "publish", "archive", "hide", "draft", "feature", "unfeature"]),
    }),
});
exports.saveDraftSchema = zod_1.z.object({
    params: zod_1.z.object({ id: zod_1.z.string() }),
    body: zod_1.z.object({ draft: zod_1.z.record(zod_1.z.any(), zod_1.z.any()) }),
});
//# sourceMappingURL=product.schema.js.map