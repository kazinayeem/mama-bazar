"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const db_1 = require("./config/db");
const drizzle_orm_1 = require("drizzle-orm");
async function fixColumns() {
    try {
        console.log('[*] Adding missing columns...');
        const columns = [
            // Users table
            { table: 'users', name: 'reset_token_hash', sql: 'VARCHAR(255)' },
            { table: 'users', name: 'reset_token_expires_at', sql: 'TIMESTAMP NULL' },
            { table: 'users', name: 'phone', sql: 'VARCHAR(20)' },
            { table: 'users', name: 'shipping_area', sql: 'VARCHAR(100)' },
            { table: 'users', name: 'shipping_address', sql: 'TEXT' },
            // Orders table
            { table: 'orders', name: 'user_id', sql: 'INT' },
        ];
        for (const col of columns) {
            try {
                await db_1.db.execute(drizzle_orm_1.sql.raw(`ALTER TABLE ${col.table} ADD COLUMN IF NOT EXISTS ${col.name} ${col.sql}`));
                console.log(`[✓] ${col.table}.${col.name} added`);
            }
            catch (error) {
                if (!error.message.includes('Duplicate column')) {
                    console.log(`[⚠️] ${col.table}.${col.name}: ${error.message}`);
                }
                else {
                    console.log(`[✓] ${col.table}.${col.name} already exists`);
                }
            }
        }
        console.log('[✅] All columns ready!');
        process.exit(0);
    }
    catch (error) {
        console.error('[Error]', error.message);
        process.exit(1);
    }
}
fixColumns();
//# sourceMappingURL=fix-db.js.map