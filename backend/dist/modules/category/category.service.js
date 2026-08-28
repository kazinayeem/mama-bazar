"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.remove = exports.moveProducts = exports.update = exports.create = exports.getUsage = exports.getBySlug = exports.getById = exports.getTree = exports.getAllFlat = exports.getAll = void 0;
const drizzle_orm_1 = require("drizzle-orm");
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const AppError_1 = require("../../utils/AppError");
const buildWhere = (params) => {
    const conditions = [];
    if (params.search) {
        conditions.push((0, drizzle_orm_1.like)(schema_1.categories.name, `%${params.search}%`));
    }
    if (params.status && ["active", "inactive", "archived"].includes(params.status)) {
        conditions.push((0, drizzle_orm_1.eq)(schema_1.categories.status, params.status));
    }
    if (params.parentId === "root") {
        conditions.push((0, drizzle_orm_1.sql) `${schema_1.categories.parentId} IS NULL`);
    }
    else if (params.parentId) {
        conditions.push((0, drizzle_orm_1.eq)(schema_1.categories.parentId, Number(params.parentId)));
    }
    if (params.featured !== undefined) {
        conditions.push((0, drizzle_orm_1.eq)(schema_1.categories.featured, params.featured));
    }
    return conditions.length ? (0, drizzle_orm_1.and)(...conditions) : undefined;
};
const getAll = async (params = {}) => {
    const page = Math.max(1, params.page || 1);
    const limit = Math.min(100, Math.max(1, params.limit || 20));
    const where = buildWhere(params);
    const orderBy = params.sort === "name"
        ? [(0, drizzle_orm_1.asc)(schema_1.categories.name)]
        : params.sort === "oldest"
            ? [(0, drizzle_orm_1.asc)(schema_1.categories.createdAt)]
            : [(0, drizzle_orm_1.asc)(schema_1.categories.sortOrder), (0, drizzle_orm_1.desc)(schema_1.categories.createdAt)];
    const [rows, totalRows] = await Promise.all([
        db_1.db.select().from(schema_1.categories).where(where).orderBy(...orderBy).limit(limit).offset((page - 1) * limit),
        db_1.db.select({ count: (0, drizzle_orm_1.count)() }).from(schema_1.categories).where(where),
    ]);
    const total = totalRows[0]?.count ?? 0;
    return {
        data: rows,
        pagination: { page, limit, total, totalPages: Math.max(1, Math.ceil(total / limit)) },
    };
};
exports.getAll = getAll;
const getAllFlat = async () => {
    return db_1.db
        .select()
        .from(schema_1.categories)
        .where((0, drizzle_orm_1.eq)(schema_1.categories.status, "active"))
        .orderBy((0, drizzle_orm_1.asc)(schema_1.categories.sortOrder), (0, drizzle_orm_1.asc)(schema_1.categories.name));
};
exports.getAllFlat = getAllFlat;
const getTree = async () => {
    const rows = await db_1.db
        .select()
        .from(schema_1.categories)
        .where((0, drizzle_orm_1.eq)(schema_1.categories.status, "active"))
        .orderBy((0, drizzle_orm_1.asc)(schema_1.categories.sortOrder), (0, drizzle_orm_1.asc)(schema_1.categories.name));
    const map = new Map();
    rows.forEach((row) => map.set(row.id, { ...row, children: [] }));
    const roots = [];
    map.forEach((node) => {
        const parent = node.parentId != null ? map.get(node.parentId) : undefined;
        if (parent)
            parent.children.push(node);
        else
            roots.push(node);
    });
    return roots;
};
exports.getTree = getTree;
const getById = async (id) => {
    const rows = await db_1.db.select().from(schema_1.categories).where((0, drizzle_orm_1.eq)(schema_1.categories.id, id)).limit(1);
    return rows[0] || null;
};
exports.getById = getById;
const getBySlug = async (slug) => {
    const rows = await db_1.db.select().from(schema_1.categories).where((0, drizzle_orm_1.eq)(schema_1.categories.slug, slug)).limit(1);
    return rows[0] || null;
};
exports.getBySlug = getBySlug;
const getUsage = async (id) => {
    const [productRows, childRows] = await Promise.all([
        db_1.db
            .select({ count: (0, drizzle_orm_1.count)() })
            .from(schema_1.products)
            .where((0, drizzle_orm_1.or)((0, drizzle_orm_1.eq)(schema_1.products.categoryId, id), (0, drizzle_orm_1.eq)(schema_1.products.subCategoryId, id), (0, drizzle_orm_1.eq)(schema_1.products.childCategoryId, id))),
        db_1.db.select({ count: (0, drizzle_orm_1.count)() }).from(schema_1.categories).where((0, drizzle_orm_1.eq)(schema_1.categories.parentId, id)),
    ]);
    return { products: productRows[0]?.count ?? 0, subCategories: childRows[0]?.count ?? 0 };
};
exports.getUsage = getUsage;
const cache_1 = require("../../utils/cache");
const create = async (data) => {
    const result = await db_1.db.insert(schema_1.categories).values(data);
    cache_1.memoryCache.invalidate("cat_slug");
    cache_1.memoryCache.invalidate("homepage_config");
    return (0, exports.getById)(result[0].insertId);
};
exports.create = create;
const update = async (id, data) => {
    if (data.parentId === id)
        throw new AppError_1.AppError(400, "A category cannot be its own parent");
    await db_1.db.update(schema_1.categories).set(data).where((0, drizzle_orm_1.eq)(schema_1.categories.id, id));
    cache_1.memoryCache.invalidate("cat_slug");
    cache_1.memoryCache.invalidate("homepage_config");
    return (0, exports.getById)(id);
};
exports.update = update;
const moveProducts = async (fromId, targetId) => {
    const usage = await (0, exports.getUsage)(fromId);
    const moved = await db_1.db
        .update(schema_1.products)
        .set({
        categoryId: targetId,
        ...(targetId === null
            ? { subCategoryId: null, childCategoryId: null }
            : { subCategoryId: null, childCategoryId: null }),
    })
        .where((0, drizzle_orm_1.or)((0, drizzle_orm_1.eq)(schema_1.products.categoryId, fromId), (0, drizzle_orm_1.eq)(schema_1.products.subCategoryId, fromId), (0, drizzle_orm_1.eq)(schema_1.products.childCategoryId, fromId)));
    cache_1.memoryCache.invalidate("cat_slug");
    return { moved: moved[0].affectedRows, usage };
};
exports.moveProducts = moveProducts;
const remove = async (id) => {
    await db_1.db.delete(schema_1.categories).where((0, drizzle_orm_1.eq)(schema_1.categories.id, id));
    cache_1.memoryCache.invalidate("cat_slug");
    cache_1.memoryCache.invalidate("homepage_config");
    return { success: true };
};
exports.remove = remove;
//# sourceMappingURL=category.service.js.map