import { Request, Response } from "express";
import { catalogService, CatalogTableName } from "./catalog.service";
import { AppError } from "../../utils/AppError";
import { uploadBuffer } from "../../utils/cloud";
import slugify from "slugify";

const IMAGE_FIELDS: Partial<Record<CatalogTableName, string>> = {
  collections: "image",
  vendors: "logo",
  suppliers: "logo",
};

const IMAGE_FOLDERS: Partial<Record<CatalogTableName, string>> = {
  collections: "collections",
  vendors: "vendors",
  suppliers: "suppliers",
};

const makeSlug = (value: string) => slugify(value, { lower: true, strict: true });

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

const persistUploadedFile = async (file: Express.Multer.File | undefined, folder: string): Promise<string | undefined> => {
  if (!file) return undefined;
  const uploaded = await uploadBuffer(file.buffer, {
    folder,
    filename: file.originalname,
    mimeType: file.mimetype,
  });
  return uploaded.url;
};

const makeController = (name: CatalogTableName) => {
  const list = async (_req: Request, res: Response) => {
    const data = await catalogService.list(name);
    res.json({ success: true, data });
  };

  const listAdmin = async (req: Request, res: Response) => {
    const q = req.query as Record<string, string>;
    const result = await catalogService.listAdmin(name, {
      page: q.page ? Number(q.page) : 1,
      limit: q.limit ? Number(q.limit) : 20,
      search: q.search,
      status: q.status,
      sort: q.sort,
    });
    res.json({ success: true, ...result });
  };

  const getById = async (req: Request, res: Response) => {
    const data = await catalogService.getById(name, Number(req.params.id));
    if (!data) throw new AppError(404, "Not found");
    res.json({ success: true, data });
  };

  const getUsage = async (req: Request, res: Response) => {
    const id = Number(req.params.id);
    const existing = await catalogService.getById(name, id);
    if (!existing) throw new AppError(404, "Not found");
    const products = await catalogService.getUsage(name, id, (existing as { name?: string }).name);
    res.json({ success: true, data: { products } });
  };

  const create = async (req: Request, res: Response) => {
    const body = req.body || {};
    const data: Record<string, unknown> = { ...body };

    if (body.name) {
      data.slug = body.slug || makeSlug(String(body.name));
    }

    // coerce non-string fields
    if ("featured" in data) data.featured = toBool(data.featured);
    if ("homepageVisibility" in data) data.homepageVisibility = toBool(data.homepageVisibility);
    if ("sortOrder" in data) data.sortOrder = toNumber(data.sortOrder) ?? 0;
    if ("status" in data && !data.status) delete data.status;
    if (data.startDate === "" || data.startDate === null) data.startDate = null;
    if (data.endDate === "" || data.endDate === null) data.endDate = null;

    const imageField = IMAGE_FIELDS[name];
    if (imageField) {
      const uploaded = await persistUploadedFile(req.file, IMAGE_FOLDERS[name] || name);
      if (uploaded) data[imageField] = uploaded;
    }

    const created = await catalogService.create(name, data);
    res.status(201).json({ success: true, data: created });
  };

  const update = async (req: Request, res: Response) => {
    const id = Number(req.params.id);
    const body = req.body || {};
    const existing = await catalogService.getById(name, id);
    if (!existing) throw new AppError(404, "Not found");

    const data: Record<string, unknown> = { ...body };
    if ("featured" in data) data.featured = toBool(data.featured);
    if ("homepageVisibility" in data) data.homepageVisibility = toBool(data.homepageVisibility);
    if ("sortOrder" in data) data.sortOrder = toNumber(data.sortOrder) ?? 0;
    if ("status" in data && !data.status) delete data.status;
    if ("startDate" in data) data.startDate = data.startDate ? String(data.startDate) : null;
    if ("endDate" in data) data.endDate = data.endDate ? String(data.endDate) : null;
    if (data.slug === "") delete data.slug;
    if (data.name && !body.slug) {
      data.slug = makeSlug(String(data.name));
    }

    const imageField = IMAGE_FIELDS[name];
    if (imageField) {
      const uploaded = await persistUploadedFile(req.file, IMAGE_FOLDERS[name] || name);
      if (uploaded) data[imageField] = uploaded;
    }

    const updated = await catalogService.update(name, id, data);
    res.json({ success: true, data: updated });
  };

  const remove = async (req: Request, res: Response) => {
    const id = Number(req.params.id);
    const existing = await catalogService.getById(name, id);
    if (!existing) throw new AppError(404, "Not found");

    const usage = await catalogService.getUsage(name, id, (existing as { name?: string }).name);
    if (usage > 0) {
      throw new AppError(
        409,
        `This ${name === "colors" || name === "sizes" ? name.slice(0, -1) : name.slice(0, -1)} is currently used by ${usage} product${usage > 1 ? "s" : ""}.`,
        { usageCount: usage, code: "in_use" }
      );
    }

    await catalogService.remove(name, id);
    res.json({ success: true, message: "Deleted" });
  };

  const moveProducts = async (req: Request, res: Response) => {
    const id = Number(req.params.id);
    const existing = await catalogService.getById(name, id);
    if (!existing) throw new AppError(404, "Not found");
    const targetId = toIdOrNull((req.body as Record<string, unknown>).targetId) ?? null;
    const result = await catalogService.move(name, id, targetId, (existing as { name?: string }).name);
    res.json({ success: true, data: result });
  };

  return { list, listAdmin, getById, getUsage, create, update, remove, moveProducts };
};

export const colorController = makeController("colors");
export const sizeController = makeController("sizes");
export const collectionController = makeController("collections");
export const vendorController = makeController("vendors");
export const supplierController = makeController("suppliers");
