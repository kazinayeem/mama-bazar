import { db } from "../../config/db";
import { AppError } from "../../utils/AppError";
import {
  products,
  categories,
  brands,
  collections,
  vendors,
  suppliers,
  productVariants,
  productSpecs,
  productRelations,
  reviews,
} from "../../config/schema";
import { eq, like, or, and, gte, lte, sql, desc, asc, inArray } from "drizzle-orm";
import {
  CreateProductInput,
  UpdateProductInput,
  ProductQuery,
  ProductVariantInput,
  ProductSpecInput,
  ProductRelationInput,
  ProductRelationType,
} from "./product.interface";

const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 12;
const DEFAULT_STATUS = "active";
const RELATED_LIMIT = 8;

/**
 * Ensure a slug is unique before inserting/updating.
 * - `autoSuffix: true`  — generated slugs: append `-2`, `-3`, … until free.
 * - `autoSuffix: false` — user-provided slug: throw a 409 with a clear message.
 */
export const ensureUniqueSlug = async (
  slug: string,
  opts: { excludeId?: number; autoSuffix?: boolean } = {}
): Promise<string> => {
  let candidate = slug;
  let suffix = 2;
  for (;;) {
    const existing = await db
      .select({ id: products.id })
      .from(products)
      .where(eq(products.slug, candidate))
      .limit(1);
    const isFree = !existing.length || existing[0].id === opts.excludeId;
    if (isFree) return candidate;
    if (!opts.autoSuffix) {
      throw new AppError(
        409,
        `Slug "${slug}" is already in use by another product. Please choose a different slug.`
      );
    }
    candidate = `${slug}-${suffix++}`;
    if (suffix > 1000) return candidate;
  }
};

const toNum = (v: unknown): number | null => {
  if (v === undefined || v === null || v === "") return null;
  const n = Number(v);
  return Number.isNaN(n) ? null : n;
};

const toStr = (v: unknown): string | null => {
  if (v === undefined || v === null || v === "") return null;
  return String(v);
};

export const deriveProfitMargin = (selling: unknown, cost: unknown): string => {
  const s = toNum(selling) ?? 0;
  const c = toNum(cost) ?? 0;
  if (!s || s <= 0) return "0";
  return String(Math.round(((s - c) / s) * 100 * 100) / 100);
};

export const deriveStockStatus = (
  stock: number,
  lowStockAlert: number | null,
  unlimited: boolean | null,
  explicit?: string | null
): string => {
  if (explicit) return explicit;
  if (unlimited) return "in_stock";
  if (stock <= 0) return "out_of_stock";
  if (lowStockAlert !== null && stock <= lowStockAlert) return "low_stock";
  return "in_stock";
};

const deriveStatusFromProductStatus = (productStatus?: string | null, status?: string | null): "active" | "inactive" => {
  if (productStatus === "published") return "active";
  if (productStatus && productStatus !== "published") return "inactive";
  return (status as "active" | "inactive") || DEFAULT_STATUS;
};

const productColumns = {
  id: products.id,
  title: products.title,
  slug: products.slug,
  description: products.description,
  shortDescription: products.shortDescription,
  price: products.price,
  salePrice: products.salePrice,
  discount: products.discount,
  costPrice: products.costPrice,
  profitMargin: products.profitMargin,
  tax: products.tax,
  vat: products.vat,
  shippingCharge: products.shippingCharge,
  codFee: products.codFee,
  flashSalePrice: products.flashSalePrice,
  wholesalePrice: products.wholesalePrice,
  dealerPrice: products.dealerPrice,
  categoryId: products.categoryId,
  subCategoryId: products.subCategoryId,
  childCategoryId: products.childCategoryId,
  collectionId: products.collectionId,
  brandId: products.brandId,
  brand: products.brand,
  vendorId: products.vendorId,
  supplierId: products.supplierId,
  supplier: products.supplier,
  countryOfOrigin: products.countryOfOrigin,
  sku: products.sku,
  barcode: products.barcode,
  tags: products.tags,
  warranty: products.warranty,
  weight: products.weight,
  dimensions: products.dimensions,
  features: products.features,
  returnPolicy: products.returnPolicy,
  warehouse: products.warehouse,
  videoUrl: products.videoUrl,
  seoTitle: products.seoTitle,
  seoDescription: products.seoDescription,
  seoKeywords: products.seoKeywords,
  canonicalUrl: products.canonicalUrl,
  ogImage: products.ogImage,
  twitterImage: products.twitterImage,
  structuredData: products.structuredData,
  emiAvailable: products.emiAvailable,
  isFeatured: products.isFeatured,
  isTrending: products.isTrending,
  isFlashSale: products.isFlashSale,
  isNewArrival: products.isNewArrival,
  isBestSeller: products.isBestSeller,
  isLimitedEdition: products.isLimitedEdition,
  isOfficial: products.isOfficial,
  isHotDeal: products.isHotDeal,
  isArchived: products.isArchived,
  meta: products.meta,
  stock: products.stock,
  lowStockAlert: products.lowStockAlert,
  minOrder: products.minOrder,
  maxOrder: products.maxOrder,
  unlimitedStock: products.unlimitedStock,
  backorder: products.backorder,
  trackInventory: products.trackInventory,
  stockStatus: products.stockStatus,
  productStatus: products.productStatus,
  images: products.images,
  sizeOptions: products.sizeOptions,
  colorOptions: products.colorOptions,
  paymentMethods: products.paymentMethods,
  paymentPhoneNumber: products.paymentPhoneNumber,
  status: products.status,
  createdAt: products.createdAt,
  categoryName: categories.name,
  categorySlug: categories.slug,
  categoryParentId: categories.parentId,
  subCategoryName: sql`sc.name`,
  subCategorySlug: sql`sc.slug`,
  childCategoryName: sql`cc.name`,
  childCategorySlug: sql`cc.slug`,
  collectionName: collections.name,
  collectionSlug: collections.slug,
  collectionImage: collections.image,
  vendorName: vendors.name,
  vendorSlug: vendors.slug,
  vendorLogo: vendors.logo,
  supplierName: suppliers.name,
  supplierSlug: suppliers.slug,
  brandName: brands.name,
  brandLogo: brands.logo,
  brandSlug: brands.slug,
};

