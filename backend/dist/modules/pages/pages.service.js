"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.setContactMessageStatus = exports.getContactMessages = exports.createContactMessage = exports.remove = exports.update = exports.create = exports.getAll = exports.getPublishedBySlug = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const AppError_1 = require("../../utils/AppError");
const getPublishedBySlug = async (slug) => {
    const rows = await db_1.db
        .select()
        .from(schema_1.policyPages)
        .where((0, drizzle_orm_1.eq)(schema_1.policyPages.slug, slug))
        .limit(1);
    const page = rows[0];
    if (!page)
        return null;
    if (page.status !== "published") {
        return null;
    }
    return {
        id: page.id,
        slug: page.slug,
        title: page.title,
        content: page.content,
        status: page.status,
        lastUpdated: page.lastUpdated,
        createdAt: page.createdAt,
    };
};
exports.getPublishedBySlug = getPublishedBySlug;
const getAll = async () => {
    return db_1.db
        .select({
        id: schema_1.policyPages.id,
        slug: schema_1.policyPages.slug,
        title: schema_1.policyPages.title,
        status: schema_1.policyPages.status,
        lastUpdated: schema_1.policyPages.lastUpdated,
        createdAt: schema_1.policyPages.createdAt,
    })
        .from(schema_1.policyPages)
        .orderBy(schema_1.policyPages.slug);
};
exports.getAll = getAll;
const create = async (data) => {
    const exists = await db_1.db.select({ id: schema_1.policyPages.id }).from(schema_1.policyPages).where((0, drizzle_orm_1.eq)(schema_1.policyPages.slug, data.slug)).limit(1);
    if (exists[0]) {
        throw new AppError_1.AppError(409, "A page with this slug already exists");
    }
    const now = Math.floor(Date.now() / 1000);
    const result = await db_1.db.insert(schema_1.policyPages).values({
        slug: data.slug,
        title: data.title,
        content: data.content,
        status: data.status,
        lastUpdated: now,
        updatedBy: data.updatedBy,
    });
    return { id: Number(result[0].insertId) };
};
exports.create = create;
const update = async (id, payload) => {
    const exists = await db_1.db.select({ id: schema_1.policyPages.id }).from(schema_1.policyPages).where((0, drizzle_orm_1.eq)(schema_1.policyPages.id, id)).limit(1);
    if (!exists[0]) {
        throw new AppError_1.AppError(404, "Policy page not found");
    }
    const now = Math.floor(Date.now() / 1000);
    await db_1.db
        .update(schema_1.policyPages)
        .set({
        title: payload.title,
        content: payload.content,
        status: payload.status,
        lastUpdated: now,
        updatedBy: payload.updatedBy,
    })
        .where((0, drizzle_orm_1.eq)(schema_1.policyPages.id, id));
    return { id, lastUpdated: now };
};
exports.update = update;
const remove = async (id) => {
    await db_1.db.delete(schema_1.policyPages).where((0, drizzle_orm_1.eq)(schema_1.policyPages.id, id));
    return { success: true };
};
exports.remove = remove;
const createContactMessage = async (payload) => {
    const result = await db_1.db.insert(schema_1.contactMessages).values({
        name: payload.name,
        phone: payload.phone,
        email: payload.email || null,
        message: payload.message,
        status: "new",
    });
    return { success: true, id: Number(result[0].insertId) };
};
exports.createContactMessage = createContactMessage;
const getContactMessages = async () => {
    return db_1.db.select().from(schema_1.contactMessages).orderBy((0, drizzle_orm_1.desc)(schema_1.contactMessages.createdAt));
};
exports.getContactMessages = getContactMessages;
const setContactMessageStatus = async (id, status) => {
    await db_1.db.update(schema_1.contactMessages).set({ status }).where((0, drizzle_orm_1.eq)(schema_1.contactMessages.id, id));
    return { success: true };
};
exports.setContactMessageStatus = setContactMessageStatus;
//# sourceMappingURL=pages.service.js.map