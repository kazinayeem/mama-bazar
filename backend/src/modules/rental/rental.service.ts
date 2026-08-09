import { db } from "../../config/db";
import { rentals, products, users } from "../../config/schema";
import { eq, and, like, desc, sql } from "drizzle-orm";
import { AppError } from "../../utils/AppError";

const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 20;

export interface RentalQuery {
  page?: number;
  limit?: number;
  search?: string;
  status?: string;
  paymentStatus?: string;
}

const baseColumns = {
  id: rentals.id,
  rentalItem: rentals.rentalItem,
  productId: rentals.productId,
  customerName: rentals.customerName,
  phone: rentals.phone,
  email: rentals.email,
  userId: rentals.userId,
  quantity: rentals.quantity,
  startDate: rentals.startDate,
  endDate: rentals.endDate,
  returnDate: rentals.returnDate,
  rateType: rentals.rateType,
  dailyRate: rentals.dailyRate,
  weeklyRate: rentals.weeklyRate,
  monthlyRate: rentals.monthlyRate,
  rate: rentals.rate,
  durationUnits: rentals.durationUnits,
  securityDeposit: rentals.securityDeposit,
  discount: rentals.discount,
  additionalCharge: rentals.additionalCharge,
  totalAmount: rentals.totalAmount,
  paymentStatus: rentals.paymentStatus,
  status: rentals.status,
  notes: rentals.notes,
  attachmentUrl: rentals.attachmentUrl,
  createdById: rentals.createdById,
  createdAt: rentals.createdAt,
};

export const listRentals = async (query: RentalQuery) => {
  const page = Math.max(1, query.page || DEFAULT_PAGE);
  const limit = Math.max(1, query.limit || DEFAULT_LIMIT);
  const offset = (page - 1) * limit;

  const conditions: any[] = [];
  if (query.status) conditions.push(eq(rentals.status, query.status as "reserved" | "rented" | "returned" | "overdue" | "cancelled"));
  if (query.paymentStatus) conditions.push(eq(rentals.paymentStatus, query.paymentStatus as "pending" | "partial" | "paid" | "refunded"));
  if (query.search) conditions.push(like(rentals.customerName, `%${query.search}%`));
  const where = conditions.length > 0 ? and(...conditions) : undefined;

  const data = await db
    .select({ ...baseColumns, productName: products.title })
    .from(rentals)
    .leftJoin(products, eq(rentals.productId, products.id))
    .where(where)
    .orderBy(desc(rentals.createdAt))
    .limit(limit)
    .offset(offset);

  const countResult = await db.select({ count: sql<number>`count(*)` }).from(rentals).where(where);
  const total = Number(countResult[0].count);
  return {
    data,
    pagination: { page, limit, total, totalPages: Math.ceil(total / limit) },
  };
};

export const getRental = async (id: number) => {
  const rows = await db
    .select({ ...baseColumns, productName: products.title, updatedAt: rentals.updatedAt })
    .from(rentals)
    .leftJoin(products, eq(rentals.productId, products.id))
    .where(eq(rentals.id, id))
    .limit(1);
  return rows[0] || null;
};

export const createRental = async (input: {
  rentalItem: string;
  productId?: number | null;
  customerName: string;
  phone: string;
  email?: string | null;
  userId?: number | null;
  quantity?: number;
  startDate: string;
  endDate: string;
  returnDate?: string | null;
  rateType?: string;
  dailyRate?: string | number;
  weeklyRate?: string | number;
  monthlyRate?: string | number;
  rate?: string | number;
  durationUnits?: number;
  securityDeposit?: string | number;
  discount?: string | number;
  additionalCharge?: string | number;
  totalAmount?: string | number;
  paymentStatus?: string;
  status?: string;
  notes?: string | null;
  attachmentUrl?: string | null;
  createdById?: number | null;
}) => {
  const [inserted] = await db.insert(rentals).values({
    rentalItem: input.rentalItem,
    productId: input.productId || null,
    customerName: input.customerName,
    phone: input.phone,
    email: input.email || null,
    userId: input.userId || null,
    quantity: input.quantity ?? 1,
    startDate: sql`STR_TO_DATE(${input.startDate}, '%Y-%m-%d %H:%i:%s')`,
    endDate: sql`STR_TO_DATE(${input.endDate}, '%Y-%m-%d %H:%i:%s')`,
    returnDate: input.returnDate ? sql`STR_TO_DATE(${input.returnDate}, '%Y-%m-%d %H:%i:%s')` : null,
    rateType: (input.rateType as "daily" | "weekly" | "monthly") || "daily",
    dailyRate: String(input.dailyRate ?? 0),
    weeklyRate: String(input.weeklyRate ?? 0),
    monthlyRate: String(input.monthlyRate ?? 0),
    rate: String(input.rate ?? 0),
    durationUnits: input.durationUnits ?? 0,
    securityDeposit: String(input.securityDeposit ?? 0),
    discount: String(input.discount ?? 0),
    additionalCharge: String(input.additionalCharge ?? 0),
    totalAmount: String(input.totalAmount ?? 0),
    paymentStatus: (input.paymentStatus as "pending" | "partial" | "paid" | "refunded") || "pending",
    status: (input.status as "reserved" | "rented" | "returned" | "overdue" | "cancelled") || "reserved",
    notes: input.notes || null,
    attachmentUrl: input.attachmentUrl || null,
    createdById: input.createdById || null,
  });
  return inserted;
};