export const formatProductRow = (row: any, ratingInfo?: { rating: number | null; reviewCount: number }) => {
  const isTrue = (v: any) => v === true || v === 1 || v === "1";
  return {
    id: row.id,
    title: row.title,
    slug: row.slug,
    description: row.description,
    shortDescription: row.shortDescription,
    price: row.price,
    salePrice: row.salePrice,
    discount: row.discount,
    costPrice: row.costPrice,
    profitMargin: row.profitMargin,
    tax: row.tax,
    vat: row.vat,
    shippingCharge: row.shippingCharge,
    codFee: row.codFee,
    flashSalePrice: row.flashSalePrice,
    wholesalePrice: row.wholesalePrice,
    dealerPrice: row.dealerPrice,
    categoryId: row.categoryId,
    subCategoryId: row.subCategoryId,
    childCategoryId: row.childCategoryId,
    collectionId: row.collectionId,
    brandId: row.brandId,
    brand: row.brand,
    vendorId: row.vendorId,
    supplierId: row.supplierId,
    supplier: row.supplier,
    countryOfOrigin: row.countryOfOrigin,
    sku: row.sku,
    barcode: row.barcode,
    tags: row.tags,
    warranty: row.warranty,
    weight: row.weight,
    dimensions: row.dimensions,
    features: row.features,
    returnPolicy: row.returnPolicy,
    warehouse: row.warehouse,
    videoUrl: row.videoUrl,
    seoTitle: row.seoTitle,
    seoDescription: row.seoDescription,
    seoKeywords: row.seoKeywords,
    canonicalUrl: row.canonicalUrl,
    ogImage: row.ogImage,
    twitterImage: row.twitterImage,
    structuredData: row.structuredData,
    emiAvailable: isTrue(row.emiAvailable),
    isFeatured: isTrue(row.isFeatured),
    isTrending: isTrue(row.isTrending),
    isFlashSale: isTrue(row.isFlashSale),
    isNewArrival: isTrue(row.isNewArrival),
    isBestSeller: isTrue(row.isBestSeller),
    isLimitedEdition: isTrue(row.isLimitedEdition),
    isOfficial: isTrue(row.isOfficial),
    isHotDeal: isTrue(row.isHotDeal),
    isArchived: isTrue(row.isArchived),
    meta: row.meta,
    stock: row.stock,
    lowStockAlert: row.lowStockAlert,
    minOrder: row.minOrder,
    maxOrder: row.maxOrder,
    unlimitedStock: isTrue(row.unlimitedStock),
    backorder: isTrue(row.backorder),
    trackInventory: isTrue(row.trackInventory),
    stockStatus: row.stockStatus,
    productStatus: row.productStatus,
    images: row.images || [],
    sizeOptions: row.sizeOptions,
    colorOptions: row.colorOptions,
    paymentMethods: row.paymentMethods,
    paymentPhoneNumber: row.paymentPhoneNumber,
    status: row.status,
    createdAt: row.createdAt,
    category: row.categoryName
      ? { id: row.categoryId, name: row.categoryName, slug: row.categorySlug, parentId: row.categoryParentId }
      : null,
    subCategory: row.subCategoryName ? { id: row.subCategoryId, name: row.subCategoryName, slug: row.subCategorySlug } : null,
    childCategory: row.childCategoryName ? { id: row.childCategoryId, name: row.childCategoryName, slug: row.childCategorySlug } : null,
    collection: row.collectionName
      ? { id: row.collectionId, name: row.collectionName, slug: row.collectionSlug, image: row.collectionImage }
      : null,
    vendor: row.vendorName ? { id: row.vendorId, name: row.vendorName, slug: row.vendorSlug, logo: row.vendorLogo } : null,
    supplierInfo: row.supplierName ? { id: row.supplierId, name: row.supplierName, slug: row.supplierSlug } : null,
    brandInfo: row.brandName
      ? { id: row.brandId, name: row.brandName, logo: row.brandLogo, slug: row.brandSlug }
      : null,
    rating: ratingInfo ? ratingInfo.rating : null,
    reviewCount: ratingInfo ? ratingInfo.reviewCount : 0,
  };
};

