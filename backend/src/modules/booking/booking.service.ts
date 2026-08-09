import { db } from "../../config/db";
import { bookings, products, users } from "../../config/schema";
import { eq, and, like, desc, sql } from "drizzle-orm";
import { AppError } from "../../utils/AppError";

const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 20;

export interface BookingQuery {
  page?: number;
  limit?: number;
  search?: string;
  status?: string;
  paymentStatus?: string;
}

const baseColumns = {
  id: bookings.id,
  customerName: bookings.customerName,
  phone: bookings.phone,
  email: bookings.email,
  userId: bookings.userId,
  bookingType: bookings.bookingType,
  service: bookings.service,
  productId: bookings.productId,
  startDate: bookings.startDate,
  endDate: bookings.endDate,
  quantity: bookings.quantity,
  price: bookings.price,
  discount: bookings.discount,
  additionalCost: bookings.additionalCost,
  totalAmount: bookings.totalAmount,
  paymentStatus: bookings.paymentStatus,
  status: bookings.status,
  notes: bookings.notes,
  attachmentUrl: bookings.attachmentUrl,
  createdAt: bookings.createdAt,
};

export const listBookings = async (query: BookingQuery) => {
  const page = Math.max(1, query.page || DEFAULT_PAGE);
  const limit = Math.max(1, query.limit || DEFAULT_LIMIT);
  const offset = (page - 1) * limit;

  const conditions: any[] = [];
  if (query.status) conditions.push(eq(bookings.status, query.status as "pending" | "confirmed" | "active" | "completed" | "cancelled"));
  if (query.paymentStatus) conditions.push(eq(bookings.paymentStatus, query.paymentStatus as "pending" | "partial" | "paid" | "refunded"));
  if (query.search) conditions.push(like(bookings.customerName, `%${query.search}%`));
  const where = conditions.length > 0 ? and(...conditions) : undefined;

  const data = await db
    .select({ ...baseColumns, productName: products.title })
    .from(bookings)
    .leftJoin(products, eq(bookings.productId, products.id))
    .where(where)
    .orderBy(desc(bookings.createdAt))
    .limit(limit)
    .offset(offset);

  const countResult = await db.select({ count: sql<number>`count(*)` }).from(bookings).where(where);
  const total = Number(countResult[0].count);
  return {
    data,
    pagination: { page, limit, total, totalPages: Math.ceil(total / limit) },
  };
};

export const getBooking = async (id: number) => {
  const rows = await db
    .select({ ...baseColumns, productName: products.title, createdAt: bookings.createdAt, updatedAt: bookings.updatedAt })
    .from(bookings)
    .leftJoin(products, eq(bookings.productId, products.id))
    .where(eq(bookings.id, id))
    .limit(1);
  return rows[0] || null;
};

export const createBooking = async (input: {
  customerName: string;
  phone: string;
  email?: string | null;
  userId?: number | null;
  bookingType?: string;
  service?: string | null;
  productId?: number | null;
  startDate: string;
  endDate: string;
  quantity?: number;
  price?: string | number;
  discount?: string | number;
  additionalCost?: string | number;
  totalAmount?: string | number;
  paymentStatus?: string;
  status?: string;
  notes?: string | null;
  attachmentUrl?: string | null;
}) => {
  const [inserted] = await db.insert(bookings).values({
    customerName: input.customerName,
    phone: input.phone,
    email: input.email || null,
    userId: input.userId || null,
    bookingType: input.bookingType || "service",
    service: input.service || null,
    productId: input.productId || null,
    startDate: sql`STR_TO_DATE(${input.startDate}, '%Y-%m-%d %H:%i:%s')`,
    endDate: sql`STR_TO_DATE(${input.endDate}, '%Y-%m-%d %H:%i:%s')`,
    quantity: input.quantity ?? 1,
    price: String(input.price ?? 0),
    discount: String(input.discount ?? 0),
    additionalCost: String(input.additionalCost ?? 0),
    totalAmount: String(input.totalAmount ?? 0),
    paymentStatus: (input.paymentStatus as "pending" | "partial" | "paid" | "refunded") || "pending",
    status: (input.status as "pending" | "confirmed" | "active" | "completed" | "cancelled") || "pending",
    notes: input.notes || null,
    attachmentUrl: input.attachmentUrl || null,
  });
  return inserted;
};

export const updateBooking = async (id: number, input: Record<string, unknown>) => {
  const existing = await getBooking(id);
  if (!existing) throw new AppError(404, "Booking not found");
  await db
    .update(bookings)
    .set({
      customerName: input.customerName !== undefined ? String(input.customerName) : existing.customerName,
      phone: input.phone !== undefined ? String(input.phone) : existing.phone,
      email: input.email !== undefined ? (input.email as string | null) : existing.email,
      userId: input.userId !== undefined ? (Number(input.userId) || null) : existing.userId,
      bookingType: input.bookingType !== undefined ? String(input.bookingType) : existing.bookingType,
      service: input.service !== undefined ? (input.service as string | null) : existing.service,
      productId: input.productId !== undefined ? (Number(input.productId) || null) : existing.productId,
      startDate: input.startDate !== undefined ? String(input.startDate) : existing.startDate,
      endDate: input.endDate !== undefined ? String(input.endDate) : existing.endDate,
      quantity: input.quantity !== undefined ? Number(input.quantity) : existing.quantity,
      price: input.price !== undefined ? String(input.price) : existing.price,
      discount: input.discount !== undefined ? String(input.discount) : existing.discount,
      additionalCost: input.additionalCost !== undefined ? String(input.additionalCost) : existing.additionalCost,
      totalAmount: input.totalAmount !== undefined ? String(input.totalAmount) : existing.totalAmount,
      paymentStatus: input.paymentStatus !== undefined ? String(input.paymentStatus) : existing.paymentStatus,
      status: input.status !== undefined ? String(input.status) : existing.status,
      notes: input.notes !== undefined ? (input.notes as string | null) : existing.notes,
      attachmentUrl: input.attachmentUrl !== undefined ? (input.attachmentUrl as string | null) : existing.attachmentUrl,
    })
    .where(eq(bookings.id, id));
  return getBooking(id);
};

export const deleteBooking = async (id: number) => {
  const existing = await getBooking(id);
  if (!existing) throw new AppError(404, "Booking not found");
  await db.delete(bookings).where(eq(bookings.id, id));
  return { success: true };
};
