import { Request, Response } from "express";
import * as categoryService from "./category.service";
import slugify from "slugify";
import { AppError } from "../../utils/AppError";
import { uploadBuffer } from "../../utils/cloud";

const toBool = (value: unknown): boolean | undefined => {
  if (value === undefined || value === null || value === "") return undefined;
  return value === true || value === "true" || value === 1 || value === "1";
};

const toNumber = (value: unknown): number | undefined => {
  if (value === undefined || value === null || value === "") return undefined;
  const n = Number(value);
  return Number.isNaN(n) ? undefined : n;
};

const toIdOrNull = (value: unknown): number | null | undefined => {
  if (value === undefined) return undefined;
  if (value === null || value === "" || value === "none") return null;
  const n = Number(value);
  return Number.isNaN(n) ? null : n;
};

const coerce = (body: Record<string, unknown>) => ({
  parentId: toIdOrNull(body.parentId),
  featured: toBool(body.featured),
  homepageVisibility: toBool(body.homepageVisibility),
  sortOrder: toNumber(body.sortOrder),
  status: body.status as never,
});

const persistImage = async (file: Express.Multer.File | undefined, fallback: string | undefined) => {
  if (!file) return fallback;
  const uploaded = await uploadBuffer(file.buffer, {
    folder: "categories",
    filename: file.originalname,
    mimeType: file.mimetype,
  });
  return uploaded.url;
};

export const getAll = async (_req: Request, res: Response) => {
  const data = await categoryService.getAllFlat();
  res.json({ success: true, data });
};

export const listAdmin = async (req: Request, res: Response) => {
  const q = req.query as Record<string, string>;
  const result = await categoryService.getAll({
    page: q.page ? Number(q.page) : 1,
    limit: q.limit ? Number(q.limit) : 20,
    search: q.search,
    status: q.status,
    parentId: q.parentId,
    featured: toBool(q.featured),
    sort: q.sort,
  });
  res.json({ success: true, ...result });
};

export const getTree = async (_req: Request, res: Response) => {
  const data = await categoryService.getTree();
  res.json({ success: true, data });
};

export const getById = async (req: Request, res: Response) => {
  const data = await categoryService.getById(Number(req.params.id));
  if (!data) throw new AppError(404, "Category not found");
  res.json({ success: true, data });
};

export const getBySlug = async (req: Request, res: Response) => {
  const data = await categoryService.getBySlug(req.params.slug);
  if (!data) throw new AppError(404, "Category not found");
  res.json({ success: true, data });
};

export const getUsage = async (req: Request, res: Response) => {
  const data = await categoryService.getUsage(Number(req.params.id));
  res.json({ success: true, data });
};

export const create = async (req: Request, res: Response) => {
  const body = req.body as Record<string, unknown>;
  if (!body.name || !String(body.name).trim()) throw new AppError(400, "Name is required");
  const slug = body.slug ? String(body.slug) : slugify(String(body.name), { lower: true, strict: true });
  const image = await persistImage(req.file, body.image ? String(body.image) : undefined);

  const data = await categoryService.create({
    name: String(body.name).trim(),
    slug,
    parentId: toIdOrNull(body.parentId),
    image,
    icon: body.icon ? String(body.icon) : undefined,
    banner: body.banner ? String(body.banner) : undefined,
    thumbnail: body.thumbnail ? String(body.thumbnail) : undefined,
    description: body.description ? String(body.description) : undefined,
    featured: toBool(body.featured),
    homepageVisibility: toBool(body.homepageVisibility),
    sortOrder: toNumber(body.sortOrder),
    seoTitle: body.seoTitle ? String(body.seoTitle) : undefined,
    seoDescription: body.seoDescription ? String(body.seoDescription) : undefined,
    seoKeywords: body.seoKeywords ? String(body.seoKeywords) : undefined,
    status: (body.status as never) || "active",
  });
  res.status(201).json({ success: true, data });
};

export const update = async (req: Request, res: Response) => {
  const id = Number(req.params.id);
  const body = req.body as Record<string, unknown>;
  const existing = await categoryService.getById(id);
  if (!existing) throw new AppError(404, "Category not found");

  const updateData: Record<string, unknown> = { ...coerce(body) };
  if (body.name) {
    updateData.name = String(body.name).trim();
    if (!body.slug) updateData.slug = slugify(String(body.name), { lower: true, strict: true });
  }
  if (body.slug) updateData.slug = String(body.slug);
  if (body.description !== undefined) updateData.description = body.description ? String(body.description) : null;
  if (body.icon !== undefined) updateData.icon = body.icon ? String(body.icon) : null;
  if (body.banner !== undefined) updateData.banner = body.banner ? String(body.banner) : null;
  if (body.thumbnail !== undefined) updateData.thumbnail = body.thumbnail ? String(body.thumbnail) : null;
  if (body.seoTitle !== undefined) updateData.seoTitle = body.seoTitle ? String(body.seoTitle) : null;
  if (body.seoDescription !== undefined) updateData.seoDescription = body.seoDescription ? String(body.seoDescription) : null;
  if (body.seoKeywords !== undefined) updateData.seoKeywords = body.seoKeywords ? String(body.seoKeywords) : null;

  const uploadedImage = await persistImage(req.file, undefined);
  if (uploadedImage) updateData.image = uploadedImage;
  else if (body.image !== undefined) updateData.image = body.image ? String(body.image) : null;

  const data = await categoryService.update(id, updateData);
  res.json({ success: true, data });
};

export const remove = async (req: Request, res: Response) => {
  const id = Number(req.params.id);
  const existing = await categoryService.getById(id);
  if (!existing) throw new AppError(404, "Category not found");

  const usage = await categoryService.getUsage(id);
  if (usage.subCategories > 0) {
    throw new AppError(
      409,
      `This category has ${usage.subCategories} sub-categories. Move or delete them first.`,
      { usageCount: usage.products, subCategories: usage.subCategories, code: "has_children" }
    );
  }
  if (usage.products > 0) {
    throw new AppError(
      409,
      `This category is currently used by ${usage.products} product${usage.products > 1 ? "s" : ""}.`,
      { usageCount: usage.products, subCategories: 0, code: "in_use" }
    );
  }

  await categoryService.remove(id);
  res.json({ success: true, message: "Category deleted" });
};

export const moveProducts = async (req: Request, res: Response) => {
  const id = Number(req.params.id);
  const targetId = toIdOrNull((req.body as Record<string, unknown>).targetId) ?? null;
  const result = await categoryService.moveProducts(id, targetId);
  res.json({ success: true, data: result });
};
