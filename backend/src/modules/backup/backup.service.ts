import AdmZip from "adm-zip";
import { pool, db } from "../../config/db";
import { adminBackups } from "../../config/schema";
import { desc, eq } from "drizzle-orm";
import { AppError } from "../../utils/AppError";
import { logAuditEvent } from "../admin/audit.service";
import { RowDataPacket } from "mysql2";
import { backupStorage } from "./backup.storage";

// Application tables in dependency order for safe export & restoration
export const TABLE_RESTORE_ORDER = [
  "site_settings",
  "admin_roles",
  "admin_permissions",
  "role_permissions",
  "categories",
  "brands",
  "collections",
  "colors",
  "sizes",
  "vendors",
  "suppliers",
  "shipping_methods",
  "payment_methods",
  "checkout_notices",
  "homepage_sections",
  "homepage_hero_slides",
  "homepage_featured_items",
  "policy_pages",
  "banners",
  "media_assets",
  "expense_categories",
  "users",
  "user_addresses",
  "user_permissions",
  "products",
  "product_variants",
  "product_specs",
  "product_relations",
  "collection_products",
  "reviews",
  "coupons",
  "coupon_usages",
  "orders",
  "order_items",
  "order_status_history",
  "order_timeline",
  "marketing_integrations",
  "tracking_logs",
  "newsletters",
  "contact_messages",
  "expenses",
  "costs",
  "bookings",
  "rentals",
  "memos",
  "admin_audit_logs",
];

// ─── Listing ───────────────────────────────────────────────────────────────────

export const listBackups = async () => {
  return db.select().from(adminBackups).orderBy(desc(adminBackups.createdAt));
};

export const getBackupById = async (id: number) => {
  const rows = await db.select().from(adminBackups).where(eq(adminBackups.id, id)).limit(1);
  return rows[0] || null;
};

// ─── Create Backup ─────────────────────────────────────────────────────────────