export const fetchRatingMap = async (productIds?: number[]) => {
  const where = productIds && productIds.length > 0 ? and(eq(reviews.status, "approved"), inArray(reviews.productId, productIds)) : eq(reviews.status, "approved");
  const rows = await db
    .select({
      productId: reviews.productId,
      rating: sql<number>`ROUND(AVG(${reviews.rating}), 1)`,
      reviewCount: sql<number>`count(*)`,
    })
    .from(reviews)
    .where(where)
    .groupBy(reviews.productId);
  const map = new Map<number, { rating: number | null; reviewCount: number }>();
  rows.forEach((r) => map.set(r.productId, { rating: Number(r.rating), reviewCount: Number(r.reviewCount) }));
  return map;
};

const buildWhere = async (query: ProductQuery) => {
  const conditions: any[] = [];

  if (query.status && query.status !== "all") {
    conditions.push(eq(products.status, query.status as any));
  } else if (!query.status && !query.productStatus) {
    conditions.push(eq(products.status, DEFAULT_STATUS));
  }

  if (query.productStatus) {
    conditions.push(eq(products.productStatus, query.productStatus));
  }

  if (query.search) {
    const term = `%${query.search}%`;
    conditions.push(
      or(
        like(products.title, term),
        like(products.sku, term),
        like(products.barcode, term),
        like(products.brand, term),
        sql`${products.tags} LIKE ${term}`
      )
    );
  }

  if (query.sku) conditions.push(like(products.sku, `%${query.sku}%`));
  if (query.barcode) conditions.push(like(products.barcode, `%${query.barcode}%`));
  if (query.tags) conditions.push(sql`${products.tags} LIKE ${`%${query.tags}%`}`);

  if (query.category) {
    const catRows = await db.select().from(categories).where(eq(categories.slug, query.category)).limit(1);
    if (catRows[0]) {
      conditions.push(
        or(eq(products.categoryId, catRows[0].id), eq(products.subCategoryId, catRows[0].id), eq(products.childCategoryId, catRows[0].id))
      );
    }
  }

  if (query.brand) {
    const brandRows = await db.select().from(brands).where(eq(brands.slug, query.brand)).limit(1);
    if (brandRows[0]) conditions.push(eq(products.brandId, brandRows[0].id));
  }

  if (query.supplier) {
    const supplierRows = await db.select().from(suppliers).where(eq(suppliers.slug, query.supplier)).limit(1);
    if (supplierRows[0]) conditions.push(eq(products.supplierId, supplierRows[0].id));
  }

  if (query.vendor) {
    const vendorRows = await db.select().from(vendors).where(eq(vendors.slug, query.vendor)).limit(1);
    if (vendorRows[0]) conditions.push(eq(products.vendorId, vendorRows[0].id));
  }

  if (query.collection) {
    const collectionRows = await db.select().from(collections).where(eq(collections.slug, query.collection)).limit(1);
    if (collectionRows[0]) conditions.push(eq(products.collectionId, collectionRows[0].id));
  }

  if (query.stock) {
    if (query.stock === "in_stock") {
      conditions.push(or(sql`${products.stock} > 0`, eq(products.unlimitedStock, true)));
    } else if (query.stock === "low_stock") {
      conditions.push(and(sql`${products.stock} > 0`, sql`${products.stock} <= COALESCE(${products.lowStockAlert}, 5)`));
    } else if (query.stock === "out_of_stock") {
      conditions.push(or(sql`${products.stock} <= 0`, eq(products.stock, 0)));
    }
  }

  if (query.minPrice) {
    conditions.push(gte(products.price, String(query.minPrice)));
  }
  if (query.maxPrice) {
    conditions.push(lte(products.price, String(query.maxPrice)));
  }

  if (query.dateFrom) {
    conditions.push(gte(products.createdAt, new Date(query.dateFrom)));
  }
  if (query.dateTo) {
    conditions.push(lte(products.createdAt, new Date(query.dateTo)));
  }

  if (query.inStock) {
    conditions.push(or(sql`${products.stock} > 0`, eq(products.unlimitedStock, true)));
  }

  if (query.sale) {
    conditions.push(or(sql`${products.discount} > 0`, sql`${products.salePrice} IS NOT NULL`));
  }

  if (query.label) {
    const labelMap: Record<string, any> = {
      featured: products.isFeatured,
      trending: products.isTrending,
      flash_sale: products.isFlashSale,
      new_arrival: products.isNewArrival,
      best_seller: products.isBestSeller,
      limited_edition: products.isLimitedEdition,
      official: products.isOfficial,
      hot_deal: products.isHotDeal,
    };
    if (labelMap[query.label]) conditions.push(eq(labelMap[query.label], true));
  }

  const minRating = toNum(query.minRating);
  if (minRating) {
    const rated = await db
      .select({ productId: reviews.productId })
      .from(reviews)
      .where(eq(reviews.status, "approved"))
      .groupBy(reviews.productId)
      .having(sql`AVG(${reviews.rating}) >= ${minRating}`);
    const ids = rated.map((r) => r.productId);
    if (ids.length === 0) {
      conditions.push(sql`0 = 1`);
    } else {
      conditions.push(inArray(products.id, ids));
    }
  }

  return conditions.length > 0 ? and(...conditions) : undefined;
};

