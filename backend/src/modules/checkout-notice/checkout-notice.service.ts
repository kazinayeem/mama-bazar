import { db } from "../../config/db";
import { checkoutNotices } from "../../config/schema";
import { eq, and, asc } from "drizzle-orm";
import { AppError } from "../../utils/AppError";
import { CreateCheckoutNoticeInput, UpdateCheckoutNoticeInput } from "./checkout-notice.schema";

export const getActive = async () => {
  return db
    .select()
    .from(checkoutNotices)
    .where(eq(checkoutNotices.status, "active"))
    .orderBy(asc(checkoutNotices.priority), asc(checkoutNotices.id));
};

export const getAll = async () => {
  return db.select().from(checkoutNotices).orderBy(asc(checkoutNotices.priority), asc(checkoutNotices.id));
};

export const getById = async (id: number) => {
  const rows = await db.select().from(checkoutNotices).where(eq(checkoutNotices.id, id)).limit(1);
  return rows[0] || null;
};

export const create = async (data: CreateCheckoutNoticeInput) => {
  const result = await db.insert(checkoutNotices).values({
    text: data.text,
    priority: data.priority ?? 0,
    backgroundColor: data.backgroundColor ?? "#FFF7ED",
    textColor: data.textColor ?? "#9A3412",
    icon: data.icon ?? "alert",
    status: data.status ?? "active",
  });
  return getById(result[0].insertId);
};

export const update = async (id: number, data: UpdateCheckoutNoticeInput) => {
  const existing = await getById(id);
  if (!existing) throw new AppError(404, "Checkout notice not found");
  await db.update(checkoutNotices).set({ ...data }).where(eq(checkoutNotices.id, id));
  return getById(id);
};

export const remove = async (id: number) => {
  await db.delete(checkoutNotices).where(eq(checkoutNotices.id, id));
  return { success: true };
};
