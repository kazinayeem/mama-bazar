"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.deleteManyMemos = exports.deleteMemo = exports.createMemo = exports.getMemo = exports.listMemos = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const AppError_1 = require("../../utils/AppError");
const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 20;
const baseColumns = {
    id: schema_1.memos.id,
    title: schema_1.memos.title,
    entityType: schema_1.memos.entityType,
    entityId: schema_1.memos.entityId,
    url: schema_1.memos.url,
    publicId: schema_1.memos.publicId,
    filename: schema_1.memos.filename,
    mimeType: schema_1.memos.mimeType,
    size: schema_1.memos.size,
    folder: schema_1.memos.folder,
    notes: schema_1.memos.notes,
    uploadedById: schema_1.memos.uploadedById,
    createdAt: schema_1.memos.createdAt,
};
const listMemos = async (query) => {
    const page = Math.max(1, query.page || DEFAULT_PAGE);
    const limit = Math.max(1, query.limit || DEFAULT_LIMIT);
    const offset = (page - 1) * limit;
    const conditions = [];
    if (query.entityType)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.memos.entityType, query.entityType));
    if (query.folder)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.memos.folder, query.folder));
    if (query.search)
        conditions.push((0, drizzle_orm_1.like)(schema_1.memos.title, `%${query.search}%`));
    const where = conditions.length > 0 ? (0, drizzle_orm_1.and)(...conditions) : undefined;
    const data = await db_1.db
        .select({ ...baseColumns, uploadedByName: schema_1.users.name })
        .from(schema_1.memos)
        .leftJoin(schema_1.users, (0, drizzle_orm_1.eq)(schema_1.memos.uploadedById, schema_1.users.id))
        .where(where)
        .orderBy((0, drizzle_orm_1.desc)(schema_1.memos.createdAt))
        .limit(limit)
        .offset(offset);
    const countResult = await db_1.db.select({ count: (0, drizzle_orm_1.sql) `count(*)` }).from(schema_1.memos).where(where);
    const total = Number(countResult[0].count);
    return {
        data,
        pagination: { page, limit, total, totalPages: Math.ceil(total / limit) },
    };
};
exports.listMemos = listMemos;
const getMemo = async (id) => {
    const rows = await db_1.db
        .select({ ...baseColumns, uploadedByName: schema_1.users.name })
        .from(schema_1.memos)
        .leftJoin(schema_1.users, (0, drizzle_orm_1.eq)(schema_1.memos.uploadedById, schema_1.users.id))
        .where((0, drizzle_orm_1.eq)(schema_1.memos.id, id))
        .limit(1);
    return rows[0] || null;
};
exports.getMemo = getMemo;
const createMemo = async (input) => {
    const [inserted] = await db_1.db.insert(schema_1.memos).values({
        title: input.title,
        entityType: input.entityType,
        entityId: input.entityId || null,
        url: input.url,
        publicId: input.publicId,
        filename: input.filename,
        mimeType: input.mimeType,
        size: input.size ?? 0,
        folder: input.folder || "memos",
        notes: input.notes || null,
        uploadedById: input.uploadedById || null,
    });
    return inserted;
};
exports.createMemo = createMemo;
const deleteMemo = async (id) => {
    const existing = await (0, exports.getMemo)(id);
    if (!existing)
        throw new AppError_1.AppError(404, "Memo not found");
    await db_1.db.delete(schema_1.memos).where((0, drizzle_orm_1.eq)(schema_1.memos.id, id));
    return { success: true };
};
exports.deleteMemo = deleteMemo;
const deleteManyMemos = async (ids) => {
    if (ids.length === 0)
        return { success: true, deleted: 0 };
    await db_1.db.delete(schema_1.memos).where((0, drizzle_orm_1.inArray)(schema_1.memos.id, ids));
    return { success: true, deleted: ids.length };
};
exports.deleteManyMemos = deleteManyMemos;
//# sourceMappingURL=memo.service.js.map