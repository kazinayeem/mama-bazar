import { db } from "../../config/db";
import { paymentMethods } from "../../config/schema";
import { eq, and, asc } from "drizzle-orm";
import { AppError } from "../../utils/AppError";
import { CreatePaymentMethodInput, UpdatePaymentMethodInput } from "./payment.schema";

const toNum = (v: string | null | undefined, fallback = 0) => {
  const n = Number(v);
  return Number.isFinite(n) ? n : fallback;
};

const publicFields = (m: any) => ({
  id: m.id,
  code: m.code,
  name: m.name,
  type: m.type,
  config: typeof m.config === "string" ? JSON.parse(m.config || "{}") : (m.config ?? {}),
});

export const getActive = async () => {
  const rows = await db
    .select()
    .from(paymentMethods)
    .where(and(eq(paymentMethods.enabled, true), eq(paymentMethods.maintenanceMode, false)))
    .orderBy(asc(paymentMethods.sortOrder), asc(paymentMethods.id));
  return rows.map(publicFields);
};

export const getByCode = async (code: string) => {
  const rows = await db.select().from(paymentMethods).where(eq(paymentMethods.code, code)).limit(1);
  return rows[0] || null;
};

export const getAll = async () => {
  const rows = await db.select().from(paymentMethods).orderBy(asc(paymentMethods.sortOrder), asc(paymentMethods.id));
  return rows.map((m) => ({ ...m, config: typeof m.config === "string" ? JSON.parse(m.config || "{}") : (m.config ?? {}) }));
};

export const getById = async (id: number) => {
  const rows = await db.select().from(paymentMethods).where(eq(paymentMethods.id, id)).limit(1);
  return rows[0] || null;
};

export const create = async (data: CreatePaymentMethodInput) => {
  const result = await db.insert(paymentMethods).values({
    code: data.code,
    name: data.name,
    type: data.type,
    enabled: data.enabled ?? true,
    sortOrder: data.sortOrder ?? 0,
    maintenanceMode: data.maintenanceMode ?? false,
    config: data.config ?? {},
  });
  return getById(result[0].insertId);
};

export const update = async (id: number, data: UpdatePaymentMethodInput) => {
  const existing = await getById(id);
  if (!existing) throw new AppError(404, "Payment method not found");
  const updateData: Record<string, unknown> = {};
  if (data.code !== undefined) updateData.code = data.code;
  if (data.name !== undefined) updateData.name = data.name;
  if (data.type !== undefined) updateData.type = data.type;
  if (data.enabled !== undefined) updateData.enabled = data.enabled;
  if (data.sortOrder !== undefined) updateData.sortOrder = data.sortOrder;
  if (data.maintenanceMode !== undefined) updateData.maintenanceMode = data.maintenanceMode;
  if (data.config !== undefined) updateData.config = data.config;
  await db.update(paymentMethods).set(updateData).where(eq(paymentMethods.id, id));
  return getById(id);
};

export const setStatuses = async (ids: number[], enabled: boolean) => {
  for (const id of ids) {
    await db.update(paymentMethods).set({ enabled }).where(eq(paymentMethods.id, id));
  }
  return { success: true };
};

export const remove = async (id: number) => {
  await db.delete(paymentMethods).where(eq(paymentMethods.id, id));
  return { success: true };
};
