import { z } from "zod";

const numeric = z.union([z.string(), z.number()]).optional();
const boolish = z.union([z.boolean(), z.string()]).optional();
const refId = (message: string) =>
  z
    .union([z.string(), z.number()], { errorMap: () => ({ message }) })
    .nullish();

const refNum = (message: string) =>
  z
    .union([z.string(), z.number()], { errorMap: () => ({ message }) })
    .optional();

const jsonArray = z.union([z.string(), z.array(z.any())]).optional();

const variantSchema = z.object({
  id: z.number().optional(),
  name: z.string(),
  options: z.record(z.string(), z.string()).optional(),
  price: z.union([z.string(), z.number()]).optional(),
  salePrice: refNum("Invalid sale price"),
  discountPrice: z.union([z.string(), z.number()]).optional(),
  sku: z.string().optional(),
  barcode: z.string().optional(),
  stock: refNum("Invalid stock"),
  weight: z.string().optional(),
  dimensions: z.string().optional(),
  images: z.array(z.string()).optional(),
  thumbnail: z.string().optional(),
  status: z.enum(["active", "inactive"]).optional(),
  shippingCost: z.union([z.string(), z.number()]).optional(),
  warranty: z.string().optional(),
  availability: z.boolean().optional(),
});

const specSchema = z.object({
  id: z.number().optional(),
  label: z.string().min(1),
  value: z.string().min(1),
  sortOrder: z.number().optional(),
});

const relationSchema = z.object({
  relatedProductId: z.union([z.string(), z.number()]),
  type: z.enum(["frequently_bought_together", "cross_sell", "up_sell", "accessories", "similar"]),
});

const productFields = {
  title: z.string().min(1, "Title is required"),
  description: z.string().optional(),
  shortDescription: z.string().optional(),
  price: z.union([z.string(), z.number()], { errorMap: () => ({ message: "Invalid price" }) }).pipe(z.coerce.number().positive("Price must be positive")),
  salePrice: refNum("Invalid sale price"),
  discount: z.union([z.string(), z.number()]).optional(),
  costPrice: z.union([z.string(), z.number()]).optional(),
  profitMargin: z.union([z.string(), z.number()]).optional(),
  tax: z.union([z.string(), z.number()]).optional(),
  vat: z.union([z.string(), z.number()]).optional(),
  shippingCharge: z.union([z.string(), z.number()]).optional(),
  codFee: z.union([z.string(), z.number()]).optional(),
  flashSalePrice: z.union([z.string(), z.number()]).optional(),
  wholesalePrice: refNum("Invalid sale price"),
  dealerPrice: z.union([z.string(), z.number()]).optional(),
  categoryId: refId("Invalid category"),
  subCategoryId: refId("Invalid sub-category id"),
  childCategoryId: refId("Invalid child category id"),
  collectionId: refId("Invalid collection id"),
  brandId: refId("Invalid brand id"),
  brand: z.string().optional(),
  vendorId: refId("Invalid vendor id"),
  supplierId: refId("Invalid supplier id"),
  supplier: z.string().optional(),
  countryOfOrigin: z.string().optional(),
  sku: z.string().optional(),
  barcode: z.string().optional(),
  tags: jsonArray,
  warranty: z.string().optional(),
  weight: z.string().optional(),
  dimensions: z.string().optional(),
  features: jsonArray,
  returnPolicy: z.string().optional(),
  warehouse: z.string().optional(),
  videoUrl: z.string().optional(),
  seoTitle: z.string().optional(),
  seoDescription: z.string().optional(),
  seoKeywords: z.string().optional(),
  canonicalUrl: z.string().optional(),
  ogImage: z.string().optional(),
  twitterImage: z.string().optional(),
  structuredData: z.union([z.string(), z.record(z.any(), z.any())]).optional(),
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
  lowStockAlert: z.union([z.string(), z.number()]).optional(),
  minOrder: z.union([z.string(), z.number()]).optional(),
  maxOrder: z.union([z.string(), z.number()]).optional(),
  unlimitedStock: boolish,
  backorder: boolish,
  trackInventory: boolish,
  stockStatus: z.string().optional(),
  productStatus: z.enum(["draft", "published", "hidden", "archived", "coming_soon", "out_of_stock"]).optional(),
  images: z.union([z.string(), z.array(z.string())]).optional(),
  sizeOptions: jsonArray,
  colorOptions: z
    .union([
      z.string(),
      z.array(z.object({ name: z.string(), value: z.string().optional(), image: z.string().optional() })),
    ])
    .optional(),
  paymentMethods: jsonArray,
  paymentPhoneNumber: z.string().optional(),
  status: z.enum(["active", "inactive"]).optional(),
  existingImages: z.string().optional(),
  variants: z.union([z.string(), z.array(variantSchema)]).optional(),
  specs: z.union([z.string(), z.array(specSchema)]).optional(),
  relations: z.union([z.string(), z.array(relationSchema)]).optional(),
};

const refinePriceSanity = (
  body: { price?: unknown; salePrice?: unknown },
  ctx: z.RefinementCtx
) => {
  if (body.price === undefined || body.salePrice === undefined) return;
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

export const createProductSchema = z.object({
  body: z.object(productFields).superRefine(refinePriceSanity),
});

// NOTE: `price` and `title` must be optional here — the admin UI sends partial
// updates (e.g. `{ isFeatured: true }` from the featured toggle). Requiring
// `price` made every partial PUT fail with "Invalid input".
export const updateProductSchema = z.object({
  params: z.object({ id: z.string() }),
  body: z.object({
    ...productFields,
    title: z.string().min(1).optional(),
    price: z
      .union([z.string(), z.number()])
      .pipe(z.coerce.number().positive("Price must be positive"))
      .optional(),
  }).superRefine(refinePriceSanity),
});

export const productIdSchema = z.object({
  params: z.object({ id: z.string() }),
});

export const productSlugSchema = z.object({
  params: z.object({ slug: z.string().min(1) }),
});

export const productListSchema = z.object({
  query: z.object({
    page: z.string().optional(),
    limit: z.string().optional(),
    category: z.string().optional(),
    search: z.string().optional(),
    brand: z.string().optional(),
    supplier: z.string().optional(),
    vendor: z.string().optional(),
    collection: z.string().optional(),
    stock: z.enum(["in_stock", "low_stock", "out_of_stock"]).optional(),
    minPrice: z.string().optional(),
    maxPrice: z.string().optional(),
    dateFrom: z.string().optional(),
    dateTo: z.string().optional(),
    sort: z.string().optional(),
    status: z.string().optional(),
    productStatus: z.string().optional(),
    label: z.string().optional(),
    tags: z.string().optional(),
    sku: z.string().optional(),
    barcode: z.string().optional(),
    inStock: z.string().optional(),
    minRating: z.string().optional(),
    sale: z.string().optional(),
  }),
});

export const bulkProductSchema = z.object({
  body: z.object({
    ids: z.array(z.number()).min(1, "Select at least one product"),
    action: z.enum(["delete", "publish", "archive", "hide", "draft", "feature", "unfeature"]),
  }),
});

export const saveDraftSchema = z.object({
  params: z.object({ id: z.string() }),
  body: z.object({ draft: z.record(z.any(), z.any()) }),
});
