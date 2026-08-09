import { Request, Response } from "express";
import * as productService from "./product.service";
import slugify from "slugify";
import { AppError } from "../../utils/AppError";
import { uploadBuffer, cloudinaryConfigured } from "../../utils/cloud";
import { ProductRelationType, ProductVariantInput, ProductSpecInput } from "./product.interface";

const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 12;
const DEFAULT_DISCOUNT = "0";
const DEFAULT_STOCK = 0;
const DEFAULT_STATUS = "active";
const DEFAULT_PAYMENT_METHODS = ["cod"];

const parseJsonField = <T>(value: unknown): T | undefined => {
  if (value === undefined || value === null || value === "") return undefined;
  if (typeof value === "string") return JSON.parse(value) as T;
  return value as T;
};

const parseArrayField = <T>(value: unknown): T[] => {
  const parsed = parseJsonField<T[]>(value);
  return Array.isArray(parsed) ? parsed : [];
};

const toBool = (value: unknown): boolean | undefined => {
  if (value === undefined || value === null) return undefined;
  return value === true || value === "true" || value === 1 || value === "1";
};

const toStr = (value: unknown): string | undefined =>
  value === undefined || value === null || value === "" ? undefined : String(value);

const toNum = (value: unknown): number | undefined => {
  if (value === undefined || value === null || value === "") return undefined;
  const n = Number(value);
  return Number.isNaN(n) ? undefined : n;
};

const assertCloudinary = (files: Express.Multer.File[]) => {
  if (files && files.length > 0 && !cloudinaryConfigured) {
    throw new AppError(
      400,
      "Cloudinary is not configured. Set CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY and CLOUDINARY_API_SECRET in backend/.env to upload images."
    );
  }
};

const persistUploadedImages = async (files: Express.Multer.File[]): Promise<string[]> => {
  if (!files || files.length === 0) return [];
  const urls: string[] = [];
  for (const f of files) {
    const uploaded = await uploadBuffer(f.buffer, {
      folder: "products",
      filename: f.originalname,
      mimeType: f.mimetype,
    });
    urls.push(uploaded.url);
  }
  return urls;
};

const extractBaseProduct = (body: any) => {
  const labels = (key: string) => (body[key] !== undefined ? toBool(body[key]) : undefined);

  return {
    title: body.title,
    description: body.description,
    shortDescription: body.shortDescription,
    price: body.price !== undefined ? String(body.price) : undefined,
    salePrice: toStr(body.salePrice),
    discount: body.discount !== undefined && body.discount !== "" ? String(body.discount) : undefined,
    costPrice: toStr(body.costPrice),
    tax: toStr(body.tax),
    vat: toStr(body.vat),
    shippingCharge: toStr(body.shippingCharge),
    codFee: toStr(body.codFee),
    flashSalePrice: toStr(body.flashSalePrice),
    wholesalePrice: toStr(body.wholesalePrice),
    dealerPrice: toStr(body.dealerPrice),
    categoryId: body.categoryId !== undefined && body.categoryId !== "" ? Number(body.categoryId) : undefined,
    subCategoryId: body.subCategoryId !== undefined && body.subCategoryId !== "" ? Number(body.subCategoryId) : undefined,
    childCategoryId: body.childCategoryId !== undefined && body.childCategoryId !== "" ? Number(body.childCategoryId) : undefined,
    collectionId: body.collectionId !== undefined && body.collectionId !== "" ? Number(body.collectionId) : undefined,
    brandId: body.brandId !== undefined && body.brandId !== "" ? Number(body.brandId) : undefined,
    brand: toStr(body.brand),
    vendorId: body.vendorId !== undefined && body.vendorId !== "" ? Number(body.vendorId) : undefined,
    supplierId: body.supplierId !== undefined && body.supplierId !== "" ? Number(body.supplierId) : undefined,
    supplier: toStr(body.supplier),
    countryOfOrigin: toStr(body.countryOfOrigin),
    sku: toStr(body.sku),
    barcode: toStr(body.barcode),
    warranty: toStr(body.warranty),
    weight: toStr(body.weight),
    dimensions: toStr(body.dimensions),
    returnPolicy: toStr(body.returnPolicy),
    warehouse: toStr(body.warehouse),
    videoUrl: toStr(body.videoUrl),
    seoTitle: toStr(body.seoTitle),
    seoDescription: toStr(body.seoDescription),
    seoKeywords: toStr(body.seoKeywords),
    canonicalUrl: toStr(body.canonicalUrl),
    ogImage: toStr(body.ogImage),
    twitterImage: toStr(body.twitterImage),
    structuredData: parseJsonField<Record<string, unknown>>(body.structuredData),
    emiAvailable: labels("emiAvailable"),
    isFeatured: labels("isFeatured"),
    isTrending: labels("isTrending"),
    isFlashSale: labels("isFlashSale"),
    isNewArrival: labels("isNewArrival"),
    isBestSeller: labels("isBestSeller"),
    isLimitedEdition: labels("isLimitedEdition"),
    isOfficial: labels("isOfficial"),
    isHotDeal: labels("isHotDeal"),
    isArchived: labels("isArchived"),
    lowStockAlert: toNum(body.lowStockAlert),
    minOrder: toNum(body.minOrder),
    maxOrder: toNum(body.maxOrder),
    unlimitedStock: labels("unlimitedStock"),
    backorder: labels("backorder"),
    trackInventory: labels("trackInventory"),
    stockStatus: toStr(body.stockStatus),
    productStatus: body.productStatus || undefined,
    stock: toNum(body.stock),
    tags: parseArrayField<string>(body.tags),
    features: parseArrayField<string>(body.features),
    sizeOptions: parseArrayField<string>(body.sizeOptions),
    colorOptions: parseArrayField<{ name: string; value?: string; image?: string }>(body.colorOptions),
    paymentMethods: body.paymentMethods ? (parseArrayField<string>(body.paymentMethods) as ["cod", "online"]) : DEFAULT_PAYMENT_METHODS,
    paymentPhoneNumber: toStr(body.paymentPhoneNumber),
    variants: parseArrayField<ProductVariantInput>(body.variants),
    specs: parseArrayField<ProductSpecInput>(body.specs),
    relations: parseArrayField<{ relatedProductId: string | number; type: ProductRelationType }>(body.relations).map((r) => ({
      relatedProductId: Number(r.relatedProductId),
      type: r.type,
    })),
  };
};