export const fullQuery = () =>
  db
    .select(productColumns)
    .from(products)
    .leftJoin(categories, eq(products.categoryId, categories.id))
    .leftJoin(sql`categories AS sc`, eq(products.subCategoryId, sql`sc.id`))
    .leftJoin(sql`categories AS cc`, eq(products.childCategoryId, sql`cc.id`))
    .leftJoin(collections, eq(products.collectionId, collections.id))
    .leftJoin(vendors, eq(products.vendorId, vendors.id))
    .leftJoin(suppliers, eq(products.supplierId, suppliers.id))
    .leftJoin(brands, eq(products.brandId, brands.id));

const fetchChildren = async (productId: number) => {
  const [variantRows, specRows, relationRows] = await Promise.all([
    db.select().from(productVariants).where(eq(productVariants.productId, productId)).orderBy(asc(productVariants.id)),
    db.select().from(productSpecs).where(eq(productSpecs.productId, productId)).orderBy(asc(productSpecs.sortOrder)),
    db.select().from(productRelations).where(eq(productRelations.productId, productId)),
  ]);

  const relatedIds = relationRows.map((r) => r.relatedProductId);
  const relatedProducts = relatedIds.length
    ? await db
        .select({ id: products.id, title: products.title, slug: products.slug, price: products.price, discount: products.discount, images: products.images })
        .from(products)
        .where(inArray(products.id, relatedIds))
    : [];
  const relatedMap = new Map(relatedProducts.map((p) => [p.id, p]));

  return {
    variants: variantRows.map((v) => ({
      id: v.id,
      name: v.name,
      options: v.options,
      price: v.price,
      discountPrice: v.discountPrice,
      sku: v.sku,
      barcode: v.barcode,
      stock: v.stock,
      weight: v.weight,
      dimensions: v.dimensions,
      images: v.images,
      thumbnail: v.thumbnail,
      status: v.status,
      shippingCost: v.shippingCost,
      warranty: v.warranty,
      availability: Boolean(v.availability),
    })),
    specs: specRows.map((s) => ({ id: s.id, label: s.label, value: s.value, sortOrder: s.sortOrder })),
    relations: relationRows.map((r) => ({
      id: r.id,
      type: r.type as ProductRelationType,
      relatedProduct: relatedMap.get(r.relatedProductId) || null,
    })),
  };
};

const toFullProduct = async (row: any, ratingInfo?: { rating: number | null; reviewCount: number }) => {
  if (!row) return null;
  const base = formatProductRow(row, ratingInfo);
  const children = await fetchChildren(base.id);
  return { ...base, ...children };
};

export const getAll = async (query: ProductQuery) => {
  const page = query.page || DEFAULT_PAGE;
  const limit = query.limit || DEFAULT_LIMIT;
  const offset = (page - 1) * limit;

  const where = await buildWhere(query);

  const orderByClause =
    query.sort === "oldest"
      ? asc(products.createdAt)
      : query.sort === "price_asc"
      ? asc(products.price)
      : query.sort === "price_desc"
      ? desc(products.price)
      : query.sort === "stock_asc"
      ? asc(products.stock)
      : query.sort === "stock_desc"
      ? desc(products.stock)
      : query.sort === "title_asc"
      ? asc(products.title)
      : query.sort === "title_desc"
      ? desc(products.title)
      : query.sort === "rating_desc"
      ? desc(sql`(SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = ${products.id} AND r.status = 'approved')`)
      : desc(products.createdAt);

  const data = await fullQuery().where(where).orderBy(orderByClause).limit(limit).offset(offset);

  const countResult = await db.select({ count: sql<number>`count(*)` }).from(products).where(where);
  const total = Number(countResult[0].count);

  const ratingMap = await fetchRatingMap(data.map((row) => row.id));

  return {
    data: data.map((row) => formatProductRow(row, ratingMap.get(row.id))),
    pagination: {
      page,
      limit,
      total,
      totalPages: Math.ceil(total / limit),
    },
  };
};

