"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.updateAlt = exports.remove = exports.getById = exports.getFolders = exports.getAll = exports.saveMedia = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const cloud_1 = require("../../utils/cloud");
const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 48;
const mediaWithUploader = {
    id: schema_1.mediaAssets.id,
    url: schema_1.mediaAssets.url,
    publicId: schema_1.mediaAssets.publicId,
    filename: schema_1.mediaAssets.filename,
    mimeType: schema_1.mediaAssets.mimeType,
    size: schema_1.mediaAssets.size,
    width: schema_1.mediaAssets.width,
    height: schema_1.mediaAssets.height,
    provider: schema_1.mediaAssets.provider,
    folder: schema_1.mediaAssets.folder,
    alt: schema_1.mediaAssets.alt,
    createdAt: schema_1.mediaAssets.createdAt,
    uploaderName: schema_1.users.name,
};
const saveMedia = async (input) => {
    const result = await (0, cloud_1.uploadBuffer)(input.buffer, {
        folder: input.folder,
        filename: input.filename,
        mimeType: input.mimeType,
    });
    const insert = await db_1.db.insert(schema_1.mediaAssets).values({
        url: result.url,
        publicId: result.publicId,
        filename: input.filename,
        mimeType: input.mimeType,
        size: input.size,
        width: result.width,
        height: result.height,
        provider: result.provider,
        folder: input.folder,
        alt: input.alt,
        uploaderId: input.uploaderId,
    });
    const rows = await db_1.db
        .select(mediaWithUploader)
        .from(schema_1.mediaAssets)
        .leftJoin(schema_1.users, (0, drizzle_orm_1.eq)(schema_1.mediaAssets.uploaderId, schema_1.users.id))
        .where((0, drizzle_orm_1.eq)(schema_1.mediaAssets.id, insert[0].insertId))
        .limit(1);
    return rows[0] || null;
};
exports.saveMedia = saveMedia;
const getAll = async (query) => {
    const page = query.page || DEFAULT_PAGE;
    const limit = query.limit || DEFAULT_LIMIT;
    const offset = (page - 1) * limit;
    const conditions = [];
    if (query.folder)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.mediaAssets.folder, query.folder));
    if (query.search)
        conditions.push((0, drizzle_orm_1.like)(schema_1.mediaAssets.filename, `%${query.search}%`));
    const where = conditions.length > 0 ? (0, drizzle_orm_1.and)(...conditions) : undefined;
    const data = await db_1.db
        .select(mediaWithUploader)
        .from(schema_1.mediaAssets)
        .leftJoin(schema_1.users, (0, drizzle_orm_1.eq)(schema_1.mediaAssets.uploaderId, schema_1.users.id))
        .where(where)
        .orderBy((0, drizzle_orm_1.desc)(schema_1.mediaAssets.createdAt))
        .limit(limit)
        .offset(offset);
    const countResult = await db_1.db
        .select({ count: (0, drizzle_orm_1.sql) `count(*)` })
        .from(schema_1.mediaAssets)
        .where(where);
    const total = Number(countResult[0].count);
    return {
        data,
        pagination: {
            page,
            limit,
            total,
            totalPages: Math.ceil(total / limit),
        },
    };
};
exports.getAll = getAll;
const getFolders = async () => {
    const rows = await db_1.db
        .selectDistinct({ folder: schema_1.mediaAssets.folder })
        .from(schema_1.mediaAssets)
        .orderBy(schema_1.mediaAssets.folder);
    const rows2 = await db_1.db.select().from(schema_1.mediaAssets);
    const byFolder = {};
    for (const r of rows2) {
        byFolder[r.folder] = (byFolder[r.folder] || 0) + 1;
    }
    return rows.map((r) => ({ name: r.folder, count: byFolder[r.folder] || 0 }));
};
exports.getFolders = getFolders;
const getById = async (id) => {
    const rows = await db_1.db
        .select(mediaWithUploader)
        .from(schema_1.mediaAssets)
        .leftJoin(schema_1.users, (0, drizzle_orm_1.eq)(schema_1.mediaAssets.uploaderId, schema_1.users.id))
        .where((0, drizzle_orm_1.eq)(schema_1.mediaAssets.id, id))
        .limit(1);
    return rows[0] || null;
};
exports.getById = getById;
const remove = async (id) => {
    const asset = await (0, exports.getById)(id);
    if (!asset)
        return { success: false };
    await (0, cloud_1.deleteAsset)(asset.publicId || undefined, asset.url);
    await db_1.db.delete(schema_1.mediaAssets).where((0, drizzle_orm_1.eq)(schema_1.mediaAssets.id, id));
    return { success: true };
};
exports.remove = remove;
const updateAlt = async (id, alt) => {
    await db_1.db.update(schema_1.mediaAssets).set({ alt }).where((0, drizzle_orm_1.eq)(schema_1.mediaAssets.id, id));
    return (0, exports.getById)(id);
};
exports.updateAlt = updateAlt;
//# sourceMappingURL=media.service.js.map