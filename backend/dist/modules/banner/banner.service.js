"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.remove = exports.update = exports.create = exports.getById = exports.getAll = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const getAll = async () => {
    return db_1.db.select().from(schema_1.banners).orderBy((0, drizzle_orm_1.desc)(schema_1.banners.updatedAt));
};
exports.getAll = getAll;
const getById = async (id) => {
    const rows = await db_1.db.select().from(schema_1.banners).where((0, drizzle_orm_1.eq)(schema_1.banners.id, id)).limit(1);
    return rows[0] || null;
};
exports.getById = getById;
const create = async (data) => {
    const result = await db_1.db.insert(schema_1.banners).values(data);
    return (0, exports.getById)(result[0].insertId);
};
exports.create = create;
const update = async (id, data) => {
    await db_1.db.update(schema_1.banners).set({ ...data, updatedAt: new Date() }).where((0, drizzle_orm_1.eq)(schema_1.banners.id, id));
    return (0, exports.getById)(id);
};
exports.update = update;
const remove = async (id) => {
    await db_1.db.delete(schema_1.banners).where((0, drizzle_orm_1.eq)(schema_1.banners.id, id));
    return { success: true };
};
exports.remove = remove;
//# sourceMappingURL=banner.service.js.map