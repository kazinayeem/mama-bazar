"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.setJSON = exports.getJSON = exports.set = exports.get = exports.getAll = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const getAll = async () => {
    return db_1.db.select().from(schema_1.siteSettings);
};
exports.getAll = getAll;
const get = async (key) => {
    const rows = await db_1.db
        .select()
        .from(schema_1.siteSettings)
        .where((0, drizzle_orm_1.eq)(schema_1.siteSettings.key, key))
        .orderBy((0, drizzle_orm_1.desc)(schema_1.siteSettings.id))
        .limit(1);
    return rows[0] || null;
};
exports.get = get;
const set = async (key, value) => {
    const existing = await (0, exports.get)(key);
    if (existing) {
        await db_1.db.update(schema_1.siteSettings).set({ value }).where((0, drizzle_orm_1.eq)(schema_1.siteSettings.key, key));
    }
    else {
        await db_1.db.insert(schema_1.siteSettings).values({ key, value });
    }
    return (0, exports.get)(key);
};
exports.set = set;
const getJSON = async (key, fallback) => {
    const setting = await (0, exports.get)(key);
    if (!setting?.value)
        return fallback;
    try {
        return JSON.parse(setting.value);
    }
    catch {
        return fallback;
    }
};
exports.getJSON = getJSON;
const setJSON = async (key, value) => {
    await (0, exports.set)(key, JSON.stringify(value));
};
exports.setJSON = setJSON;
//# sourceMappingURL=settings.service.js.map