export const getById = async (id: number) => {
  const rows = await fullQuery().where(eq(products.id, id)).limit(1);
  if (!rows[0]) return null;
  const ratingMap = await fetchRatingMap([id]);
  return toFullProduct(rows[0], ratingMap.get(id));
};

export const getBySlug = async (slug: string) => {
  const rows = await fullQuery().where(eq(products.slug, slug)).limit(1);
  if (!rows[0]) return null;
  const ratingMap = await fetchRatingMap([rows[0].id]);
  return toFullProduct(rows[0], ratingMap.get(rows[0].id));
};

export const getRelated = async (categoryId: number, excludeId: number) => {
  const rows = await fullQuery()
    .where(
      and(
        eq(products.categoryId, categoryId),
        eq(products.status, DEFAULT_STATUS),
        sql`${products.id} != ${excludeId}`
      )
    )
    .limit(RELATED_LIMIT);
  const ratingMap = await fetchRatingMap(rows.map((row) => row.id));
  return rows.map((row) => formatProductRow(row, ratingMap.get(row.id)));
};

const hasId = (v: unknown): v is number => {
  if (v === undefined || v === null || v === "") return false;
  const n = Number(v);
  return Number.isInteger(n) && n > 0;
};

const validateProductRelations = async (
  data: Pick<CreateProductInput, "categoryId" | "subCategoryId" | "childCategoryId" | "brandId" | "collectionId" | "vendorId" | "supplierId">
): Promise<void> => {
  const errors: Record<string, string> = {};

  if (hasId(data.categoryId)) {
    const rows = await db
      .select({ id: categories.id })
      .from(categories)
      .where(eq(categories.id, Number(data.categoryId)))
      .limit(1);
    if (!rows.length) errors.categoryId = "Category not found";
  }

  if (hasId(data.subCategoryId)) {
    const rows = await db
      .select({ id: categories.id, parentId: categories.parentId })
      .from(categories)
      .where(eq(categories.id, Number(data.subCategoryId)))
      .limit(1);
    if (!rows.length) {
      errors.subCategoryId = "Sub-category not found";
    } else if (hasId(data.categoryId) && rows[0].parentId !== Number(data.categoryId)) {
      errors.subCategoryId = "Sub-category does not belong to selected category";
    }
  }

  if (hasId(data.childCategoryId)) {
    const rows = await db
      .select({ id: categories.id, parentId: categories.parentId })
      .from(categories)
      .where(eq(categories.id, Number(data.childCategoryId)))
      .limit(1);
    if (!rows.length) {
      errors.childCategoryId = "Child category not found";
    } else if (hasId(data.subCategoryId) && rows[0].parentId !== Number(data.subCategoryId)) {
      errors.childCategoryId = "Child category does not belong to selected sub-category";
    }
  }

  const refChecks: Array<[string, unknown, any]> = [
    ["brandId", data.brandId, brands],
    ["collectionId", data.collectionId, collections],
    ["vendorId", data.vendorId, vendors],
    ["supplierId", data.supplierId, suppliers],
  ];
  for (const [key, value, table] of refChecks) {
    if (!hasId(value)) continue;
    const rows = await db.select({ id: table.id }).from(table).where(eq(table.id, Number(value))).limit(1);
    if (!rows.length) {
      errors[key] = "Reference not found";
    }
  }

  if (Object.keys(errors).length) {
    throw new AppError(400, "Validation failed", { errors });
  }
};