export const createBackup = async (
  options: {
    type?: "manual" | "safety_auto";
    createdById?: number | null;
    actorName?: string;
    actorEmail?: string;
    ip?: string;
    userAgent?: string;
  } = {}
) => {
  const connection = await pool.getConnection();
  try {
    const type = options.type || "manual";
    const zip = new AdmZip();

    const manifest: {
      formatVersion: string;
      application: string;
      createdAt: string;
      type: string;
      tableCount: number;
      totalRecords: number;
      tables: Record<string, number>;
    } = {
      formatVersion: "1.0",
      application: "MamaBazar",
      createdAt: new Date().toISOString(),
      type,
      tableCount: 0,
      totalRecords: 0,
      tables: {},
    };

    // Get list of existing tables in DB
    const [existingTableRows] = await connection.query<RowDataPacket[]>("SHOW TABLES");
    const existingDbTables = new Set(existingTableRows.map((r) => Object.values(r)[0] as string));

    let tableCount = 0;
    let totalRecords = 0;

    for (const table of TABLE_RESTORE_ORDER) {
      if (!existingDbTables.has(table)) continue;

      const [rows] = await connection.query<RowDataPacket[]>(`SELECT * FROM \`${table}\``);
      const rowList = rows as any[];

      // Sanitize sensitive values from `users` table
      const sanitizedRows = rowList.map((row) => {
        if (table === "users") {
          const { resetTokenHash, resetTokenExpiresAt, ...safeUser } = row;
          return safeUser;
        }
        return row;
      });

      manifest.tables[table] = sanitizedRows.length;
      tableCount++;
      totalRecords += sanitizedRows.length;

      zip.addFile(
        `database/${table}.json`,
        Buffer.from(JSON.stringify(sanitizedRows, null, 2), "utf8")
      );
    }

    manifest.tableCount = tableCount;
    manifest.totalRecords = totalRecords;

    zip.addFile("manifest.json", Buffer.from(JSON.stringify(manifest, null, 2), "utf8"));
    zip.addFile(
      "metadata.json",
      Buffer.from(
        JSON.stringify(
          {
            version: "1.0.0",
            app: "MamaBazar E-Commerce",
            engine: "MySQL + Drizzle ORM",
            environment: process.env.NODE_ENV || "production",
            createdByName: options.actorName || "System",
            storage: backupStorage.provider,
          },
          null,
          2
        ),
        "utf8"
      )
    );

    // Build in-memory ZIP buffer — no local filesystem write on Vercel
    const zipBuffer = zip.toBuffer();

    // ── Validate the generated ZIP before storing it ──────────────────────────
    // ZIP files always start with the local-file header magic bytes: PK (0x50 0x4b)
    // An empty or malformed buffer here means adm-zip failed silently.
    if (!zipBuffer || zipBuffer.length < 22) {
      throw new AppError(500, "Backup archive generation failed: resulting buffer is too small to be a valid ZIP.");
    }
    if (zipBuffer[0] !== 0x50 || zipBuffer[1] !== 0x4b) {
      throw new AppError(500, "Backup archive generation failed: output is not a valid ZIP file (missing PK signature).");
    }
    // Self-verify: try reading back the ZIP we just created
    try {
      const AdmZipVerify = require("adm-zip");
      const verify = new AdmZipVerify(zipBuffer);
      const entries = verify.getEntries();
      if (entries.length === 0) {
        throw new Error("ZIP archive contains no entries");
      }
      // Ensure manifest.json is present
      const hasManifest = entries.some((e: any) => e.entryName === "manifest.json");
      if (!hasManifest) {
        throw new Error("manifest.json is missing from the generated archive");
      }
    } catch (verifyErr: any) {
      throw new AppError(500, `Backup archive verification failed: ${verifyErr.message}`);
    }

    const timestamp = new Date().toISOString().replace(/[:.]/g, "-");
    const filename = `mamabazar-backup-${type}-${timestamp}.zip`;

    // Upload to persistent storage (Cloudinary in production, /tmp in dev)
    let storageResult: { storageKey: string; size: number };
    try {
      storageResult = await backupStorage.upload(zipBuffer, filename);
    } catch (uploadErr: any) {
      console.error("[Backup] Storage upload failed:", uploadErr?.message);
      throw new AppError(
        503,
        "Backup archive could not be stored. " +
          (backupStorage.isCloudinary()
            ? "Check CLOUDINARY_* environment variables."
            : "Storage unavailable.")
      );
    }

    // Sanity-check what storage actually received
    if (storageResult.size === 0) {
      throw new AppError(500, "Storage reported 0 bytes for the uploaded backup archive.");
    }


    // Persist metadata in DB — filepath column now holds storageKey (public_id or /tmp path)
    const [insertResult] = await connection.query<any>(
      "INSERT INTO `admin_backups` (`filename`, `filepath`, `size`, `type`, `table_count`, `record_count`, `created_by_id`) VALUES (?, ?, ?, ?, ?, ?, ?)",
      [
        filename,
        storageResult.storageKey,
        storageResult.size,
        type,
        tableCount,
        totalRecords,
        options.createdById || null,
      ]
    );

    const backupId = insertResult.insertId;

    await logAuditEvent({
      actorId: options.createdById || null,
      actorName: options.actorName || "System",
      actorEmail: options.actorEmail || null,
      action: "BACKUP_CREATED",
      targetType: "Backup",
      targetId: String(backupId),
      details: {
        filename,
        size: storageResult.size,
        type,
        tableCount,
        totalRecords,
        storage: backupStorage.provider,
      },
      ipAddress: options.ip,
      userAgent: options.userAgent,
    });

    return {
      id: backupId,
      filename,
      size: storageResult.size,
      type,
      tableCount,
      recordCount: totalRecords,
      createdAt: new Date(),
    };
  } finally {
    connection.release();
  }
};

