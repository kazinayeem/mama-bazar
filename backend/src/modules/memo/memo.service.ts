import { db } from "../../config/db";
import { memos, users } from "../../config/schema";
import { eq, and, like, desc, inArray, sql } from "drizzle-orm";
import { AppError } from "../../utils/AppError";

const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 20;

export interface MemoQuery {
  page?: number;
  limit?: number;
  search?: string;
  entityType?: string;
  folder?: string;
}

const baseColumns = {
  id: memos.id,
  title: memos.title,
  entityType: memos.entityType,
  entityId: memos.entityId,
  url: memos.url,
  publicId: memos.publicId,
  filename: memos.filename,
  mimeType: memos.mimeType,
  size: memos.size,
  folder: memos.folder,
  notes: memos.notes,
  uploadedById: memos.uploadedById,
  createdAt: memos.createdAt,
};

export const listMemos = async (query: MemoQuery) => {
  const page = Math.max(1, query.page || DEFAULT_PAGE);
  const limit = Math.max(1, query.limit || DEFAULT_LIMIT);
  const offset = (page - 1) * limit;

  const conditions: any[] = [];
  if (query.entityType) conditions.push(eq(memos.entityType, query.entityType));
  if (query.folder) conditions.push(eq(memos.folder, query.folder));
  if (query.search) conditions.push(like(memos.title, `%${query.search}%`));
  const where = conditions.length > 0 ? and(...conditions) : undefined;

  const data = await db
    .select({ ...baseColumns, uploadedByName: users.name })
    .from(memos)
    .leftJoin(users, eq(memos.uploadedById, users.id))
    .where(where)
    .orderBy(desc(memos.createdAt))
    .limit(limit)
    .offset(offset);

  const countResult = await db.select({ count: sql<number>`count(*)` }).from(memos).where(where);
  const total = Number(countResult[0].count);
  return {
    data,
    pagination: { page, limit, total, totalPages: Math.ceil(total / limit) },
  };
};

export const getMemo = async (id: number) => {
  const rows = await db
    .select({ ...baseColumns, uploadedByName: users.name })
    .from(memos)
    .leftJoin(users, eq(memos.uploadedById, users.id))
    .where(eq(memos.id, id))
    .limit(1);
  return rows[0] || null;
};

export const createMemo = async (input: {
  title: string;
  entityType: string;
  entityId?: number | null;
  url: string;
  publicId: string;
  filename: string;
  mimeType: string;
  size?: number;
  folder?: string;
  notes?: string | null;
  uploadedById?: number | null;
}) => {
  const [inserted] = await db.insert(memos).values({
    title: input.title,
    entityType: input.entityType,
    entityId: input.entityId || null,
    url: input.url,
    publicId: input.publicId,
    filename: input.filename,
    mimeType: input.mimeType,
    size: input.size ?? 0,
    folder: input.folder || "memos",
    notes: input.notes || null,
    uploadedById: input.uploadedById || null,
  });
  return inserted;
};

export const deleteMemo = async (id: number) => {
  const existing = await getMemo(id);
  if (!existing) throw new AppError(404, "Memo not found");
  await db.delete(memos).where(eq(memos.id, id));
  return { success: true };
};

export const deleteManyMemos = async (ids: number[]) => {
  if (ids.length === 0) return { success: true, deleted: 0 };
  await db.delete(memos).where(inArray(memos.id, ids));
  return { success: true, deleted: ids.length };
};
