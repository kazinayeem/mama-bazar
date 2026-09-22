import dotenv from "dotenv";
import { drizzle } from "drizzle-orm/mysql2";
import mysql from "mysql2/promise";
import * as schema from "./schema";
import { env } from "./env";

dotenv.config();

const dbUrl = env.DATABASE_URL || process.env.DATABASE_URL || "";

// Detect if SSL should be used:
// 1. Explicit DATABASE_SSL or DB_SSL flag overrides
// 2. Localhost / 127.0.0.1 defaults to NO SSL (cPanel local MySQL)
// 3. Remote hosts default to SSL with rejectUnauthorized: false (supports TiDB/Cloud/Remote MySQL)
const isLocalhost =
  !dbUrl ||
  dbUrl.includes("localhost") ||
  dbUrl.includes("127.0.0.1") ||
  process.env.DB_HOST === "localhost" ||
  process.env.DB_HOST === "127.0.0.1";

const explicitSsl = process.env.DATABASE_SSL ?? process.env.DB_SSL;
const useSsl =
  explicitSsl !== undefined
    ? explicitSsl === "true" || explicitSsl === "1"
    : !isLocalhost && !dbUrl.includes("ssl=false");

const pool = mysql.createPool({
  uri: dbUrl || undefined,
  waitForConnections: true,
  connectionLimit: 10,
  maxIdle: 5,
  idleTimeout: 60000,
  enableKeepAlive: true,
  keepAliveInitialDelay: 10000,
  connectTimeout: 20000,
  ...(useSsl ? { ssl: { rejectUnauthorized: false } } : {}),
});

export const db = drizzle(pool, { schema, mode: "default" });
export { pool };