const syncVariants = async (
  tx: any,
  productId: number,
  variants: ProductVariantInput[]
) => {
  if (!variants.length) {
    await tx.delete(productVariants).where(eq(productVariants.productId, productId));
    return;
  }

  const existing = await tx
    .select({ id: productVariants.id })
    .from(productVariants)
    .where(eq(productVariants.productId, productId));
  const existingIds = new Set<number>(existing.map((e: any) => Number(e.id)));
  const keptIds = new Set<number>();
  const seenNames = new Set<string>();
  const seenSkus = new Set<string>();

  for (const v of variants) {
    const nameLower = v.name.toLowerCase();
    if (seenNames.has(nameLower)) {
      throw new AppError(400, `Duplicate variant name: "${v.name}"`);
    }
    seenNames.add(nameLower);

    const priceNum = toNum(v.price);
    if (priceNum !== null && priceNum < 0) {
      throw new AppError(400, `Price cannot be negative for variant "${v.name}"`);
    }
    const salePriceNum = toNum(v.discountPrice ?? v.salePrice);
    if (salePriceNum !== null && salePriceNum < 0) {
      throw new AppError(400, `Sale price cannot be negative for variant "${v.name}"`);
    }
    const stockNum = toNum(v.stock) ?? 0;
    if (stockNum < 0) {
      throw new AppError(400, `Stock cannot be negative for variant "${v.name}"`);
    }

    const sku = toStr(v.sku);
    if (sku) {
      const skuLower = sku.toLowerCase();
      if (seenSkus.has(skuLower)) {
        throw new AppError(400, `Duplicate SKU: "${sku}"`);
      }
      seenSkus.add(skuLower);
      const duplicateSku = await tx
        .select({ id: productVariants.id })
        .from(productVariants)
        .where(eq(productVariants.sku, sku))
        .limit(1);
      if (duplicateSku.length > 0 && !existingIds.has(Number(duplicateSku[0].id))) {
        throw new AppError(400, `SKU "${sku}" already exists on another variant`);
      }
    }

    const data = {
      name: v.name,
      options: v.options || {},
      price: toStr(v.price),
      discountPrice: toStr(v.discountPrice ?? v.salePrice),
      sku: sku,
      barcode: toStr(v.barcode),
      stock: stockNum,
      weight: toStr(v.weight),
      dimensions: toStr(v.dimensions),
      images: v.images || [],
      thumbnail: v.thumbnail || null,
      status: v.status || "active",
      shippingCost: toStr(v.shippingCost),
      warranty: toStr(v.warranty),
      availability: v.availability === undefined ? true : v.availability,
    };
    const id = v.id !== undefined && v.id !== null ? Number(v.id) : null;
    if (id !== null && existingIds.has(id)) {
      await tx
        .update(productVariants)
        .set(data)
        .where(and(eq(productVariants.id, id), eq(productVariants.productId, productId)));
      keptIds.add(id);
    } else {
      await tx.insert(productVariants).values({ ...data, productId });
    }
  }

  const removedIds: number[] = [...existingIds].filter((id) => !keptIds.has(id));
  if (removedIds.length) {
    await tx
      .delete(productVariants)
      .where(and(inArray(productVariants.id, removedIds), eq(productVariants.productId, productId)));
  }
};

const insertChildren = async (
  tx: any,
  productId: number,
  data: Pick<CreateProductInput, "variants" | "specs" | "relations"> & Partial<CreateProductInput>
) => {
  const variants = (data.variants || []).filter(Boolean);
  if (variants.length) {
    await tx.insert(productVariants).values(
      variants.map((v: ProductVariantInput) => ({
        productId,
        name: v.name,
        options: v.options || {},
        price: toStr(v.price),
        discountPrice: toStr(v.discountPrice ?? v.salePrice),
        sku: toStr(v.sku),
        barcode: toStr(v.barcode),
        stock: toNum(v.stock) ?? 0,
        weight: toStr(v.weight),
        dimensions: toStr(v.dimensions),
        images: v.images || [],
        thumbnail: v.thumbnail || null,
        status: v.status || "active",
        shippingCost: toStr(v.shippingCost),
        warranty: toStr(v.warranty),
        availability: v.availability === undefined ? true : v.availability,
      }))
    );
  }

  const specs = (data.specs || []).filter((s) => s.label && s.value);
  if (specs.length) {
    await tx.insert(productSpecs).values(
      specs.map((s: ProductSpecInput, index) => ({
        productId,
        label: s.label,
        value: s.value,
        sortOrder: s.sortOrder ?? index,
      }))
    );
  }

  const relations = (data.relations || []).filter((r) => r.relatedProductId);
  if (relations.length) {
    await tx.insert(productRelations).values(
      relations.map((r: ProductRelationInput) => ({
        productId,
        relatedProductId: Number(r.relatedProductId),
        type: r.type,
      }))
    );
  }
};

export const create = async (data: CreateProductInput) => {
  await validateProductRelations(data);
  const variantStock = (data.variants || []).reduce((sum, v) => sum + (toNum(v.stock) ?? 0), 0);
  const stock = data.stock ?? (data.variants && data.variants.length ? variantStock : 0);

  const insertData: any = {
    ...data,
    stock,
    profitMargin: deriveProfitMargin(data.salePrice ?? data.price, data.costPrice),
    stockStatus: deriveStockStatus(stock, toNum(data.lowStockAlert), data.unlimitedStock ?? false, data.stockStatus || null),
    status: deriveStatusFromProductStatus(data.productStatus, data.status),
  };
  delete insertData.variants;
  delete insertData.specs;
  delete insertData.relations;

  const productId = await db.transaction(async (tx) => {
    const result = await tx.insert(products).values(insertData);
    const id = result[0].insertId;
    await insertChildren(tx, id, data);
    return id;
  });

  return getById(productId);
};

