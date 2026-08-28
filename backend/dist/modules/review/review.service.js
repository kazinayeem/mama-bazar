"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.remove = exports.updateStatus = exports.create = exports.getById = exports.getAll = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const cache_1 = require("../../utils/cache");
const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 12;
const getAll = async (query) => {
    const page = query.page || DEFAULT_PAGE;
    const limit = query.limit || DEFAULT_LIMIT;
    const offset = (page - 1) * limit;
    const cacheKey = `reviews:${query.productId || 'all'}:${query.status || 'all'}:${query.search || 'none'}:${page}:${limit}`;
    const cached = cache_1.memoryCache.get(cacheKey);
    if (cached)
        return cached;
    const conditions = [];
    if (query.status)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.reviews.status, query.status));
    if (query.productId)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.reviews.productId, query.productId));
    if (query.search)
        conditions.push((0, drizzle_orm_1.sql) `${schema_1.reviews.comment} LIKE ${`%${query.search}%`}`);
    const where = conditions.length > 0 ? (0, drizzle_orm_1.and)(...conditions) : undefined;
    const [rows, countResult] = await Promise.all([
        db_1.db
            .select({
            id: schema_1.reviews.id,
            productId: schema_1.reviews.productId,
            userId: schema_1.reviews.userId,
            customerName: schema_1.reviews.customerName,
            rating: schema_1.reviews.rating,
            title: schema_1.reviews.title,
            comment: schema_1.reviews.comment,
            status: schema_1.reviews.status,
            createdAt: schema_1.reviews.createdAt,
            productTitle: schema_1.products.title,
            productSlug: schema_1.products.slug,
            productImage: (0, drizzle_orm_1.sql) `JSON_UNQUOTE(JSON_EXTRACT(${schema_1.products.images}, '$[0]'))`,
        })
            .from(schema_1.reviews)
            .leftJoin(schema_1.products, (0, drizzle_orm_1.eq)(schema_1.reviews.productId, schema_1.products.id))
            .where(where)
            .orderBy((0, drizzle_orm_1.desc)(schema_1.reviews.createdAt))
            .limit(limit)
            .offset(offset),
        db_1.db.select({ count: (0, drizzle_orm_1.sql) `count(*)` }).from(schema_1.reviews).where(where),
    ]);
    const total = Number(countResult[0]?.count || 0);
    const result = {
        data: rows,
        pagination: { page, limit, total, totalPages: Math.ceil(total / limit) },
    };
    cache_1.memoryCache.set(cacheKey, result, 120);
    return result;
};
exports.getAll = getAll;
const getById = async (id) => {
    const rows = await db_1.db
        .select({
        id: schema_1.reviews.id,
        productId: schema_1.reviews.productId,
        userId: schema_1.reviews.userId,
        customerName: schema_1.reviews.customerName,
        rating: schema_1.reviews.rating,
        title: schema_1.reviews.title,
        comment: schema_1.reviews.comment,
        status: schema_1.reviews.status,
        createdAt: schema_1.reviews.createdAt,
        productTitle: schema_1.products.title,
        productSlug: schema_1.products.slug,
        customerPhone: schema_1.users.phone,
        customerRole: schema_1.users.role,
    })
        .from(schema_1.reviews)
        .leftJoin(schema_1.products, (0, drizzle_orm_1.eq)(schema_1.reviews.productId, schema_1.products.id))
        .leftJoin(schema_1.users, (0, drizzle_orm_1.eq)(schema_1.reviews.userId, schema_1.users.id))
        .where((0, drizzle_orm_1.eq)(schema_1.reviews.id, id))
        .limit(1);
    return rows[0] || null;
};
exports.getById = getById;
const create = async (data) => {
    const productRows = await db_1.db.select({ id: schema_1.products.id }).from(schema_1.products).where((0, drizzle_orm_1.eq)(schema_1.products.id, data.productId)).limit(1);
    if (!productRows[0])
        throw new Error("Product not found");
    if (data.userId) {
        const existing = await db_1.db
            .select({ id: schema_1.reviews.id })
            .from(schema_1.reviews)
            .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.reviews.productId, data.productId), (0, drizzle_orm_1.eq)(schema_1.reviews.userId, data.userId)))
            .limit(1);
        if (existing[0])
            throw new Error("You have already reviewed this product");
    }
    const result = await db_1.db.insert(schema_1.reviews).values({
        productId: data.productId,
        userId: data.userId ?? null,
        customerName: data.customerName || null,
        rating: Math.max(1, Math.min(5, data.rating)),
        title: data.title || null,
        comment: data.comment,
        status: "pending",
    });
    cache_1.memoryCache.invalidate("reviews:");
    cache_1.memoryCache.invalidate("rating:");
    const id = result[0].insertId;
    return (0, exports.getById)(id);
};
exports.create = create;
const updateStatus = async (id, status) => {
    const existing = await db_1.db.select({ id: schema_1.reviews.id }).from(schema_1.reviews).where((0, drizzle_orm_1.eq)(schema_1.reviews.id, id)).limit(1);
    if (!existing[0])
        throw new Error("Review not found");
    await db_1.db.update(schema_1.reviews).set({ status }).where((0, drizzle_orm_1.eq)(schema_1.reviews.id, id));
    cache_1.memoryCache.invalidate("reviews:");
    cache_1.memoryCache.invalidate("rating:");
    return (0, exports.getById)(id);
};
exports.updateStatus = updateStatus;
const remove = async (id) => {
    const result = await db_1.db.delete(schema_1.reviews).where((0, drizzle_orm_1.eq)(schema_1.reviews.id, id));
    if (!result[0].affectedRows)
        throw new Error("Review not found");
    cache_1.memoryCache.invalidate("reviews:");
    cache_1.memoryCache.invalidate("rating:");
    return { success: true };
};
exports.remove = remove;
//# sourceMappingURL=review.service.js.map