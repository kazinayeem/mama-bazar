import { and, asc, count, desc, eq, like, sql } from "drizzle-orm";
import { db } from "../../config/db";
import { colors, sizes, collections, vendors, suppliers, products } from "../../config/schema";

const TABLES = { colors, sizes, collections, vendors, suppliers } as const;

export type CatalogTableName = keyof typeof TABLES;

const tableOf = (name: CatalogTableName) => TABLES[name];

const PRODUCT_FK: Partial<Record<CatalogTableName, string>> = {
  collections: "collection_id",
  vendors: "vendor_id",
  suppliers: "supplier_id",
};

const JSON_FIELD: Partial<Record<CatalogTableName, string>> = {
  colors: "color_options",
  sizes: "size_options",
};

export const catalogService = {
  async list(name: CatalogTableName) {
    const table = tableOf(name);
    const sortCol = (table as any).sortOrder;
    const rows = sortCol
      ? await db.select().from(table as any).where(eq((table as any).status, "active")).orderBy(asc(sortCol), asc((table as any).name))
      : await db.select().from(table as any).where(eq((table as any).status, "active")).orderBy(asc((table as any).name));
    return rows;
  },

  async listAdmin(name: CatalogTableName, params: { page?: number; limit?: number; search?: string; status?: string; sort?: string }) {
    const table = tableOf(name);
    const page = Math.max(1, params.page || 1);
    const limit = Math.min(100, Math.max(1, params.limit || 20));

    const conditions = [];
    if (params.search) conditions.push(like((table as any).name, `%${params.search}%`));
    if (params.status && ["active", "inactive", "archived"].includes(params.status)) {
      conditions.push(eq((table as any).status, params.status));
    }
    const where = conditions.length ? and(...conditions) : undefined;

    const orderBy =
      params.sort === "oldest"
        ? [asc((table as any).createdAt)]
        : (table as any).sortOrder
          ? [asc((table as any).sortOrder), asc((table as any).name)]
          : [asc((table as any).name)];

    const [rows, totalRows] = await Promise.all([
      db.select().from(table as any).where(where).orderBy(...orderBy).limit(limit).offset((page - 1) * limit),
      db.select({ count: count() }).from(table as any).where(where),
    ]);

    const total = totalRows[0]?.count ?? 0;
    return {
      data: rows,
      pagination: { page, limit, total, totalPages: Math.max(1, Math.ceil(total / limit)) },
    };
  },

  async getById(name: CatalogTableName, id: number) {
    const table = tableOf(name);
    const rows = await db.select().from(table as any).where(eq((table as any).id, id)).limit(1);
    return rows[0] || null;
  },

  async create(name: CatalogTableName, data: Record<string, unknown>) {
    const table = tableOf(name);
    const result = await db.insert(table as any).values(data as any);
    return this.getById(name, result[0].insertId);
  },

  async update(name: CatalogTableName, id: number, data: Record<string, unknown>) {
    const table = tableOf(name);
    await db.update(table as any).set(data as any).where(eq((table as any).id, id));
    return this.getById(name, id);
  },

  async remove(name: CatalogTableName, id: number) {
    const table = tableOf(name);
    await db.delete(table as any).where(eq((table as any).id, id));
    return { success: true };
  },

  async getUsage(name: CatalogTableName, id: number, valueName?: string): Promise<number> {
    const fk = PRODUCT_FK[name];
    if (fk) {
      const rows = await db
        .select({ count: count() })
        .from(products)
        .where(eq(products[fk as keyof typeof products] as never, id));
      return rows[0]?.count ?? 0;
    }

    const jsonField = JSON_FIELD[name];
    if (jsonField && valueName) {
      const rows = await db.execute(
        sql`SELECT COUNT(*) AS c FROM products WHERE JSON_CONTAINS(${sql.raw(jsonField)}, JSON_QUOTE(${valueName}))`
      );
      const first = (rows as { rows?: Array<{ c: number | string }> }).rows?.[0];
      return Number(first?.c ?? 0);
    }
    return 0;
  },

  async move(name: CatalogTableName, id: number, targetId: number | null, valueName?: string) {
    const fk = PRODUCT_FK[name];
    if (fk) {
      const result = await db
        .update(products)
        .set({ [fk]: targetId } as never)
        .where(eq(products[fk as keyof typeof products] as never, id));
      return { moved: result[0].affectedRows };
    }

    const jsonField = JSON_FIELD[name];
    if (jsonField && valueName) {
      const rows = await db
        .select({ id: products.id, options: products[jsonField as keyof typeof products] as never })
        .from(products)
        .where(sql`JSON_CONTAINS(${sql.raw(jsonField)}, JSON_QUOTE(${valueName}))`);
      let moved = 0;
      for (const row of rows) {
        const options = (row as any).options as unknown;
        const values = Array.isArray(options) ? [...options] : [];
        const idx = values.findIndex((v) => (typeof v === "string" ? v === valueName : v?.name === valueName));
        if (idx >= 0) values.splice(idx, 1);
        await db
          .update(products)
          .set({ [jsonField]: values } as never)
          .where(eq(products.id, (row as any).id));
        moved++;
      }
      return { moved };
    }
    return { moved: 0 };
  },
};