export const update = async (id: number, data: UpdateProductInput) => {
  await validateProductRelations(data);
  const updateData: any = { ...data };
  delete updateData.variants;
  delete updateData.specs;
  delete updateData.relations;

  const existing = await db
    .select({
      stock: products.stock,
      lowStockAlert: products.lowStockAlert,
      unlimitedStock: products.unlimitedStock,
      price: products.price,
      salePrice: products.salePrice,
      costPrice: products.costPrice,
    })
    .from(products)
    .where(eq(products.id, id))
    .limit(1);
  const current = existing[0];

  const stock = data.stock ?? current?.stock ?? 0;
  if (data.stock !== undefined || data.variants !== undefined) {
    const variantStock = (data.variants || []).reduce((sum, v) => sum + (toNum(v.stock) ?? 0), 0);
    if (data.variants && data.variants.length) updateData.stock = variantStock;
    else updateData.stock = data.stock ?? stock;
  }

  if (data.salePrice !== undefined || data.price !== undefined || data.costPrice !== undefined) {
    updateData.profitMargin = deriveProfitMargin(
      data.salePrice ?? data.price ?? current?.salePrice ?? current?.price,
      data.costPrice ?? current?.costPrice
    );
  }
  if (data.stockStatus !== undefined || data.stock !== undefined || data.variants !== undefined) {
    updateData.stockStatus = deriveStockStatus(
      updateData.stock ?? stock,
      toNum(data.lowStockAlert ?? current?.lowStockAlert),
      data.unlimitedStock ?? current?.unlimitedStock ?? false,
      data.stockStatus || null
    );
  }
  if (data.productStatus !== undefined || data.status !== undefined) {
    updateData.status = deriveStatusFromProductStatus(data.productStatus, data.status);
  }
  if (data.variants !== undefined || data.specs !== undefined || data.relations !== undefined) {
    await db.transaction(async (tx) => {
      await tx.update(products).set(updateData).where(eq(products.id, id));
      if (data.variants !== undefined) {
        await syncVariants(tx, id, (data.variants || []).filter(Boolean) as ProductVariantInput[]);
      }
      if (data.specs !== undefined) {
        await tx.delete(productSpecs).where(eq(productSpecs.productId, id));
      }
      if (data.relations !== undefined) {
        await tx.delete(productRelations).where(eq(productRelations.productId, id));
      }
      if (data.specs !== undefined || data.relations !== undefined) {
        await insertChildren(tx, id, { ...data, variants: [], specs: data.specs || [], relations: data.relations || [] });
      }
    });
  } else {
    await db.update(products).set(updateData).where(eq(products.id, id));
  }

  return getById(id);
};

export const remove = async (id: number) => {
  await db.transaction(async (tx) => {
    await tx.delete(productVariants).where(eq(productVariants.productId, id));
    await tx.delete(productSpecs).where(eq(productSpecs.productId, id));
    await tx.delete(productRelations).where(eq(productRelations.productId, id));
    await tx.delete(products).where(eq(products.id, id));
  });
  return { success: true };
};

export const bulkAction = async (
  ids: number[],
  action: "delete" | "publish" | "archive" | "hide" | "draft" | "feature" | "unfeature"
) => {
  if (action === "delete") {
    await db.transaction(async (tx) => {
      await tx.delete(productVariants).where(inArray(productVariants.productId, ids));
      await tx.delete(productSpecs).where(inArray(productSpecs.productId, ids));
      await tx.delete(productRelations).where(inArray(productRelations.productId, ids));
      await tx.delete(products).where(inArray(products.id, ids));
    });
    return { affected: ids.length };
  }

  if (action === "feature" || action === "unfeature") {
    const result = await db
      .update(products)
      .set({ isFeatured: action === "feature" })
      .where(inArray(products.id, ids));
    return { affected: Number(result[0].affectedRows) };
  }

  const map: Record<string, { productStatus: "published" | "archived" | "hidden" | "draft"; status: "active" | "inactive" }> = {
    publish: { productStatus: "published", status: "active" },
    archive: { productStatus: "archived", status: "inactive" },
    hide: { productStatus: "hidden", status: "inactive" },
    draft: { productStatus: "draft", status: "inactive" },
  };
  const target = map[action];
  if (!target) throw new Error(`Unknown bulk action: ${action}`);
  const result = await db.update(products).set({ productStatus: target.productStatus, status: target.status }).where(inArray(products.id, ids));
  return { affected: Number(result[0].affectedRows) };
};