export const getAll = async (req: Request, res: Response) => {
  const result = await productService.getAll({
    page: Number(req.query.page) || DEFAULT_PAGE,
    limit: Number(req.query.limit) || DEFAULT_LIMIT,
    category: req.query.category as string,
    search: req.query.search as string,
    brand: req.query.brand as string,
    supplier: req.query.supplier as string,
    vendor: req.query.vendor as string,
    collection: req.query.collection as string,
    stock: req.query.stock as "in_stock" | "low_stock" | "out_of_stock",
    minPrice: req.query.minPrice ? Number(req.query.minPrice) : undefined,
    maxPrice: req.query.maxPrice ? Number(req.query.maxPrice) : undefined,
    dateFrom: req.query.dateFrom as string,
    dateTo: req.query.dateTo as string,
    sort: req.query.sort as string,
    status: req.query.status as string,
    productStatus: req.query.productStatus as string,
    label: req.query.label as string,
    tags: req.query.tags as string,
    sku: req.query.sku as string,
    barcode: req.query.barcode as string,
    inStock: req.query.inStock === "true",
  });
  res.json({ success: true, ...result });
};

export const getById = async (req: Request, res: Response) => {
  const data = await productService.getById(Number(req.params.id));
  if (!data) throw new AppError(404, "Product not found");
  res.json({ success: true, data });
};

export const getBySlug = async (req: Request, res: Response) => {
  const data = await productService.getBySlug(req.params.slug);
  if (!data) throw new AppError(404, "Product not found");
  res.json({ success: true, data });
};

export const getRelated = async (req: Request, res: Response) => {
  const product = await productService.getById(Number(req.params.id));
  if (!product) throw new AppError(404, "Product not found");
  const data = await productService.getRelated(product.categoryId || 0, product.id);
  res.json({ success: true, data });
};

export const create = async (req: Request, res: Response) => {
  const body = req.body || {};
  const base = extractBaseProduct(body);
  const files = req.files as Express.Multer.File[];

  assertCloudinary(files);
  const uploadedImages = await persistUploadedImages(files);
  const bodyImages = Array.isArray(body.images) ? body.images : parseArrayField<string>(body.images);
  const images = [...(bodyImages || []), ...uploadedImages];

  const slug = slugify(base.title, { lower: true, strict: true });

  const data = await productService.create({
    ...base,
    title: base.title,
    slug,
    price: base.price || "0",
    discount: base.discount || DEFAULT_DISCOUNT,
    stock: base.stock ?? (base.variants?.length ? 0 : DEFAULT_STOCK),
    images,
    paymentMethods: (base.paymentMethods || DEFAULT_PAYMENT_METHODS) as ["cod", "online"],
  });
  res.status(201).json({ success: true, data });
};

export const update = async (req: Request, res: Response) => {
  const id = Number(req.params.id);
  const body = req.body || {};
  const base = extractBaseProduct(body);
  const files = req.files as Express.Multer.File[];

  assertCloudinary(files);
  const uploadedImages = await persistUploadedImages(files);

  const updateData: any = { ...base };
  if (base.title) {
    updateData.title = base.title;
    updateData.slug = slugify(base.title, { lower: true, strict: true });
  }

  if (body.images !== undefined || uploadedImages.length > 0) {
    const bodyImages = Array.isArray(body.images) ? body.images : parseArrayField<string>(body.images);
    const existing = parseArrayField<string>(body.existingImages);
    const combined = existing.length ? [...existing, ...(bodyImages || [])] : bodyImages || [];
    updateData.images = [...combined, ...uploadedImages];
  }

  const data = await productService.update(id, updateData);
  res.json({ success: true, data });
};

export const remove = async (req: Request, res: Response) => {
  await productService.remove(Number(req.params.id));
  res.json({ success: true, message: "Product deleted" });
};

export const bulk = async (req: Request, res: Response) => {
  const { ids, action } = req.body;
  const result = await productService.bulkAction(ids, action);
  res.json({ success: true, message: `Bulk ${action} complete`, ...result });
};

export const duplicate = async (req: Request, res: Response) => {
  const data = await productService.duplicate(Number(req.params.id));
  res.status(201).json({ success: true, data });
};

export const exportCsv = async (req: Request, res: Response) => {
  const csv = await productService.exportCsv({
    category: req.query.category as string,
    search: req.query.search as string,
    brand: req.query.brand as string,
    status: (req.query.status as string) || undefined,
    productStatus: req.query.productStatus as string,
    label: req.query.label as string,
  });
  res.json({ success: true, csv });
};

export const importCsv = async (req: Request, res: Response) => {
  const { csv } = req.body || {};
  if (!csv || typeof csv !== "string") throw new AppError(400, "CSV payload is required");
  const result = await productService.importCsv(csv);
  res.json({ success: true, ...result });
};

export const saveDraft = async (req: Request, res: Response) => {
  const id = Number(req.params.id);
  await productService.autoSaveDraft(id, req.body.draft || {});
  res.json({ success: true, message: "Draft saved" });
};
