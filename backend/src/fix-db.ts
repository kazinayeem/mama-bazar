import { db } from './config/db';
import { sql } from 'drizzle-orm';

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
        await db.execute(sql.raw(`ALTER TABLE ${col.table} ADD COLUMN IF NOT EXISTS ${col.name} ${col.sql}`));
        console.log(`[✓] ${col.table}.${col.name} added`);
      } catch (error: any) {
        if (!error.message.includes('Duplicate column')) {
          console.log(`[⚠️] ${col.table}.${col.name}: ${error.message}`);
        } else {
          console.log(`[✓] ${col.table}.${col.name} already exists`);
        }
      }
    }
    
    console.log('[✅] All columns ready!');
    process.exit(0);
  } catch (error: any) {
    console.error('[Error]', error.message);
    process.exit(1);
  }
}

fixColumns();
