import { db } from "../../config/db";
import { mediaAssets, users } from "../../config/schema";
import { eq, desc, and, like, sql } from "drizzle-orm";
import { uploadBuffer, deleteAsset } from "../../utils/cloud";
import { CreateMediaInput, MediaQuery } from "./media.interface";

const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 48;

const mediaWithUploader = {
  id: mediaAssets.id,
  url: mediaAssets.url,
  publicId: mediaAssets.publicId,
  filename: mediaAssets.filename,
  mimeType: mediaAssets.mimeType,
  size: mediaAssets.size,
  width: mediaAssets.width,
  height: mediaAssets.height,
  provider: mediaAssets.provider,
  folder: mediaAssets.folder,
  alt: mediaAssets.alt,
  createdAt: mediaAssets.createdAt,
  uploaderName: users.name,
};

export const saveMedia = async (input: CreateMediaInput) => {
  const result = await uploadBuffer(input.buffer, {
    folder: input.folder,
    filename: input.filename,
    mimeType: input.mimeType,
  });

  const insert = await db.insert(mediaAssets).values({
    url: result.url,
    publicId: result.publicId,
    filename: input.filename,
    mimeType: input.mimeType,
    size: input.size,
    width: result.width,
    height: result.height,
    provider: result.provider,
    folder: input.folder,
    alt: input.alt,
    uploaderId: input.uploaderId,
  });

  const rows = await db
    .select(mediaWithUploader)
    .from(mediaAssets)
    .leftJoin(users, eq(mediaAssets.uploaderId, users.id))
    .where(eq(mediaAssets.id, insert[0].insertId))
    .limit(1);

  return rows[0] || null;
};

export const getAll = async (query: MediaQuery) => {
  const page = query.page || DEFAULT_PAGE;
  const limit = query.limit || DEFAULT_LIMIT;
  const offset = (page - 1) * limit;

  const conditions: any[] = [];
  if (query.folder) conditions.push(eq(mediaAssets.folder, query.folder));
  if (query.search) conditions.push(like(mediaAssets.filename, `%${query.search}%`));

  const where = conditions.length > 0 ? and(...conditions) : undefined;

  const data = await db
    .select(mediaWithUploader)
    .from(mediaAssets)
    .leftJoin(users, eq(mediaAssets.uploaderId, users.id))
    .where(where)
    .orderBy(desc(mediaAssets.createdAt))
    .limit(limit)
    .offset(offset);

  const countResult = await db
    .select({ count: sql<number>`count(*)` })
    .from(mediaAssets)
    .where(where);

  const total = Number(countResult[0].count);

  return {
    data,
    pagination: {
      page,
      limit,
      total,
      totalPages: Math.ceil(total / limit),
    },
  };
};

export const getFolders = async () => {
  const rows = await db
    .selectDistinct({ folder: mediaAssets.folder })
    .from(mediaAssets)
    .orderBy(mediaAssets.folder);

  const rows2 = await db.select().from(mediaAssets);
  const byFolder: Record<string, number> = {};
  for (const r of rows2) {
    byFolder[r.folder] = (byFolder[r.folder] || 0) + 1;
  }
  return rows.map((r) => ({ name: r.folder, count: byFolder[r.folder] || 0 }));
};

export const getById = async (id: number) => {
  const rows = await db
    .select(mediaWithUploader)
    .from(mediaAssets)
    .leftJoin(users, eq(mediaAssets.uploaderId, users.id))
    .where(eq(mediaAssets.id, id))
    .limit(1);
  return rows[0] || null;
};

export const remove = async (id: number) => {
  const asset = await getById(id);
  if (!asset) return { success: false };
  await deleteAsset(asset.publicId || undefined, asset.url);
  await db.delete(mediaAssets).where(eq(mediaAssets.id, id));
  return { success: true };
};

export const updateAlt = async (id: number, alt: string) => {
  await db.update(mediaAssets).set({ alt }).where(eq(mediaAssets.id, id));
  return getById(id);
};
