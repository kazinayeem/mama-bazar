import { db } from "../../config/db";
import { siteSettings } from "../../config/schema";
import { desc, eq } from "drizzle-orm";

export const getAll = async () => {
  return db.select().from(siteSettings);
};

export const get = async (key: string) => {
  const rows = await db
    .select()
    .from(siteSettings)
    .where(eq(siteSettings.key, key))
    .orderBy(desc(siteSettings.id))
    .limit(1);
  return rows[0] || null;
};

export const set = async (key: string, value: string | null) => {
  const existing = await get(key);
  if (existing) {
    await db.update(siteSettings).set({ value }).where(eq(siteSettings.key, key));
  } else {
    await db.insert(siteSettings).values({ key, value });
  }
  return get(key);
};

export const getJSON = async <T>(key: string, fallback: T): Promise<T> => {
  const setting = await get(key);
  if (!setting?.value) return fallback;
  try {
    return JSON.parse(setting.value) as T;
  } catch {
    return fallback;
  }
};

export const setJSON = async <T>(key: string, value: T): Promise<void> => {
  await set(key, JSON.stringify(value));
};
