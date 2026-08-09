import { and, asc, count, desc, eq, like } from "drizzle-orm";
import { db } from "../../config/db";
import { brands, products } from "../../config/schema";
import { CreateBrandInput, UpdateBrandInput } from "./brand.interface";

export interface BrandListParams {
  page?: number;
  limit?: number;
  search?: string;
  status?: string;
  featured?: boolean;
  sort?: string;
}

const buildWhere = (params: BrandListParams) => {
  const conditions = [];
  if (params.search) conditions.push(like(brands.name, `%${params.search}%`));
  if (params.status && ["active", "inactive", "archived"].includes(params.status)) {
    conditions.push(eq(brands.status, params.status as never));
  }
  if (params.featured !== undefined) conditions.push(eq(brands.featured, params.featured));
  return conditions.length ? and(...conditions) : undefined;
};

export const getAll = async (params: BrandListParams = {}) => {
  const page = Math.max(1, params.page || 1);
  const limit = Math.min(100, Math.max(1, params.limit || 20));
  const where = buildWhere(params);
  const orderBy =
    params.sort === "name" || params.sort === "oldest"
      ? [asc(brands.createdAt)]
      : [asc(brands.sortOrder), desc(brands.createdAt)];

  const [rows, totalRows] = await Promise.all([
    db.select().from(brands).where(where).orderBy(...orderBy).limit(limit).offset((page - 1) * limit),
    db.select({ count: count() }).from(brands).where(where),
  ]);

  const total = totalRows[0]?.count ?? 0;
  return {
    data: rows,
    pagination: { page, limit, total, totalPages: Math.max(1, Math.ceil(total / limit)) },
  };
};

export const getAllActive = async () => {
  return db.select().from(brands).where(eq(brands.status, "active")).orderBy(asc(brands.sortOrder), asc(brands.name));
};

export const getById = async (id: number) => {
  const rows = await db.select().from(brands).where(eq(brands.id, id)).limit(1);
  return rows[0] || null;
};

export const getBySlug = async (slug: string) => {
  const rows = await db.select().from(brands).where(eq(brands.slug, slug)).limit(1);
  return rows[0] || null;
};

export const getUsage = async (id: number) => {
  const rows = await db.select({ count: count() }).from(products).where(eq(products.brandId, id));
  return rows[0]?.count ?? 0;
};

export const create = async (data: CreateBrandInput) => {
  const result = await db.insert(brands).values(data);
  return getById(result[0].insertId);
};

export const update = async (id: number, data: UpdateBrandInput) => {
  await db.update(brands).set(data).where(eq(brands.id, id));
  return getById(id);
};

export const moveProducts = async (fromId: number, targetId: number | null) => {
  const moved = await db.update(products).set({ brandId: targetId }).where(eq(products.brandId, fromId));
  return { moved: moved[0].affectedRows };
};

export const remove = async (id: number) => {
  await db.delete(brands).where(eq(brands.id, id));
  return { success: true };
};