export const updateRental = async (id: number, input: Record<string, unknown>) => {
  const existing = await getRental(id);
  if (!existing) throw new AppError(404, "Rental not found");
  await db
    .update(rentals)
    .set({
      rentalItem: input.rentalItem !== undefined ? String(input.rentalItem) : existing.rentalItem,
      productId: input.productId !== undefined ? (Number(input.productId) || null) : existing.productId,
      customerName: input.customerName !== undefined ? String(input.customerName) : existing.customerName,
      phone: input.phone !== undefined ? String(input.phone) : existing.phone,
      email: input.email !== undefined ? (input.email as string | null) : existing.email,
      userId: input.userId !== undefined ? (Number(input.userId) || null) : existing.userId,
      quantity: input.quantity !== undefined ? Number(input.quantity) : existing.quantity,
      startDate: input.startDate !== undefined ? sql`STR_TO_DATE(${input.startDate}, '%Y-%m-%d %H:%i:%s')` : existing.startDate,
      endDate: input.endDate !== undefined ? sql`STR_TO_DATE(${input.endDate}, '%Y-%m-%d %H:%i:%s')` : existing.endDate,
      returnDate: input.returnDate !== undefined ? (input.returnDate ? sql`STR_TO_DATE(${input.returnDate}, '%Y-%m-%d %H:%i:%s')` : null) : existing.returnDate,
      rateType: input.rateType !== undefined ? (input.rateType as "daily" | "weekly" | "monthly") : existing.rateType,
      dailyRate: input.dailyRate !== undefined ? String(input.dailyRate) : existing.dailyRate,
      weeklyRate: input.weeklyRate !== undefined ? String(input.weeklyRate) : existing.weeklyRate,
      monthlyRate: input.monthlyRate !== undefined ? String(input.monthlyRate) : existing.monthlyRate,
      rate: input.rate !== undefined ? String(input.rate) : existing.rate,
      durationUnits: input.durationUnits !== undefined ? Number(input.durationUnits) : existing.durationUnits,
      securityDeposit: input.securityDeposit !== undefined ? String(input.securityDeposit) : existing.securityDeposit,
      discount: input.discount !== undefined ? String(input.discount) : existing.discount,
      additionalCharge: input.additionalCharge !== undefined ? String(input.additionalCharge) : existing.additionalCharge,
      totalAmount: input.totalAmount !== undefined ? String(input.totalAmount) : existing.totalAmount,
      paymentStatus: input.paymentStatus !== undefined ? (input.paymentStatus as "pending" | "partial" | "paid" | "refunded") : existing.paymentStatus,
      status: input.status !== undefined ? (input.status as "reserved" | "rented" | "returned" | "overdue" | "cancelled") : existing.status,
      notes: input.notes !== undefined ? (input.notes as string | null) : existing.notes,
      attachmentUrl: input.attachmentUrl !== undefined ? (input.attachmentUrl as string | null) : existing.attachmentUrl,
      createdById: input.createdById !== undefined ? (Number(input.createdById) || null) : existing.createdById,
    })
    .where(eq(rentals.id, id));
  return getRental(id);
};

export const deleteRental = async (id: number) => {
  const existing = await getRental(id);
  if (!existing) throw new AppError(404, "Rental not found");
  await db.delete(rentals).where(eq(rentals.id, id));
  return { success: true };
};
