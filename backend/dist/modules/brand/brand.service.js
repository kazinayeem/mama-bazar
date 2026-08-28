"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.remove = exports.moveProducts = exports.update = exports.create = exports.getUsage = exports.getBySlug = exports.getById = exports.getAllActive = exports.getAll = void 0;
const drizzle_orm_1 = require("drizzle-orm");
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const buildWhere = (params) => {
    const conditions = [];
    if (params.search)
        conditions.push((0, drizzle_orm_1.like)(schema_1.brands.name, `%${params.search}%`));
    if (params.status && ["active", "inactive", "archived"].includes(params.status)) {
        conditions.push((0, drizzle_orm_1.eq)(schema_1.brands.status, params.status));
    }
    if (params.featured !== undefined)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.brands.featured, params.featured));
    return conditions.length ? (0, drizzle_orm_1.and)(...conditions) : undefined;
};
const getAll = async (params = {}) => {
    const page = Math.max(1, params.page || 1);
    const limit = Math.min(100, Math.max(1, params.limit || 20));
    const where = buildWhere(params);
    const orderBy = params.sort === "name" || params.sort === "oldest"
        ? [(0, drizzle_orm_1.asc)(schema_1.brands.createdAt)]
        : [(0, drizzle_orm_1.asc)(schema_1.brands.sortOrder), (0, drizzle_orm_1.desc)(schema_1.brands.createdAt)];
    const [rows, totalRows] = await Promise.all([
        db_1.db.select().from(schema_1.brands).where(where).orderBy(...orderBy).limit(limit).offset((page - 1) * limit),
        db_1.db.select({ count: (0, drizzle_orm_1.count)() }).from(schema_1.brands).where(where),
    ]);
    const total = totalRows[0]?.count ?? 0;
    return {
        data: rows,
        pagination: { page, limit, total, totalPages: Math.max(1, Math.ceil(total / limit)) },
    };
};
exports.getAll = getAll;
const getAllActive = async () => {
    return db_1.db.select().from(schema_1.brands).where((0, drizzle_orm_1.eq)(schema_1.brands.status, "active")).orderBy((0, drizzle_orm_1.asc)(schema_1.brands.sortOrder), (0, drizzle_orm_1.asc)(schema_1.brands.name));
};
exports.getAllActive = getAllActive;
const getById = async (id) => {
    const rows = await db_1.db.select().from(schema_1.brands).where((0, drizzle_orm_1.eq)(schema_1.brands.id, id)).limit(1);
    return rows[0] || null;
};
exports.getById = getById;
const getBySlug = async (slug) => {
    const rows = await db_1.db.select().from(schema_1.brands).where((0, drizzle_orm_1.eq)(schema_1.brands.slug, slug)).limit(1);
    return rows[0] || null;
};
exports.getBySlug = getBySlug;
const getUsage = async (id) => {
    const rows = await db_1.db.select({ count: (0, drizzle_orm_1.count)() }).from(schema_1.products).where((0, drizzle_orm_1.eq)(schema_1.products.brandId, id));
    return rows[0]?.count ?? 0;
};
exports.getUsage = getUsage;
const cache_1 = require("../../utils/cache");
const create = async (data) => {
    const result = await db_1.db.insert(schema_1.brands).values(data);
    cache_1.memoryCache.invalidate("brand_slug");
    cache_1.memoryCache.invalidate("homepage_config");
    return (0, exports.getById)(result[0].insertId);
};
exports.create = create;
const update = async (id, data) => {
    await db_1.db.update(schema_1.brands).set(data).where((0, drizzle_orm_1.eq)(schema_1.brands.id, id));
    cache_1.memoryCache.invalidate("brand_slug");
    cache_1.memoryCache.invalidate("homepage_config");
    return (0, exports.getById)(id);
};
exports.update = update;
const moveProducts = async (fromId, targetId) => {
    const moved = await db_1.db.update(schema_1.products).set({ brandId: targetId }).where((0, drizzle_orm_1.eq)(schema_1.products.brandId, fromId));
    cache_1.memoryCache.invalidate("brand_slug");
    return { moved: moved[0].affectedRows };
};
exports.moveProducts = moveProducts;
const remove = async (id) => {
    await db_1.db.delete(schema_1.brands).where((0, drizzle_orm_1.eq)(schema_1.brands.id, id));
    cache_1.memoryCache.invalidate("brand_slug");
    cache_1.memoryCache.invalidate("homepage_config");
    return { success: true };
};
exports.remove = remove;
//# sourceMappingURL=brand.service.js.map