"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.remove = exports.update = exports.create = exports.getById = exports.getAll = exports.estimate = exports.getActive = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const AppError_1 = require("../../utils/AppError");
const toNum = (v, fallback = 0) => {
    const n = Number(v);
    return Number.isFinite(n) ? n : fallback;
};
const getActive = async () => {
    const rows = await db_1.db
        .select()
        .from(schema_1.shippingMethods)
        .where((0, drizzle_orm_1.eq)(schema_1.shippingMethods.status, "active"))
        .orderBy((0, drizzle_orm_1.asc)(schema_1.shippingMethods.priority), (0, drizzle_orm_1.asc)(schema_1.shippingMethods.id));
    return rows.map((m) => ({
        ...m,
        charge: toNum(m.charge),
        freeShippingMinAmount: m.freeShippingMinAmount === null ? null : toNum(m.freeShippingMinAmount),
    }));
};
exports.getActive = getActive;
const estimate = async (subtotal) => {
    const methods = await (0, exports.getActive)();
    return methods.map((m) => {
        let cost = m.charge;
        if (m.freeShippingMinAmount !== null && subtotal >= m.freeShippingMinAmount) {
            cost = 0;
        }
        return { ...m, estimatedCost: cost };
    });
};
exports.estimate = estimate;
const getAll = async () => {
    return db_1.db.select().from(schema_1.shippingMethods).orderBy((0, drizzle_orm_1.asc)(schema_1.shippingMethods.priority), (0, drizzle_orm_1.asc)(schema_1.shippingMethods.id));
};
exports.getAll = getAll;
const getById = async (id) => {
    const rows = await db_1.db.select().from(schema_1.shippingMethods).where((0, drizzle_orm_1.eq)(schema_1.shippingMethods.id, id)).limit(1);
    return rows[0] || null;
};
exports.getById = getById;
const create = async (data) => {
    const result = await db_1.db.insert(schema_1.shippingMethods).values({
        name: data.name,
        charge: String(data.charge),
        estimatedDelivery: data.estimatedDelivery ?? null,
        description: data.description ?? null,
        priority: data.priority ?? 0,
        freeShippingMinAmount: data.freeShippingMinAmount !== undefined ? String(data.freeShippingMinAmount) : null,
        codAvailable: data.codAvailable ?? true,
        status: data.status ?? "active",
    });
    return (0, exports.getById)(result[0].insertId);
};
exports.create = create;
const update = async (id, data) => {
    const existing = await (0, exports.getById)(id);
    if (!existing)
        throw new AppError_1.AppError(404, "Shipping method not found");
    const updateData = {};
    if (data.name !== undefined)
        updateData.name = data.name;
    if (data.charge !== undefined)
        updateData.charge = String(data.charge);
    if (data.estimatedDelivery !== undefined)
        updateData.estimatedDelivery = data.estimatedDelivery;
    if (data.description !== undefined)
        updateData.description = data.description;
    if (data.priority !== undefined)
        updateData.priority = data.priority;
    if (data.freeShippingMinAmount !== undefined) {
        updateData.freeShippingMinAmount =
            data.freeShippingMinAmount === null ? null : String(data.freeShippingMinAmount);
    }
    if (data.codAvailable !== undefined)
        updateData.codAvailable = data.codAvailable;
    if (data.status !== undefined)
        updateData.status = data.status;
    await db_1.db.update(schema_1.shippingMethods).set(updateData).where((0, drizzle_orm_1.eq)(schema_1.shippingMethods.id, id));
    return (0, exports.getById)(id);
};
exports.update = update;
const remove = async (id) => {
    await db_1.db.delete(schema_1.shippingMethods).where((0, drizzle_orm_1.eq)(schema_1.shippingMethods.id, id));
    return { success: true };
};
exports.remove = remove;
//# sourceMappingURL=shipping.service.js.map