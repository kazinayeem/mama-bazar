"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.catalogService = void 0;
const drizzle_orm_1 = require("drizzle-orm");
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const TABLES = { colors: schema_1.colors, sizes: schema_1.sizes, collections: schema_1.collections, vendors: schema_1.vendors, suppliers: schema_1.suppliers };
const tableOf = (name) => TABLES[name];
const PRODUCT_FK = {
    collections: "collection_id",
    vendors: "vendor_id",
    suppliers: "supplier_id",
};
const JSON_FIELD = {
    colors: "color_options",
    sizes: "size_options",
};
exports.catalogService = {
    async list(name) {
        const table = tableOf(name);
        const sortCol = table.sortOrder;
        const rows = sortCol
            ? await db_1.db.select().from(table).where((0, drizzle_orm_1.eq)(table.status, "active")).orderBy((0, drizzle_orm_1.asc)(sortCol), (0, drizzle_orm_1.asc)(table.name))
            : await db_1.db.select().from(table).where((0, drizzle_orm_1.eq)(table.status, "active")).orderBy((0, drizzle_orm_1.asc)(table.name));
        return rows;
    },
    async listAdmin(name, params) {
        const table = tableOf(name);
        const page = Math.max(1, params.page || 1);
        const limit = Math.min(100, Math.max(1, params.limit || 20));
        const conditions = [];
        if (params.search)
            conditions.push((0, drizzle_orm_1.like)(table.name, `%${params.search}%`));
        if (params.status && ["active", "inactive", "archived"].includes(params.status)) {
            conditions.push((0, drizzle_orm_1.eq)(table.status, params.status));
        }
        const where = conditions.length ? (0, drizzle_orm_1.and)(...conditions) : undefined;
        const orderBy = params.sort === "oldest"
            ? [(0, drizzle_orm_1.asc)(table.createdAt)]
            : table.sortOrder
                ? [(0, drizzle_orm_1.asc)(table.sortOrder), (0, drizzle_orm_1.asc)(table.name)]
                : [(0, drizzle_orm_1.asc)(table.name)];
        const [rows, totalRows] = await Promise.all([
            db_1.db.select().from(table).where(where).orderBy(...orderBy).limit(limit).offset((page - 1) * limit),
            db_1.db.select({ count: (0, drizzle_orm_1.count)() }).from(table).where(where),
        ]);
        const total = totalRows[0]?.count ?? 0;
        return {
            data: rows,
            pagination: { page, limit, total, totalPages: Math.max(1, Math.ceil(total / limit)) },
        };
    },
    async getById(name, id) {
        const table = tableOf(name);
        const rows = await db_1.db.select().from(table).where((0, drizzle_orm_1.eq)(table.id, id)).limit(1);
        return rows[0] || null;
    },
    async create(name, data) {
        const table = tableOf(name);
        const result = await db_1.db.insert(table).values(data);
        return this.getById(name, result[0].insertId);
    },
    async update(name, id, data) {
        const table = tableOf(name);
        await db_1.db.update(table).set(data).where((0, drizzle_orm_1.eq)(table.id, id));
        return this.getById(name, id);
    },
    async remove(name, id) {
        const table = tableOf(name);
        await db_1.db.delete(table).where((0, drizzle_orm_1.eq)(table.id, id));
        return { success: true };
    },
    async getUsage(name, id, valueName) {
        const fk = PRODUCT_FK[name];
        if (fk) {
            const rows = await db_1.db
                .select({ count: (0, drizzle_orm_1.count)() })
                .from(schema_1.products)
                .where((0, drizzle_orm_1.eq)(schema_1.products[fk], id));
            return rows[0]?.count ?? 0;
        }
        const jsonField = JSON_FIELD[name];
        if (jsonField && valueName) {
            const rows = await db_1.db.execute((0, drizzle_orm_1.sql) `SELECT COUNT(*) AS c FROM products WHERE JSON_CONTAINS(${drizzle_orm_1.sql.raw(jsonField)}, JSON_QUOTE(${valueName}))`);
            const first = rows.rows?.[0];
            return Number(first?.c ?? 0);
        }
        return 0;
    },
    async move(name, id, targetId, valueName) {
        const fk = PRODUCT_FK[name];
        if (fk) {
            const result = await db_1.db
                .update(schema_1.products)
                .set({ [fk]: targetId })
                .where((0, drizzle_orm_1.eq)(schema_1.products[fk], id));
            return { moved: result[0].affectedRows };
        }
        const jsonField = JSON_FIELD[name];
        if (jsonField && valueName) {
            const rows = await db_1.db
                .select({ id: schema_1.products.id, options: schema_1.products[jsonField] })
                .from(schema_1.products)
                .where((0, drizzle_orm_1.sql) `JSON_CONTAINS(${drizzle_orm_1.sql.raw(jsonField)}, JSON_QUOTE(${valueName}))`);
            let moved = 0;
            for (const row of rows) {
                const options = row.options;
                const values = Array.isArray(options) ? [...options] : [];
                const idx = values.findIndex((v) => (typeof v === "string" ? v === valueName : v?.name === valueName));
                if (idx >= 0)
                    values.splice(idx, 1);
                await db_1.db
                    .update(schema_1.products)
                    .set({ [jsonField]: values })
                    .where((0, drizzle_orm_1.eq)(schema_1.products.id, row.id));
                moved++;
            }
            return { moved };
        }
        return { moved: 0 };
    },
};
//# sourceMappingURL=catalog.service.js.map