import { and, asc, count, desc, eq, like, or, sql } from "drizzle-orm";
import { db } from "../../config/db";
import { categories, products } from "../../config/schema";
import {
  CreateCategoryInput,
  UpdateCategoryInput,
  CategoryUsage,
  CategoryTreeNode,
} from "./category.interface";
import { AppError } from "../../utils/AppError";

export interface CategoryListParams {
  page?: number;
  limit?: number;
  search?: string;
  status?: string;
  parentId?: string;
  featured?: boolean;
  sort?: string;
}

const buildWhere = (params: CategoryListParams) => {
  const conditions = [];
  if (params.search) {
    conditions.push(like(categories.name, `%${params.search}%`));
  }
  if (params.status && ["active", "inactive", "archived"].includes(params.status)) {
    conditions.push(eq(categories.status, params.status as never));
  }
  if (params.parentId === "root") {
    conditions.push(sql`${categories.parentId} IS NULL`);
  } else if (params.parentId) {
    conditions.push(eq(categories.parentId, Number(params.parentId)));
  }
  if (params.featured !== undefined) {
    conditions.push(eq(categories.featured, params.featured));
  }
  return conditions.length ? and(...conditions) : undefined;
};

export const getAll = async (params: CategoryListParams = {}) => {
  const page = Math.max(1, params.page || 1);
  const limit = Math.min(100, Math.max(1, params.limit || 20));
  const where = buildWhere(params);
  const orderBy =
    params.sort === "name"
      ? [asc(categories.name)]
      : params.sort === "oldest"
        ? [asc(categories.createdAt)]
        : [asc(categories.sortOrder), desc(categories.createdAt)];

  const [rows, totalRows] = await Promise.all([
    db.select().from(categories).where(where).orderBy(...orderBy).limit(limit).offset((page - 1) * limit),
    db.select({ count: count() }).from(categories).where(where),
  ]);

  const total = totalRows[0]?.count ?? 0;
  return {
    data: rows,
    pagination: { page, limit, total, totalPages: Math.max(1, Math.ceil(total / limit)) },
  };
};

export const getAllFlat = async () => {
  return db
    .select()
    .from(categories)
    .where(eq(categories.status, "active"))
    .orderBy(asc(categories.sortOrder), asc(categories.name));
};

export const getTree = async (): Promise<CategoryTreeNode[]> => {
  const rows = await db
    .select()
    .from(categories)
    .where(eq(categories.status, "active"))
    .orderBy(asc(categories.sortOrder), asc(categories.name));

  const map = new Map<number, CategoryTreeNode>();
  rows.forEach((row) => map.set(row.id, { ...row, children: [] }));

  const roots: CategoryTreeNode[] = [];
  map.forEach((node) => {
    const parent = node.parentId != null ? map.get(node.parentId) : undefined;
    if (parent) parent.children.push(node);
    else roots.push(node);
  });
  return roots;
};

export const getById = async (id: number) => {
  const rows = await db.select().from(categories).where(eq(categories.id, id)).limit(1);
  return rows[0] || null;
};

export const getBySlug = async (slug: string) => {
  const rows = await db.select().from(categories).where(eq(categories.slug, slug)).limit(1);
  return rows[0] || null;
};

export const getUsage = async (id: number): Promise<CategoryUsage> => {
  const [productRows, childRows] = await Promise.all([
    db
      .select({ count: count() })
      .from(products)
      .where(
        or(eq(products.categoryId, id), eq(products.subCategoryId, id), eq(products.childCategoryId, id))
      ),
    db.select({ count: count() }).from(categories).where(eq(categories.parentId, id)),
  ]);
  return { products: productRows[0]?.count ?? 0, subCategories: childRows[0]?.count ?? 0 };
};

import { memoryCache } from "../../utils/cache";

export const create = async (data: CreateCategoryInput) => {
  const result = await db.insert(categories).values(data);
  memoryCache.invalidate("cat_slug");
  memoryCache.invalidate("homepage_config");
  return getById(result[0].insertId);
};

export const update = async (id: number, data: UpdateCategoryInput) => {
  if (data.parentId === id) throw new AppError(400, "A category cannot be its own parent");
  await db.update(categories).set(data).where(eq(categories.id, id));
  memoryCache.invalidate("cat_slug");
  memoryCache.invalidate("homepage_config");
  return getById(id);
};

export const moveProducts = async (fromId: number, targetId: number | null) => {
  const usage = await getUsage(fromId);
  const moved = await db
    .update(products)
    .set({
      categoryId: targetId,
      ...(targetId === null
        ? { subCategoryId: null, childCategoryId: null }
        : { subCategoryId: null, childCategoryId: null }),
    })
    .where(
      or(eq(products.categoryId, fromId), eq(products.subCategoryId, fromId), eq(products.childCategoryId, fromId))
    );
  memoryCache.invalidate("cat_slug");
  return { moved: moved[0].affectedRows, usage };
};

export const remove = async (id: number) => {
  await db.delete(categories).where(eq(categories.id, id));
  memoryCache.invalidate("cat_slug");
  memoryCache.invalidate("homepage_config");
  return { success: true };
};