// ─── Restore Backup ────────────────────────────────────────────────────────────

export const restoreBackup = async (
  fileBuffer: Buffer,
  actor?: { id?: number; name?: string; email?: string; ip?: string; userAgent?: string }
) => {
  // Step 1: Validate ZIP Archive & Manifest
  let zip: AdmZip;
  try {
    zip = new AdmZip(fileBuffer);
  } catch {
    throw new AppError(400, "Invalid backup archive: Unable to read ZIP format");
  }

  const manifestEntry = zip.getEntry("manifest.json");
  if (!manifestEntry) {
    throw new AppError(400, "Invalid backup format: Missing manifest.json");
  }

  let manifest: any;
  try {
    manifest = JSON.parse(zip.readAsText(manifestEntry));
  } catch {
    throw new AppError(400, "Invalid backup format: Malformed manifest.json");
  }

  if (manifest.application !== "MamaBazar" || !manifest.tables) {
    throw new AppError(400, "Incompatible backup archive: Unrecognized application manifest");
  }

  // Step 2: CREATE MANDATORY SAFETY BACKUP OF CURRENT STATE FIRST
  console.log("[Backup] Creating automatic pre-restore safety backup...");
  const safetyBackup = await createBackup({
    type: "safety_auto",
    createdById: actor?.id,
    actorName: `Auto Safety Pre-Restore (${actor?.name || "Admin"})`,
    actorEmail: actor?.email,
    ip: actor?.ip,
    userAgent: actor?.userAgent,
  });
  console.log("[Backup] Safety backup created:", safetyBackup.filename);

  // Step 3: Execute Restoration in Safe Order inside Transaction
  const connection = await pool.getConnection();
  try {
    await connection.beginTransaction();

    // Disable foreign keys temporarily for clean table reload
    await connection.query("SET FOREIGN_KEY_CHECKS = 0");

    let restoredTablesCount = 0;
    let restoredRecordsCount = 0;

    for (const table of TABLE_RESTORE_ORDER) {
      const entry = zip.getEntry(`database/${table}.json`);
      if (!entry) continue;

      let tableData: any[];
      try {
        tableData = JSON.parse(zip.readAsText(entry));
      } catch {
        throw new Error(`Corrupted table data for '${table}' in backup archive`);
      }

      if (!Array.isArray(tableData)) continue;

      // Truncate current table
      await connection.query(`TRUNCATE TABLE \`${table}\``);

      if (tableData.length > 0) {
        const batchSize = 100;
        for (let i = 0; i < tableData.length; i += batchSize) {
          const chunk = tableData.slice(i, i + batchSize);
          const columns = Object.keys(chunk[0]);
          const placeholders = chunk.map(() => `(${columns.map(() => "?").join(", ")})`).join(", ");
          const values = chunk.flatMap((row) =>
            columns.map((col) => {
              const val = row[col];
              if (val === undefined || val === null) return null;
              if (typeof val === "object") return JSON.stringify(val);
              return val;
            })
          );

          await connection.query(
            `INSERT INTO \`${table}\` (${columns.map((c) => `\`${c}\``).join(", ")}) VALUES ${placeholders}`,
            values
          );
        }
      }

      restoredTablesCount++;
      restoredRecordsCount += tableData.length;
    }

    await connection.query("SET FOREIGN_KEY_CHECKS = 1");
    await connection.commit();

    await logAuditEvent({
      actorId: actor?.id || null,
      actorName: actor?.name || "Super Admin",
      actorEmail: actor?.email || null,
      action: "RESTORE_COMPLETED",
      targetType: "Database",
      details: {
        restoredTablesCount,
        restoredRecordsCount,
        safetyBackupId: safetyBackup.id,
        safetyBackupFilename: safetyBackup.filename,
      },
      ipAddress: actor?.ip,
      userAgent: actor?.userAgent,
      status: "success",
    });

    return {
      success: true,
      restoredTablesCount,
      restoredRecordsCount,
      safetyBackupFilename: safetyBackup.filename,
    };
  } catch (err) {
    await connection.rollback();
    await connection.query("SET FOREIGN_KEY_CHECKS = 1");

    await logAuditEvent({
      actorId: actor?.id || null,
      actorName: actor?.name || "Super Admin",
      actorEmail: actor?.email || null,
      action: "RESTORE_FAILED",
      targetType: "Database",
      details: {
        error: err instanceof Error ? err.message : String(err),
        safetyBackupId: safetyBackup.id,
      },
      ipAddress: actor?.ip,
      userAgent: actor?.userAgent,
      status: "failure",
    });

    throw new AppError(
      500,
      `Database restore failed: ${err instanceof Error ? err.message : "Unknown error"}. Current state preserved via safety backup '${safetyBackup.filename}'.`
    );
  } finally {
    connection.release();
  }
};

// ─── Delete Backup ─────────────────────────────────────────────────────────────

export const deleteBackup = async (
  id: number,
  actor?: { id?: number; name?: string; email?: string; ip?: string; userAgent?: string }
) => {
  const rows = await db.select().from(adminBackups).where(eq(adminBackups.id, id)).limit(1);
  const backup = rows[0];
  if (!backup) {
    throw new AppError(404, "Backup not found");
  }

  // Remove from persistent storage (Cloudinary public_id or /tmp path)
  try {
    await backupStorage.delete(backup.filepath);
  } catch (err) {
    // Log but don't block metadata deletion — the record should still be cleaned up
    console.error("[Backup] Failed to delete backup from storage:", err);
  }

  await db.delete(adminBackups).where(eq(adminBackups.id, id));

  await logAuditEvent({
    actorId: actor?.id || null,
    actorName: actor?.name || "Super Admin",
    actorEmail: actor?.email || null,
    action: "BACKUP_DELETED",
    targetType: "Backup",
    targetId: String(id),
    details: { filename: backup.filename, storage: backupStorage.provider },
    ipAddress: actor?.ip,
    userAgent: actor?.userAgent,
  });

  return { success: true };
};

// ─── PIN Validation ────────────────────────────────────────────────────────────

/**
 * Returns valid DDMMYYYY PINs based on the server's current date.
 * Covers local time, Asia/Dhaka time, and UTC to prevent timezone shifts from blocking legitimate admin access.
 */
export const getExpectedBackupPins = (): string[] => {
  const now = new Date();

  const formatDateToDDMMYYYY = (date: Date, timeZone?: string): string => {
    try {
      const formatter = new Intl.DateTimeFormat("en-GB", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        timeZone: timeZone || undefined,
      });
      const parts = formatter.formatToParts(date);
      const day = parts.find((p) => p.type === "day")?.value.padStart(2, "0") || "";
      const month = parts.find((p) => p.type === "month")?.value.padStart(2, "0") || "";
      const year = parts.find((p) => p.type === "year")?.value || "";
      return `${day}${month}${year}`;
    } catch {
      // Fallback
      const d = String(date.getDate()).padStart(2, "0");
      const m = String(date.getMonth() + 1).padStart(2, "0");
      const y = String(date.getFullYear());
      return `${d}${m}${y}`;
    }
  };

  const pins = new Set<string>();
  pins.add(formatDateToDDMMYYYY(now));
  pins.add(formatDateToDDMMYYYY(now, "Asia/Dhaka"));
  pins.add(formatDateToDDMMYYYY(now, "UTC"));

  return Array.from(pins).filter((pin) => pin.length === 8);
};

export const validateBackupPin = (inputPin?: string): boolean => {
  if (!inputPin || typeof inputPin !== "string") return false;
  const cleanPin = inputPin.trim();
  const validPins = getExpectedBackupPins();
  return validPins.includes(cleanPin);
};