export const duplicate = async (id: number) => {
  const existing = await getById(id);
  if (!existing) throw new Error("Product not found");

  const slug = `${existing.slug}-copy-${Date.now().toString(36)}`;
  const title = `${existing.title} (Copy)`;

  const insertData: any = { ...existing };
  delete insertData.id;
  delete insertData.createdAt;
  delete insertData.category;
  delete insertData.subCategory;
  delete insertData.childCategory;
  delete insertData.collection;
  delete insertData.vendor;
  delete insertData.supplierInfo;
  delete insertData.brandInfo;
  insertData.title = title;
  insertData.slug = slug;
  insertData.sku = existing.sku ? `${existing.sku}-C` : undefined;
  insertData.images = existing.images || [];
  insertData.variants = (existing.variants || []).map((v) => ({ ...v }));
  insertData.specs = (existing.specs || []).map((s) => ({ ...s }));
  insertData.relations = (existing.relations || []).filter((r) => r.relatedProduct).map((r) => ({ relatedProductId: (r as any).relatedProduct.id, type: r.type }));

  return create(insertData);
};

export const autoSaveDraft = async (id: number, draft: Record<string, unknown>) => {
  await db.update(products).set({ draft }).where(eq(products.id, id));
  return { success: true };
};

const CSV_COLUMNS = [
  "title",
  "price",
  "salePrice",
  "discount",
  "costPrice",
  "sku",
  "barcode",
  "brand",
  "category",
  "stock",
  "productStatus",
  "status",
  "shortDescription",
  "description",
  "tags",
  "features",
];

const csvEscape = (value: unknown): string => {
  const s = value === null || value === undefined ? "" : String(value);
  if (/[",\n]/.test(s)) return `"${s.replace(/"/g, '""')}"`;
  return s;
};

export const exportCsv = async (query: ProductQuery) => {
  const where = await buildWhere({ ...query, limit: undefined });
  const rows = await db
    .select({
      ...productColumns,
      categoryName: categories.name,
    })
    .from(products)
    .leftJoin(categories, eq(products.categoryId, categories.id))
    .leftJoin(sql`categories AS sc`, eq(products.subCategoryId, sql`sc.id`))
    .leftJoin(sql`categories AS cc`, eq(products.childCategoryId, sql`cc.id`))
    .leftJoin(collections, eq(products.collectionId, collections.id))
    .leftJoin(vendors, eq(products.vendorId, vendors.id))
    .leftJoin(suppliers, eq(products.supplierId, suppliers.id))
    .leftJoin(brands, eq(products.brandId, brands.id))
    .where(where)
    .orderBy(desc(products.createdAt));

  const header = CSV_COLUMNS.join(",");
  const lines = rows.map((row: any) =>
    CSV_COLUMNS.map((col) => {
      if (col === "category") return csvEscape(row.categoryName);
      if (col === "tags" || col === "features") return csvEscape(JSON.stringify(row[col] || []));
      return csvEscape(row[col]);
    }).join(",")
  );
  return [header, ...lines].join("\n");
};

export const importCsv = async (csv: string) => {
  const lines = csv.split(/\r?\n/).filter((l) => l.trim().length > 0);
  if (lines.length < 2) return { imported: 0 };

  const headers = lines[0].split(",").map((h) => h.trim().replace(/^"|"$/g, ""));
  const parseLine = (line: string): string[] => {
    const values: string[] = [];
    let current = "";
    let inQuotes = false;
    for (let i = 0; i < line.length; i++) {
      const ch = line[i];
      if (inQuotes) {
        if (ch === '"' && line[i + 1] === '"') {
          current += '"';
          i++;
        } else if (ch === '"') {
          inQuotes = false;
        } else {
          current += ch;
        }
      } else if (ch === '"') {
        inQuotes = true;
      } else if (ch === ",") {
        values.push(current);
        current = "";
      } else {
        current += ch;
      }
    }
    values.push(current);
    return values;
  };

  let imported = 0;
  for (const line of lines.slice(1)) {
    const values = parseLine(line);
    const record: Record<string, string> = {};
    headers.forEach((h, i) => (record[h] = values[i]?.trim() ?? ""));

    if (!record.title || !record.price) continue;

    const slug = `${String(record.title).toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "")}-${Date.now().toString(36)}`;
    const tags = record.tags ? JSON.parse(record.tags || "[]") : undefined;
    const features = record.features ? JSON.parse(record.features || "[]") : undefined;

    await db.insert(products).values({
      title: record.title,
      slug,
      price: record.price || "0",
      salePrice: record.salePrice || null,
      discount: record.discount || "0",
      costPrice: record.costPrice || "0",
      sku: record.sku || null,
      barcode: record.barcode || null,
      brand: record.brand || null,
      stock: Number(record.stock) || 0,
      productStatus: (record.productStatus as any) || "published",
      status: record.productStatus === "published" || !record.productStatus ? "active" : "inactive",
      shortDescription: record.shortDescription || null,
      description: record.description || null,
      tags,
      features,
      profitMargin: "0",
      stockStatus: deriveStockStatus(Number(record.stock) || 0, 10, false, null),
    });
    imported++;
  }

  return { imported };
};
