import { db } from "../../config/db";
import { banners } from "../../config/schema";
import { eq, desc, asc } from "drizzle-orm";
import { CreateBannerInput, UpdateBannerInput } from "./banner.interface";

export const getAll = async () => {
  return db.select().from(banners).orderBy(desc(banners.updatedAt));
};

export const getById = async (id: number) => {
  const rows = await db.select().from(banners).where(eq(banners.id, id)).limit(1);
  return rows[0] || null;
};

export const create = async (data: CreateBannerInput) => {
  const result = await db.insert(banners).values(data);
  return getById(result[0].insertId);
};

export const update = async (id: number, data: UpdateBannerInput) => {
  await db.update(banners).set({ ...data, updatedAt: new Date() }).where(eq(banners.id, id));
  return getById(id);
};

export const remove = async (id: number) => {
  await db.delete(banners).where(eq(banners.id, id));
  return { success: true };
};
