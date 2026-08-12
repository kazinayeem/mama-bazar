"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.deleteRental = exports.updateRental = exports.createRental = exports.getRental = exports.listRentals = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const AppError_1 = require("../../utils/AppError");
const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 20;
const baseColumns = {
    id: schema_1.rentals.id,
    rentalItem: schema_1.rentals.rentalItem,
    productId: schema_1.rentals.productId,
    customerName: schema_1.rentals.customerName,
    phone: schema_1.rentals.phone,
    email: schema_1.rentals.email,
    userId: schema_1.rentals.userId,
    quantity: schema_1.rentals.quantity,
    startDate: schema_1.rentals.startDate,
    endDate: schema_1.rentals.endDate,
    returnDate: schema_1.rentals.returnDate,
    rateType: schema_1.rentals.rateType,
    dailyRate: schema_1.rentals.dailyRate,
    weeklyRate: schema_1.rentals.weeklyRate,
    monthlyRate: schema_1.rentals.monthlyRate,
    rate: schema_1.rentals.rate,
    durationUnits: schema_1.rentals.durationUnits,
    securityDeposit: schema_1.rentals.securityDeposit,
    discount: schema_1.rentals.discount,
    additionalCharge: schema_1.rentals.additionalCharge,
    totalAmount: schema_1.rentals.totalAmount,
    paymentStatus: schema_1.rentals.paymentStatus,
    status: schema_1.rentals.status,
    notes: schema_1.rentals.notes,
    attachmentUrl: schema_1.rentals.attachmentUrl,
    createdById: schema_1.rentals.createdById,
    createdAt: schema_1.rentals.createdAt,
};
const listRentals = async (query) => {
    const page = Math.max(1, query.page || DEFAULT_PAGE);
    const limit = Math.max(1, query.limit || DEFAULT_LIMIT);
    const offset = (page - 1) * limit;
    const conditions = [];
    if (query.status)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.rentals.status, query.status));
    if (query.paymentStatus)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.rentals.paymentStatus, query.paymentStatus));
    if (query.search)
        conditions.push((0, drizzle_orm_1.like)(schema_1.rentals.customerName, `%${query.search}%`));
    const where = conditions.length > 0 ? (0, drizzle_orm_1.and)(...conditions) : undefined;
    const data = await db_1.db
        .select({ ...baseColumns, productName: schema_1.products.title })
        .from(schema_1.rentals)
        .leftJoin(schema_1.products, (0, drizzle_orm_1.eq)(schema_1.rentals.productId, schema_1.products.id))
        .where(where)
        .orderBy((0, drizzle_orm_1.desc)(schema_1.rentals.createdAt))
        .limit(limit)
        .offset(offset);
    const countResult = await db_1.db.select({ count: (0, drizzle_orm_1.sql) `count(*)` }).from(schema_1.rentals).where(where);
    const total = Number(countResult[0].count);
    return {
        data,
        pagination: { page, limit, total, totalPages: Math.ceil(total / limit) },
    };
};
exports.listRentals = listRentals;
const getRental = async (id) => {
    const rows = await db_1.db
        .select({ ...baseColumns, productName: schema_1.products.title, updatedAt: schema_1.rentals.updatedAt })
        .from(schema_1.rentals)
        .leftJoin(schema_1.products, (0, drizzle_orm_1.eq)(schema_1.rentals.productId, schema_1.products.id))
        .where((0, drizzle_orm_1.eq)(schema_1.rentals.id, id))
        .limit(1);
    return rows[0] || null;
};
exports.getRental = getRental;
const createRental = async (input) => {
    const [inserted] = await db_1.db.insert(schema_1.rentals).values({
        rentalItem: input.rentalItem,
        productId: input.productId || null,
        customerName: input.customerName,
        phone: input.phone,
        email: input.email || null,
        userId: input.userId || null,
        quantity: input.quantity ?? 1,
        startDate: (0, drizzle_orm_1.sql) `STR_TO_DATE(${input.startDate}, '%Y-%m-%d %H:%i:%s')`,
        endDate: (0, drizzle_orm_1.sql) `STR_TO_DATE(${input.endDate}, '%Y-%m-%d %H:%i:%s')`,
        returnDate: input.returnDate ? (0, drizzle_orm_1.sql) `STR_TO_DATE(${input.returnDate}, '%Y-%m-%d %H:%i:%s')` : null,
        rateType: input.rateType || "daily",
        dailyRate: String(input.dailyRate ?? 0),
        weeklyRate: String(input.weeklyRate ?? 0),
        monthlyRate: String(input.monthlyRate ?? 0),
        rate: String(input.rate ?? 0),
        durationUnits: input.durationUnits ?? 0,
        securityDeposit: String(input.securityDeposit ?? 0),
        discount: String(input.discount ?? 0),
        additionalCharge: String(input.additionalCharge ?? 0),
        totalAmount: String(input.totalAmount ?? 0),
        paymentStatus: input.paymentStatus || "pending",
        status: input.status || "reserved",
        notes: input.notes || null,
        attachmentUrl: input.attachmentUrl || null,
        createdById: input.createdById || null,
    });
    return inserted;
};
exports.createRental = createRental;
const updateRental = async (id, input) => {
    const existing = await (0, exports.getRental)(id);
    if (!existing)
        throw new AppError_1.AppError(404, "Rental not found");
    await db_1.db
        .update(schema_1.rentals)
        .set({
        rentalItem: input.rentalItem !== undefined ? String(input.rentalItem) : existing.rentalItem,
        productId: input.productId !== undefined ? (Number(input.productId) || null) : existing.productId,
        customerName: input.customerName !== undefined ? String(input.customerName) : existing.customerName,
        phone: input.phone !== undefined ? String(input.phone) : existing.phone,
        email: input.email !== undefined ? input.email : existing.email,
        userId: input.userId !== undefined ? (Number(input.userId) || null) : existing.userId,
        quantity: input.quantity !== undefined ? Number(input.quantity) : existing.quantity,
        startDate: input.startDate !== undefined ? (0, drizzle_orm_1.sql) `STR_TO_DATE(${input.startDate}, '%Y-%m-%d %H:%i:%s')` : existing.startDate,
        endDate: input.endDate !== undefined ? (0, drizzle_orm_1.sql) `STR_TO_DATE(${input.endDate}, '%Y-%m-%d %H:%i:%s')` : existing.endDate,
        returnDate: input.returnDate !== undefined ? (input.returnDate ? (0, drizzle_orm_1.sql) `STR_TO_DATE(${input.returnDate}, '%Y-%m-%d %H:%i:%s')` : null) : existing.returnDate,
        rateType: input.rateType !== undefined ? input.rateType : existing.rateType,
        dailyRate: input.dailyRate !== undefined ? String(input.dailyRate) : existing.dailyRate,
        weeklyRate: input.weeklyRate !== undefined ? String(input.weeklyRate) : existing.weeklyRate,
        monthlyRate: input.monthlyRate !== undefined ? String(input.monthlyRate) : existing.monthlyRate,
        rate: input.rate !== undefined ? String(input.rate) : existing.rate,
        durationUnits: input.durationUnits !== undefined ? Number(input.durationUnits) : existing.durationUnits,
        securityDeposit: input.securityDeposit !== undefined ? String(input.securityDeposit) : existing.securityDeposit,
        discount: input.discount !== undefined ? String(input.discount) : existing.discount,
        additionalCharge: input.additionalCharge !== undefined ? String(input.additionalCharge) : existing.additionalCharge,
        totalAmount: input.totalAmount !== undefined ? String(input.totalAmount) : existing.totalAmount,
        paymentStatus: input.paymentStatus !== undefined ? input.paymentStatus : existing.paymentStatus,
        status: input.status !== undefined ? input.status : existing.status,
        notes: input.notes !== undefined ? input.notes : existing.notes,
        attachmentUrl: input.attachmentUrl !== undefined ? input.attachmentUrl : existing.attachmentUrl,
        createdById: input.createdById !== undefined ? (Number(input.createdById) || null) : existing.createdById,
    })
        .where((0, drizzle_orm_1.eq)(schema_1.rentals.id, id));
    return (0, exports.getRental)(id);
};
exports.updateRental = updateRental;
const deleteRental = async (id) => {
    const existing = await (0, exports.getRental)(id);
    if (!existing)
        throw new AppError_1.AppError(404, "Rental not found");
    await db_1.db.delete(schema_1.rentals).where((0, drizzle_orm_1.eq)(schema_1.rentals.id, id));
    return { success: true };
};
exports.deleteRental = deleteRental;
//# sourceMappingURL=rental.service.js.map