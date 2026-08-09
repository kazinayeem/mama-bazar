import { db } from "../../config/db";
import { costs, suppliers, products, orders, bookings } from "../../config/schema";
import { eq, and, like, desc, sql } from "drizzle-orm"
import { AppError } from "../../utils/AppError";

const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 20;

export interface CostQuery {
  page?: number;
  limit?: number;
  search?: string;
  costType?: string;
}

export const listCosts = async (query: CostQuery) => {
  const page = Math.max(1, query.page || DEFAULT_PAGE);
  const limit = Math.max(1, query.limit || DEFAULT_LIMIT);
  const offset = (page - 1) * limit;

  const conditions: any[] = [];
  if (query.costType) conditions.push(eq(costs.costType, query.costType));
  if (query.search) conditions.push(like(costs.title, `%${query.search}%`));
  const where = conditions.length > 0 ? and(...conditions) : undefined;

  const data = await db
    .select({
      id: costs.id,
      title: costs.title,
      costType: costs.costType,
      quantity: costs.quantity,
      unitCost: costs.unitCost,
      totalCost: costs.totalCost,
      supplierName: suppliers.name,
      productName: products.title,
      orderOrderId: costs.orderId,
      bookingId: costs.bookingId,
      costDate: costs.costDate,
      paymentMethod: costs.paymentMethod,
      notes: costs.notes,
      attachmentUrl: costs.attachmentUrl,
      createdAt: costs.createdAt,
    })
    .from(costs)
    .leftJoin(suppliers, eq(costs.supplierId, suppliers.id))
    .leftJoin(products, eq(costs.productId, products.id))
    .leftJoin(orders, eq(costs.orderId, orders.id))
    .leftJoin(bookings, eq(costs.bookingId, bookings.id))
    .where(where)
    .orderBy(desc(costs.costDate))
    .limit(limit)
    .offset(offset);

  const countResult = await db.select({ count: sql<number>`count(*)` }).from(costs).where(where);
  const total = Number(countResult[0].count);
  return {
    data,
    pagination: { page, limit, total, totalPages: Math.ceil(total / limit) },
  };
};

export const getCost = async (id: number) => {
  const rows = await db
    .select({
      id: costs.id,
      title: costs.title,
      costType: costs.costType,
      quantity: costs.quantity,
      unitCost: costs.unitCost,
      totalCost: costs.totalCost,
      supplierId: costs.supplierId,
      supplierName: suppliers.name,
      productId: costs.productId,
      productName: products.title,
      orderId: costs.orderId,
      bookingId: costs.bookingId,
      costDate: costs.costDate,
      paymentMethod: costs.paymentMethod,
      notes: costs.notes,
      attachmentUrl: costs.attachmentUrl,
      createdAt: costs.createdAt,
      updatedAt: costs.updatedAt,
    })
    .from(costs)
    .leftJoin(suppliers, eq(costs.supplierId, suppliers.id))
    .leftJoin(products, eq(costs.productId, products.id))
    .leftJoin(orders, eq(costs.orderId, orders.id))
    .leftJoin(bookings, eq(costs.bookingId, bookings.id))
    .where(eq(costs.id, id))
    .limit(1);
  return rows[0] || null;
};

export const createCost = async (input: {
  title: string;
  costType?: string;
  quantity?: string | number;
  unitCost?: string | number;
  totalCost?: string | number;
  supplierId?: number | null;
  productId?: number | null;
  orderId?: number | null;
  bookingId?: number | null;
  costDate: string;
  paymentMethod?: string;
  notes?: string | null;
  attachmentUrl?: string | null;
}) => {
  const [inserted] = await db.insert(costs).values({
    title: input.title,
    costType: input.costType || "operational",
    quantity: String(input.quantity ?? 1),
    unitCost: String(input.unitCost ?? 0),
    totalCost: String(input.totalCost ?? 0),
    supplierId: input.supplierId || null,
    productId: input.productId || null,
    orderId: input.orderId || null,
    bookingId: input.bookingId || null,
    costDate: sql`STR_TO_DATE(${input.costDate}, '%Y-%m-%d %H:%i:%s')`,
    paymentMethod: input.paymentMethod || "cash",
    notes: input.notes || null,
    attachmentUrl: input.attachmentUrl || null,
  });
  return inserted;
};

export const updateCost = async (id: number, input: Record<string, unknown>) => {
  const existing = await getCost(id);
  if (!existing) throw new AppError(404, "Cost not found");
  await db
    .update(costs)
    .set({
      title: input.title !== undefined ? String(input.title) : existing.title,
      costType: input.costType !== undefined ? String(input.costType) : existing.costType,
      quantity: input.quantity !== undefined ? String(input.quantity) : existing.quantity,
      unitCost: input.unitCost !== undefined ? String(input.unitCost) : existing.unitCost,
      totalCost: input.totalCost !== undefined ? String(input.totalCost) : existing.totalCost,
      supplierId: input.supplierId !== undefined ? (Number(input.supplierId) || null) : existing.supplierId,
      productId: input.productId !== undefined ? (Number(input.productId) || null) : existing.productId,
      orderId: input.orderId !== undefined ? (Number(input.orderId) || null) : existing.orderId,
      bookingId: input.bookingId !== undefined ? (Number(input.bookingId) || null) : existing.bookingId,
      costDate: input.costDate !== undefined ? sql`STR_TO_DATE(${input.costDate}, '%Y-%m-%d %H:%i:%s')` : existing.costDate,
      paymentMethod: input.paymentMethod !== undefined ? String(input.paymentMethod) : existing.paymentMethod,
      notes: input.notes !== undefined ? (input.notes as string | null) : existing.notes,
      attachmentUrl: input.attachmentUrl !== undefined ? (input.attachmentUrl as string | null) : existing.attachmentUrl,
    })
    .where(eq(costs.id, id));
  return getCost(id);
};

export const deleteCost = async (id: number) => {
  const existing = await getCost(id);
  if (!existing) throw new AppError(404, "Cost not found");
  await db.delete(costs).where(eq(costs.id, id));
  return { success: true };
};
