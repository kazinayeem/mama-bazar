import { db } from "../../config/db";
import { reviews, products, users } from "../../config/schema";
import { eq, desc, and, sql } from "drizzle-orm";
import { memoryCache } from "../../utils/cache";

export interface CreateReviewInput {
  productId: number;
  userId?: number | null;
  customerName?: string | null;
  rating: number;
  title?: string | null;
  comment: string;
}

export interface ReviewQuery {
  page?: number;
  limit?: number;
  productId?: number;
  status?: "pending" | "approved" | "rejected";
  search?: string;
}

const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 12;

export const getAll = async (query: ReviewQuery) => {
  const page = query.page || DEFAULT_PAGE;
  const limit = query.limit || DEFAULT_LIMIT;
  const offset = (page - 1) * limit;

  const cacheKey = `reviews:${query.productId || 'all'}:${query.status || 'all'}:${query.search || 'none'}:${page}:${limit}`;
  const cached = memoryCache.get<any>(cacheKey);
  if (cached) return cached;

  const conditions: any[] = [];
  if (query.status) conditions.push(eq(reviews.status, query.status));
  if (query.productId) conditions.push(eq(reviews.productId, query.productId));
  if (query.search) conditions.push(sql`${reviews.comment} LIKE ${`%${query.search}%`}`);

  const where = conditions.length > 0 ? and(...conditions) : undefined;

  const [rows, countResult] = await Promise.all([
    db
      .select({
        id: reviews.id,
        productId: reviews.productId,
        userId: reviews.userId,
        customerName: reviews.customerName,
        rating: reviews.rating,
        title: reviews.title,
        comment: reviews.comment,
        status: reviews.status,
        createdAt: reviews.createdAt,
        productTitle: products.title,
        productSlug: products.slug,
        productImage: sql<string | null>`JSON_UNQUOTE(JSON_EXTRACT(${products.images}, '$[0]'))`,
      })
      .from(reviews)
      .leftJoin(products, eq(reviews.productId, products.id))
      .where(where)
      .orderBy(desc(reviews.createdAt))
      .limit(limit)
      .offset(offset),
    db.select({ count: sql<number>`count(*)` }).from(reviews).where(where),
  ]);

  const total = Number(countResult[0]?.count || 0);

  const result = {
    data: rows,
    pagination: { page, limit, total, totalPages: Math.ceil(total / limit) },
  };

  memoryCache.set(cacheKey, result, 120);
  return result;
};

export const getById = async (id: number) => {
  const rows = await db
    .select({
      id: reviews.id,
      productId: reviews.productId,
      userId: reviews.userId,
      customerName: reviews.customerName,
      rating: reviews.rating,
      title: reviews.title,
      comment: reviews.comment,
      status: reviews.status,
      createdAt: reviews.createdAt,
      productTitle: products.title,
      productSlug: products.slug,
      customerPhone: users.phone,
      customerRole: users.role,
    })
    .from(reviews)
    .leftJoin(products, eq(reviews.productId, products.id))
    .leftJoin(users, eq(reviews.userId, users.id))
    .where(eq(reviews.id, id))
    .limit(1);
  return rows[0] || null;
};

export const create = async (data: CreateReviewInput) => {
  const productRows = await db.select({ id: products.id }).from(products).where(eq(products.id, data.productId)).limit(1);
  if (!productRows[0]) throw new Error("Product not found");

  if (data.userId) {
    const existing = await db
      .select({ id: reviews.id })
      .from(reviews)
      .where(and(eq(reviews.productId, data.productId), eq(reviews.userId, data.userId)))
      .limit(1);
    if (existing[0]) throw new Error("You have already reviewed this product");
  }

  const result = await db.insert(reviews).values({
    productId: data.productId,
    userId: data.userId ?? null,
    customerName: data.customerName || null,
    rating: Math.max(1, Math.min(5, data.rating)),
    title: data.title || null,
    comment: data.comment,
    status: "pending",
  });

  memoryCache.invalidate("reviews:");
  memoryCache.invalidate("rating:");

  const id = result[0].insertId;
  return getById(id);
};

export const updateStatus = async (id: number, status: "pending" | "approved" | "rejected") => {
  const existing = await db.select({ id: reviews.id }).from(reviews).where(eq(reviews.id, id)).limit(1);
  if (!existing[0]) throw new Error("Review not found");
  await db.update(reviews).set({ status }).where(eq(reviews.id, id));
  memoryCache.invalidate("reviews:");
  memoryCache.invalidate("rating:");
  return getById(id);
};

export const remove = async (id: number) => {
  const result = await db.delete(reviews).where(eq(reviews.id, id));
  if (!result[0].affectedRows) throw new Error("Review not found");
  memoryCache.invalidate("reviews:");
  memoryCache.invalidate("rating:");
  return { success: true };
};
