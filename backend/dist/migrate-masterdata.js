"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const drizzle_orm_1 = require("drizzle-orm");
const db_1 = require("./config/db");
const run = async () => {
    try {
        // ---------- add columns (idempotent) ----------
        const addColumns = (table, columns) => Promise.all(columns.map(([column, definition]) => db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE ${drizzle_orm_1.sql.raw(table)} ADD COLUMN IF NOT EXISTS ${drizzle_orm_1.sql.raw(column)} ${drizzle_orm_1.sql.raw(definition)}`)));
        await addColumns("categories", [["homepage_visibility", "TINYINT DEFAULT 1"]]);
        await addColumns("brands", [
            ["country_of_origin", "VARCHAR(100)"],
            ["homepage_visibility", "TINYINT DEFAULT 1"],
            ["sort_order", "INT DEFAULT 0"],
            ["seo_title", "VARCHAR(255)"],
            ["seo_description", "TEXT"],
            ["seo_keywords", "VARCHAR(500)"],
        ]);
        await addColumns("collections", [
            ["banner", "VARCHAR(500)"],
            ["homepage_visibility", "TINYINT DEFAULT 1"],
            ["start_date", "DATETIME"],
            ["end_date", "DATETIME"],
        ]);
        await addColumns("vendors", [["notes", "TEXT"]]);
        await addColumns("suppliers", [
            ["logo", "VARCHAR(500)"],
            ["description", "TEXT"],
            ["notes", "TEXT"],
        ]);
        await addColumns("colors", [["display_name", "VARCHAR(100)"]]);
        await addColumns("sizes", [["type", "ENUM('clothing','shoes','general','custom') DEFAULT 'general'"]]);
        // ---------- extend status enums with 'archived' ----------
        const enumTables = ["categories", "brands", "collections", "vendors", "suppliers", "colors", "sizes"];
        for (const table of enumTables) {
            await db_1.db.execute((0, drizzle_orm_1.sql) `ALTER TABLE ${drizzle_orm_1.sql.raw(table)} MODIFY COLUMN status ENUM('active','inactive','archived') DEFAULT 'active' NOT NULL`);
        }
        console.log("✓ master-data migration complete: new columns + archived status");
    }
    catch (error) {
        console.error("✗ master-data migration failed", error);
        process.exitCode = 1;
    }
};
run();
//# sourceMappingURL=migrate-masterdata.js.map