"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.deleteBooking = exports.updateBooking = exports.createBooking = exports.getBooking = exports.listBookings = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const AppError_1 = require("../../utils/AppError");
const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 20;
const baseColumns = {
    id: schema_1.bookings.id,
    customerName: schema_1.bookings.customerName,
    phone: schema_1.bookings.phone,
    email: schema_1.bookings.email,
    userId: schema_1.bookings.userId,
    bookingType: schema_1.bookings.bookingType,
    service: schema_1.bookings.service,
    productId: schema_1.bookings.productId,
    startDate: schema_1.bookings.startDate,
    endDate: schema_1.bookings.endDate,
    quantity: schema_1.bookings.quantity,
    price: schema_1.bookings.price,
    discount: schema_1.bookings.discount,
    additionalCost: schema_1.bookings.additionalCost,
    totalAmount: schema_1.bookings.totalAmount,
    paymentStatus: schema_1.bookings.paymentStatus,
    status: schema_1.bookings.status,
    notes: schema_1.bookings.notes,
    attachmentUrl: schema_1.bookings.attachmentUrl,
    createdAt: schema_1.bookings.createdAt,
};
const listBookings = async (query) => {
    const page = Math.max(1, query.page || DEFAULT_PAGE);
    const limit = Math.max(1, query.limit || DEFAULT_LIMIT);
    const offset = (page - 1) * limit;
    const conditions = [];
    if (query.status)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.bookings.status, query.status));
    if (query.paymentStatus)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.bookings.paymentStatus, query.paymentStatus));
    if (query.search)
        conditions.push((0, drizzle_orm_1.like)(schema_1.bookings.customerName, `%${query.search}%`));
    const where = conditions.length > 0 ? (0, drizzle_orm_1.and)(...conditions) : undefined;
    const data = await db_1.db
        .select({ ...baseColumns, productName: schema_1.products.title })
        .from(schema_1.bookings)
        .leftJoin(schema_1.products, (0, drizzle_orm_1.eq)(schema_1.bookings.productId, schema_1.products.id))
        .where(where)
        .orderBy((0, drizzle_orm_1.desc)(schema_1.bookings.createdAt))
        .limit(limit)
        .offset(offset);
    const countResult = await db_1.db.select({ count: (0, drizzle_orm_1.sql) `count(*)` }).from(schema_1.bookings).where(where);
    const total = Number(countResult[0].count);
    return {
        data,
        pagination: { page, limit, total, totalPages: Math.ceil(total / limit) },
    };
};
exports.listBookings = listBookings;
const getBooking = async (id) => {
    const rows = await db_1.db
        .select({ ...baseColumns, productName: schema_1.products.title, createdAt: schema_1.bookings.createdAt, updatedAt: schema_1.bookings.updatedAt })
        .from(schema_1.bookings)
        .leftJoin(schema_1.products, (0, drizzle_orm_1.eq)(schema_1.bookings.productId, schema_1.products.id))
        .where((0, drizzle_orm_1.eq)(schema_1.bookings.id, id))
        .limit(1);
    return rows[0] || null;
};
exports.getBooking = getBooking;
const createBooking = async (input) => {
    const [inserted] = await db_1.db.insert(schema_1.bookings).values({
        customerName: input.customerName,
        phone: input.phone,
        email: input.email || null,
        userId: input.userId || null,
        bookingType: input.bookingType || "service",
        service: input.service || null,
        productId: input.productId || null,
        startDate: (0, drizzle_orm_1.sql) `STR_TO_DATE(${input.startDate}, '%Y-%m-%d %H:%i:%s')`,
        endDate: (0, drizzle_orm_1.sql) `STR_TO_DATE(${input.endDate}, '%Y-%m-%d %H:%i:%s')`,
        quantity: input.quantity ?? 1,
        price: String(input.price ?? 0),
        discount: String(input.discount ?? 0),
        additionalCost: String(input.additionalCost ?? 0),
        totalAmount: String(input.totalAmount ?? 0),
        paymentStatus: input.paymentStatus || "pending",
        status: input.status || "pending",
        notes: input.notes || null,
        attachmentUrl: input.attachmentUrl || null,
    });
    return inserted;
};
exports.createBooking = createBooking;
const updateBooking = async (id, input) => {
    const existing = await (0, exports.getBooking)(id);
    if (!existing)
        throw new AppError_1.AppError(404, "Booking not found");
    await db_1.db
        .update(schema_1.bookings)
        .set({
        customerName: input.customerName !== undefined ? String(input.customerName) : existing.customerName,
        phone: input.phone !== undefined ? String(input.phone) : existing.phone,
        email: input.email !== undefined ? input.email : existing.email,
        userId: input.userId !== undefined ? (Number(input.userId) || null) : existing.userId,
        bookingType: input.bookingType !== undefined ? String(input.bookingType) : existing.bookingType,
        service: input.service !== undefined ? input.service : existing.service,
        productId: input.productId !== undefined ? (Number(input.productId) || null) : existing.productId,
        startDate: input.startDate !== undefined ? (0, drizzle_orm_1.sql) `STR_TO_DATE(${input.startDate}, '%Y-%m-%d %H:%i:%s')` : existing.startDate,
        endDate: input.endDate !== undefined ? (0, drizzle_orm_1.sql) `STR_TO_DATE(${input.endDate}, '%Y-%m-%d %H:%i:%s')` : existing.endDate,
        quantity: input.quantity !== undefined ? Number(input.quantity) : existing.quantity,
        price: input.price !== undefined ? String(input.price) : existing.price,
        discount: input.discount !== undefined ? String(input.discount) : existing.discount,
        additionalCost: input.additionalCost !== undefined ? String(input.additionalCost) : existing.additionalCost,
        totalAmount: input.totalAmount !== undefined ? String(input.totalAmount) : existing.totalAmount,
        paymentStatus: input.paymentStatus !== undefined ? input.paymentStatus : existing.paymentStatus,
        status: input.status !== undefined ? input.status : existing.status,
        notes: input.notes !== undefined ? input.notes : existing.notes,
        attachmentUrl: input.attachmentUrl !== undefined ? input.attachmentUrl : existing.attachmentUrl,
    })
        .where((0, drizzle_orm_1.eq)(schema_1.bookings.id, id));
    return (0, exports.getBooking)(id);
};
exports.updateBooking = updateBooking;
const deleteBooking = async (id) => {
    const existing = await (0, exports.getBooking)(id);
    if (!existing)
        throw new AppError_1.AppError(404, "Booking not found");
    await db_1.db.delete(schema_1.bookings).where((0, drizzle_orm_1.eq)(schema_1.bookings.id, id));
    return { success: true };
};
exports.deleteBooking = deleteBooking;
//# sourceMappingURL=booking.service.js.map