"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.remove = exports.setStatuses = exports.update = exports.create = exports.getById = exports.getAll = exports.getByCode = exports.getActive = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const AppError_1 = require("../../utils/AppError");
const toNum = (v, fallback = 0) => {
    const n = Number(v);
    return Number.isFinite(n) ? n : fallback;
};
const publicFields = (m) => ({
    id: m.id,
    code: m.code,
    name: m.name,
    type: m.type,
    config: typeof m.config === "string" ? JSON.parse(m.config || "{}") : (m.config ?? {}),
});
const getActive = async () => {
    const rows = await db_1.db
        .select()
        .from(schema_1.paymentMethods)
        .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.paymentMethods.enabled, true), (0, drizzle_orm_1.eq)(schema_1.paymentMethods.maintenanceMode, false)))
        .orderBy((0, drizzle_orm_1.asc)(schema_1.paymentMethods.sortOrder), (0, drizzle_orm_1.asc)(schema_1.paymentMethods.id));
    return rows.map(publicFields);
};
exports.getActive = getActive;
const getByCode = async (code) => {
    const rows = await db_1.db.select().from(schema_1.paymentMethods).where((0, drizzle_orm_1.eq)(schema_1.paymentMethods.code, code)).limit(1);
    return rows[0] || null;
};
exports.getByCode = getByCode;
const getAll = async () => {
    const rows = await db_1.db.select().from(schema_1.paymentMethods).orderBy((0, drizzle_orm_1.asc)(schema_1.paymentMethods.sortOrder), (0, drizzle_orm_1.asc)(schema_1.paymentMethods.id));
    return rows.map((m) => ({ ...m, config: typeof m.config === "string" ? JSON.parse(m.config || "{}") : (m.config ?? {}) }));
};
exports.getAll = getAll;
const getById = async (id) => {
    const rows = await db_1.db.select().from(schema_1.paymentMethods).where((0, drizzle_orm_1.eq)(schema_1.paymentMethods.id, id)).limit(1);
    return rows[0] || null;
};
exports.getById = getById;
const create = async (data) => {
    const result = await db_1.db.insert(schema_1.paymentMethods).values({
        code: data.code,
        name: data.name,
        type: data.type,
        enabled: data.enabled ?? true,
        sortOrder: data.sortOrder ?? 0,
        maintenanceMode: data.maintenanceMode ?? false,
        config: data.config ?? {},
    });
    return (0, exports.getById)(result[0].insertId);
};
exports.create = create;
const update = async (id, data) => {
    const existing = await (0, exports.getById)(id);
    if (!existing)
        throw new AppError_1.AppError(404, "Payment method not found");
    const updateData = {};
    if (data.code !== undefined)
        updateData.code = data.code;
    if (data.name !== undefined)
        updateData.name = data.name;
    if (data.type !== undefined)
        updateData.type = data.type;
    if (data.enabled !== undefined)
        updateData.enabled = data.enabled;
    if (data.sortOrder !== undefined)
        updateData.sortOrder = data.sortOrder;
    if (data.maintenanceMode !== undefined)
        updateData.maintenanceMode = data.maintenanceMode;
    if (data.config !== undefined)
        updateData.config = data.config;
    await db_1.db.update(schema_1.paymentMethods).set(updateData).where((0, drizzle_orm_1.eq)(schema_1.paymentMethods.id, id));
    return (0, exports.getById)(id);
};
exports.update = update;
const setStatuses = async (ids, enabled) => {
    for (const id of ids) {
        await db_1.db.update(schema_1.paymentMethods).set({ enabled }).where((0, drizzle_orm_1.eq)(schema_1.paymentMethods.id, id));
    }
    return { success: true };
};
exports.setStatuses = setStatuses;
const remove = async (id) => {
    await db_1.db.delete(schema_1.paymentMethods).where((0, drizzle_orm_1.eq)(schema_1.paymentMethods.id, id));
    return { success: true };
};
exports.remove = remove;
//# sourceMappingURL=payment.service.js.map