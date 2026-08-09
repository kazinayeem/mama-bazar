import { db } from "../../config/db";
import { shippingMethods } from "../../config/schema";
import { eq, and, desc, asc } from "drizzle-orm";
import { AppError } from "../../utils/AppError";
import { CreateShippingMethodInput, UpdateShippingMethodInput } from "./shipping.schema";

const toNum = (v: string | null | undefined, fallback = 0) => {
  const n = Number(v);
  return Number.isFinite(n) ? n : fallback;
};

export const getActive = async () => {
  const rows = await db
    .select()
    .from(shippingMethods)
    .where(eq(shippingMethods.status, "active"))
    .orderBy(asc(shippingMethods.priority), asc(shippingMethods.id));
  return rows.map((m) => ({
    ...m,
    charge: toNum(m.charge),
    freeShippingMinAmount: m.freeShippingMinAmount === null ? null : toNum(m.freeShippingMinAmount),
  }));
};

export const estimate = async (subtotal: number) => {
  const methods = await getActive();
  return methods.map((m) => {
    let cost = m.charge;
    if (m.freeShippingMinAmount !== null && subtotal >= m.freeShippingMinAmount) {
      cost = 0;
    }
    return { ...m, estimatedCost: cost };
  });
};

export const getAll = async () => {
  return db.select().from(shippingMethods).orderBy(asc(shippingMethods.priority), asc(shippingMethods.id));
};

export const getById = async (id: number) => {
  const rows = await db.select().from(shippingMethods).where(eq(shippingMethods.id, id)).limit(1);
  return rows[0] || null;
};

export const create = async (data: CreateShippingMethodInput) => {
  const result = await db.insert(shippingMethods).values({
    name: data.name,
    charge: String(data.charge),
    estimatedDelivery: data.estimatedDelivery ?? null,
    description: data.description ?? null,
    priority: data.priority ?? 0,
    freeShippingMinAmount: data.freeShippingMinAmount !== undefined ? String(data.freeShippingMinAmount) : null,
    codAvailable: data.codAvailable ?? true,
    status: data.status ?? "active",
  });
  return getById(result[0].insertId);
};

export const update = async (id: number, data: UpdateShippingMethodInput) => {
  const existing = await getById(id);
  if (!existing) throw new AppError(404, "Shipping method not found");
  const updateData: Record<string, unknown> = {};
  if (data.name !== undefined) updateData.name = data.name;
  if (data.charge !== undefined) updateData.charge = String(data.charge);
  if (data.estimatedDelivery !== undefined) updateData.estimatedDelivery = data.estimatedDelivery;
  if (data.description !== undefined) updateData.description = data.description;
  if (data.priority !== undefined) updateData.priority = data.priority;
  if (data.freeShippingMinAmount !== undefined) {
    updateData.freeShippingMinAmount =
      data.freeShippingMinAmount === null ? null : String(data.freeShippingMinAmount);
  }
  if (data.codAvailable !== undefined) updateData.codAvailable = data.codAvailable;
  if (data.status !== undefined) updateData.status = data.status;
  await db.update(shippingMethods).set(updateData).where(eq(shippingMethods.id, id));
  return getById(id);
};

export const remove = async (id: number) => {
  await db.delete(shippingMethods).where(eq(shippingMethods.id, id));
  return { success: true };
};
