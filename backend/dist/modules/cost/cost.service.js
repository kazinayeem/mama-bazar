"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.deleteCost = exports.updateCost = exports.createCost = exports.getCost = exports.listCosts = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const AppError_1 = require("../../utils/AppError");
const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 20;
const listCosts = async (query) => {
    const page = Math.max(1, query.page || DEFAULT_PAGE);
    const limit = Math.max(1, query.limit || DEFAULT_LIMIT);
    const offset = (page - 1) * limit;
    const conditions = [];
    if (query.costType)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.costs.costType, query.costType));
    if (query.search)
        conditions.push((0, drizzle_orm_1.like)(schema_1.costs.title, `%${query.search}%`));
    const where = conditions.length > 0 ? (0, drizzle_orm_1.and)(...conditions) : undefined;
    const data = await db_1.db
        .select({
        id: schema_1.costs.id,
        title: schema_1.costs.title,
        costType: schema_1.costs.costType,
        quantity: schema_1.costs.quantity,
        unitCost: schema_1.costs.unitCost,
        totalCost: schema_1.costs.totalCost,
        supplierName: schema_1.suppliers.name,
        productName: schema_1.products.title,
        orderOrderId: schema_1.costs.orderId,
        bookingId: schema_1.costs.bookingId,
        costDate: schema_1.costs.costDate,
        paymentMethod: schema_1.costs.paymentMethod,
        notes: schema_1.costs.notes,
        attachmentUrl: schema_1.costs.attachmentUrl,
        createdAt: schema_1.costs.createdAt,
    })
        .from(schema_1.costs)
        .leftJoin(schema_1.suppliers, (0, drizzle_orm_1.eq)(schema_1.costs.supplierId, schema_1.suppliers.id))
        .leftJoin(schema_1.products, (0, drizzle_orm_1.eq)(schema_1.costs.productId, schema_1.products.id))
        .leftJoin(schema_1.orders, (0, drizzle_orm_1.eq)(schema_1.costs.orderId, schema_1.orders.id))
        .leftJoin(schema_1.bookings, (0, drizzle_orm_1.eq)(schema_1.costs.bookingId, schema_1.bookings.id))
        .where(where)
        .orderBy((0, drizzle_orm_1.desc)(schema_1.costs.costDate))
        .limit(limit)
        .offset(offset);
    const countResult = await db_1.db.select({ count: (0, drizzle_orm_1.sql) `count(*)` }).from(schema_1.costs).where(where);
    const total = Number(countResult[0].count);
    return {
        data,
        pagination: { page, limit, total, totalPages: Math.ceil(total / limit) },
    };
};
exports.listCosts = listCosts;
const getCost = async (id) => {
    const rows = await db_1.db
        .select({
        id: schema_1.costs.id,
        title: schema_1.costs.title,
        costType: schema_1.costs.costType,
        quantity: schema_1.costs.quantity,
        unitCost: schema_1.costs.unitCost,
        totalCost: schema_1.costs.totalCost,
        supplierId: schema_1.costs.supplierId,
        supplierName: schema_1.suppliers.name,
        productId: schema_1.costs.productId,
        productName: schema_1.products.title,
        orderId: schema_1.costs.orderId,
        bookingId: schema_1.costs.bookingId,
        costDate: schema_1.costs.costDate,
        paymentMethod: schema_1.costs.paymentMethod,
        notes: schema_1.costs.notes,
        attachmentUrl: schema_1.costs.attachmentUrl,
        createdAt: schema_1.costs.createdAt,
        updatedAt: schema_1.costs.updatedAt,
    })
        .from(schema_1.costs)
        .leftJoin(schema_1.suppliers, (0, drizzle_orm_1.eq)(schema_1.costs.supplierId, schema_1.suppliers.id))
        .leftJoin(schema_1.products, (0, drizzle_orm_1.eq)(schema_1.costs.productId, schema_1.products.id))
        .leftJoin(schema_1.orders, (0, drizzle_orm_1.eq)(schema_1.costs.orderId, schema_1.orders.id))
        .leftJoin(schema_1.bookings, (0, drizzle_orm_1.eq)(schema_1.costs.bookingId, schema_1.bookings.id))
        .where((0, drizzle_orm_1.eq)(schema_1.costs.id, id))
        .limit(1);
    return rows[0] || null;
};
exports.getCost = getCost;
const createCost = async (input) => {
    const [inserted] = await db_1.db.insert(schema_1.costs).values({
        title: input.title,
        costType: input.costType || "operational",
        quantity: String(input.quantity ?? 1),
        unitCost: String(input.unitCost ?? 0),
        totalCost: String(input.totalCost ?? 0),
        supplierId: input.supplierId || null,
        productId: input.productId || null,
        orderId: input.orderId || null,
        bookingId: input.bookingId || null,
        costDate: (0, drizzle_orm_1.sql) `STR_TO_DATE(${input.costDate}, '%Y-%m-%d %H:%i:%s')`,
        paymentMethod: input.paymentMethod || "cash",
        notes: input.notes || null,
        attachmentUrl: input.attachmentUrl || null,
    });
    return inserted;
};
exports.createCost = createCost;
const updateCost = async (id, input) => {
    const existing = await (0, exports.getCost)(id);
    if (!existing)
        throw new AppError_1.AppError(404, "Cost not found");
    await db_1.db
        .update(schema_1.costs)
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
        costDate: input.costDate !== undefined ? (0, drizzle_orm_1.sql) `STR_TO_DATE(${input.costDate}, '%Y-%m-%d %H:%i:%s')` : existing.costDate,
        paymentMethod: input.paymentMethod !== undefined ? String(input.paymentMethod) : existing.paymentMethod,
        notes: input.notes !== undefined ? input.notes : existing.notes,
        attachmentUrl: input.attachmentUrl !== undefined ? input.attachmentUrl : existing.attachmentUrl,
    })
        .where((0, drizzle_orm_1.eq)(schema_1.costs.id, id));
    return (0, exports.getCost)(id);
};
exports.updateCost = updateCost;
const deleteCost = async (id) => {
    const existing = await (0, exports.getCost)(id);
    if (!existing)
        throw new AppError_1.AppError(404, "Cost not found");
    await db_1.db.delete(schema_1.costs).where((0, drizzle_orm_1.eq)(schema_1.costs.id, id));
    return { success: true };
};
exports.deleteCost = deleteCost;
//# sourceMappingURL=cost.service.js.map