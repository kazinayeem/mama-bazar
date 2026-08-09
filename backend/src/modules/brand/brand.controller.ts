import { Request, Response } from "express";
import * as brandService from "./brand.service";
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

const persistLogo = async (req: Request): Promise<string | undefined> => {
  if (!req.file) return undefined;
  const uploaded = await uploadBuffer(req.file.buffer, {
    folder: "brands",
    filename: req.file.originalname,
    mimeType: req.file.mimetype,
  });
  return uploaded.url;
};

export const getAll = async (_req: Request, res: Response) => {
  const data = await brandService.getAllActive();
  res.json({ success: true, data });
};

export const listAdmin = async (req: Request, res: Response) => {
  const q = req.query as Record<string, string>;
  const result = await brandService.getAll({
    page: q.page ? Number(q.page) : 1,
    limit: q.limit ? Number(q.limit) : 20,
    search: q.search,
    status: q.status,
    featured: toBool(q.featured),
    sort: q.sort,
  });
  res.json({ success: true, ...result });
};

export const getById = async (req: Request, res: Response) => {
  const data = await brandService.getById(Number(req.params.id));
  if (!data) throw new AppError(404, "Brand not found");
  res.json({ success: true, data });
};

export const getBySlug = async (req: Request, res: Response) => {
  const data = await brandService.getBySlug(req.params.slug);
  if (!data) throw new AppError(404, "Brand not found");
  res.json({ success: true, data });
};

export const getUsage = async (req: Request, res: Response) => {
  const data = await brandService.getUsage(Number(req.params.id));
  res.json({ success: true, data: { products: data } });
};

export const create = async (req: Request, res: Response) => {
  const body = req.body as Record<string, unknown>;
  if (!body.name || !String(body.name).trim()) throw new AppError(400, "Name is required");
  const slug = body.slug ? String(body.slug) : slugify(String(body.name), { lower: true, strict: true });
  const logo = await persistLogo(req);

  const data = await brandService.create({
    name: String(body.name).trim(),
    slug,
    logo,
    bannerImage: body.bannerImage ? String(body.bannerImage) : undefined,
    description: body.description ? String(body.description) : undefined,
    website: body.website ? String(body.website) : undefined,
    countryOfOrigin: body.countryOfOrigin ? String(body.countryOfOrigin) : undefined,
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
  const existing = await brandService.getById(id);
  if (!existing) throw new AppError(404, "Brand not found");

  const updateData: Record<string, unknown> = {
    featured: toBool(body.featured),
    homepageVisibility: toBool(body.homepageVisibility),
    sortOrder: toNumber(body.sortOrder),
    status: body.status as never,
  };
  if (body.name) {
    updateData.name = String(body.name).trim();
    if (!body.slug) updateData.slug = slugify(String(body.name), { lower: true, strict: true });
  }
  if (body.slug) updateData.slug = String(body.slug);
  if (body.bannerImage !== undefined) updateData.bannerImage = body.bannerImage ? String(body.bannerImage) : null;
  if (body.description !== undefined) updateData.description = body.description ? String(body.description) : null;
  if (body.website !== undefined) updateData.website = body.website ? String(body.website) : null;
  if (body.countryOfOrigin !== undefined) updateData.countryOfOrigin = body.countryOfOrigin ? String(body.countryOfOrigin) : null;
  if (body.seoTitle !== undefined) updateData.seoTitle = body.seoTitle ? String(body.seoTitle) : null;
  if (body.seoDescription !== undefined) updateData.seoDescription = body.seoDescription ? String(body.seoDescription) : null;
  if (body.seoKeywords !== undefined) updateData.seoKeywords = body.seoKeywords ? String(body.seoKeywords) : null;

  const uploadedLogo = await persistLogo(req);
  if (uploadedLogo) updateData.logo = uploadedLogo;
  else if (body.logo !== undefined) updateData.logo = body.logo ? String(body.logo) : null;

  const data = await brandService.update(id, updateData);
  res.json({ success: true, data });
};

export const remove = async (req: Request, res: Response) => {
  const id = Number(req.params.id);
  const existing = await brandService.getById(id);
  if (!existing) throw new AppError(404, "Brand not found");

  const usage = await brandService.getUsage(id);
  if (usage > 0) {
    throw new AppError(
      409,
      `This brand is currently used by ${usage} product${usage > 1 ? "s" : ""}.`,
      { usageCount: usage, code: "in_use" }
    );
  }

  await brandService.remove(id);
  res.json({ success: true, message: "Brand deleted" });
};

export const moveProducts = async (req: Request, res: Response) => {
  const id = Number(req.params.id);
  const targetId = toIdOrNull((req.body as Record<string, unknown>).targetId) ?? null;
  const result = await brandService.moveProducts(id, targetId);
  res.json({ success: true, data: result });
};
