"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.remove = exports.update = exports.create = exports.getById = exports.getAll = exports.getActive = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const AppError_1 = require("../../utils/AppError");
const getActive = async () => {
    return db_1.db
        .select()
        .from(schema_1.checkoutNotices)
        .where((0, drizzle_orm_1.eq)(schema_1.checkoutNotices.status, "active"))
        .orderBy((0, drizzle_orm_1.asc)(schema_1.checkoutNotices.priority), (0, drizzle_orm_1.asc)(schema_1.checkoutNotices.id));
};
exports.getActive = getActive;
const getAll = async () => {
    return db_1.db.select().from(schema_1.checkoutNotices).orderBy((0, drizzle_orm_1.asc)(schema_1.checkoutNotices.priority), (0, drizzle_orm_1.asc)(schema_1.checkoutNotices.id));
};
exports.getAll = getAll;
const getById = async (id) => {
    const rows = await db_1.db.select().from(schema_1.checkoutNotices).where((0, drizzle_orm_1.eq)(schema_1.checkoutNotices.id, id)).limit(1);
    return rows[0] || null;
};
exports.getById = getById;
const create = async (data) => {
    const result = await db_1.db.insert(schema_1.checkoutNotices).values({
        text: data.text,
        priority: data.priority ?? 0,
        backgroundColor: data.backgroundColor ?? "#FFF7ED",
        textColor: data.textColor ?? "#9A3412",
        icon: data.icon ?? "alert",
        status: data.status ?? "active",
    });
    return (0, exports.getById)(result[0].insertId);
};
exports.create = create;
const update = async (id, data) => {
    const existing = await (0, exports.getById)(id);
    if (!existing)
        throw new AppError_1.AppError(404, "Checkout notice not found");
    await db_1.db.update(schema_1.checkoutNotices).set({ ...data }).where((0, drizzle_orm_1.eq)(schema_1.checkoutNotices.id, id));
    return (0, exports.getById)(id);
};
exports.update = update;
const remove = async (id) => {
    await db_1.db.delete(schema_1.checkoutNotices).where((0, drizzle_orm_1.eq)(schema_1.checkoutNotices.id, id));
    return { success: true };
};
exports.remove = remove;
//# sourceMappingURL=checkout-notice.service.js.map