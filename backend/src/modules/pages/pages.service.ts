import { db } from "../../config/db";
import { policyPages, contactMessages } from "../../config/schema";
import { eq, sql, desc } from "drizzle-orm";
import { AppError } from "../../utils/AppError";

export const getPublishedBySlug = async (slug: string) => {
  const rows = await db
    .select()
    .from(policyPages)
    .where(eq(policyPages.slug, slug))
    .limit(1);
  const page = rows[0];
  if (!page) return null;
  if (page.status !== "published") {
    return null;
  }
  return {
    id: page.id,
    slug: page.slug,
    title: page.title,
    content: page.content,
    status: page.status,
    lastUpdated: page.lastUpdated,
    createdAt: page.createdAt,
  };
};

export const getAll = async () => {
  return db
    .select({
      id: policyPages.id,
      slug: policyPages.slug,
      title: policyPages.title,
      status: policyPages.status,
      lastUpdated: policyPages.lastUpdated,
      createdAt: policyPages.createdAt,
    })
    .from(policyPages)
    .orderBy(policyPages.slug);
};

export const create = async (data: {
  slug: string;
  title: string;
  content: string;
  status: "published" | "draft";
  updatedBy: number;
}) => {
  const exists = await db.select({ id: policyPages.id }).from(policyPages).where(eq(policyPages.slug, data.slug)).limit(1);
  if (exists[0]) {
    throw new AppError(409, "A page with this slug already exists");
  }
  const now = Math.floor(Date.now() / 1000);
  const result = await db.insert(policyPages).values({
    slug: data.slug,
    title: data.title,
    content: data.content,
    status: data.status,
    lastUpdated: now,
    updatedBy: data.updatedBy,
  });
  return { id: Number(result[0].insertId) };
};

export const update = async (
  id: number,
  payload: {
    title?: string;
    content?: string;
    status?: "published" | "draft";
    updatedBy: number;
  }
) => {
  const exists = await db.select({ id: policyPages.id }).from(policyPages).where(eq(policyPages.id, id)).limit(1);
  if (!exists[0]) {
    throw new AppError(404, "Policy page not found");
  }
  const now = Math.floor(Date.now() / 1000);
  await db
    .update(policyPages)
    .set({
      title: payload.title,
      content: payload.content,
      status: payload.status,
      lastUpdated: now,
      updatedBy: payload.updatedBy,
    })
    .where(eq(policyPages.id, id));
  return { id, lastUpdated: now };
};

export const remove = async (id: number) => {
  await db.delete(policyPages).where(eq(policyPages.id, id));
  return { success: true };
};

export const createContactMessage = async (payload: {
  name: string;
  phone: string;
  email?: string;
  message: string;
}) => {
  const result = await db.insert(contactMessages).values({
    name: payload.name,
    phone: payload.phone,
    email: payload.email || null,
    message: payload.message,
    status: "new",
  });
  return { success: true, id: Number(result[0].insertId) };
};

export const getContactMessages = async () => {
  return db.select().from(contactMessages).orderBy(desc(contactMessages.createdAt));
};

export const setContactMessageStatus = async (id: number, status: "new" | "read" | "archived") => {
  await db.update(contactMessages).set({ status }).where(eq(contactMessages.id, id));
  return { success: true };
};