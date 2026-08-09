import { db } from "../../config/db";
import { marketingIntegrations, trackingLogs } from "../../config/schema";
import { eq, desc } from "drizzle-orm";
import { CreateIntegrationInput, UpdateIntegrationInput, TrackingConfig } from "./tracking.interface";

const DEFAULT_STATUS = "active";
const DEFAULT_LOG_LIMIT = 50;

const PIXEL_ID_PATTERNS: Record<string, RegExp> = {
  facebook_pixel: /^\d{10,20}$/,
  google_analytics: /^G-[A-Z0-9]{6,14}$/,
  google_tag_manager: /^GTM-[A-Z0-9]{4,12}$/,
  tiktok_pixel: /^[A-Z0-9]{10,30}$/i,
};

function sanitizeScript(script: string): string {
  return script
    .replace(/<\/?iframe[^>]*>/gi, "")
    .replace(/on\w+\s*=/gi, "")
    .replace(/javascript:/gi, "")
    .replace(/eval\s*\(/gi, "")
    .replace(/document\.cookie/gi, "")
    .replace(/document\.write/gi, "")
    .replace(/window\.location\s*=/gi, "");
}

function validatePixelId(type: string, pixelId: string): boolean {
  const pattern = PIXEL_ID_PATTERNS[type];
  if (!pattern) return true;
  return pattern.test(pixelId);
}

export const getAll = async () => {
  return db.select().from(marketingIntegrations).orderBy(desc(marketingIntegrations.createdAt));
};

export const getActive = async () => {
  return db.select().from(marketingIntegrations).where(eq(marketingIntegrations.status, DEFAULT_STATUS));
};

export const getById = async (id: number) => {
  const rows = await db.select().from(marketingIntegrations).where(eq(marketingIntegrations.id, id)).limit(1);
  return rows[0] || null;
};

export const getTrackingConfig = async (): Promise<TrackingConfig> => {
  const active = await getActive();
  const config: TrackingConfig = {
    customHeadScripts: [],
    customBodyScripts: [],
  };

  for (const row of active) {
    switch (row.type) {
      case "google_tag_manager":
        config.gtmId = row.pixelId || undefined;
        break;
      case "google_analytics":
        config.gaMeasurementId = row.pixelId || undefined;
        break;
      case "facebook_pixel":
        config.facebookPixelId = row.pixelId || undefined;
        break;
      case "facebook_conversion_api":
        config.facebookAccessToken = row.accessToken || undefined;
        config.facebookTestEventCode = row.testEventCode || undefined;
        break;
      case "tiktok_pixel":
        config.tiktokPixelId = row.pixelId || undefined;
        break;
      case "custom_script":
        if (row.scriptCode) config.customHeadScripts.push(row.scriptCode);
        break;
    }
  }
  return config;
};

export const create = async (data: CreateIntegrationInput) => {
  if (data.pixelId && !validatePixelId(data.type, data.pixelId)) {
    throw new Error(`Invalid pixel ID format for ${data.type}`);
  }

  const insertData: any = {
    name: data.name,
    type: data.type,
    pixelId: data.pixelId || null,
    scriptCode: data.type === "custom_script" && data.scriptCode ? sanitizeScript(data.scriptCode) : null,
    accessToken: data.accessToken || null,
    testEventCode: data.testEventCode || null,
    status: data.status || DEFAULT_STATUS,
  };

  const result = await db.insert(marketingIntegrations).values(insertData);
  return getById(result[0].insertId);
};

export const update = async (id: number, data: UpdateIntegrationInput) => {
  if (data.pixelId && data.type && !validatePixelId(data.type, data.pixelId)) {
    throw new Error(`Invalid pixel ID format for ${data.type}`);
  }

  const updateData: any = { ...data, updatedAt: new Date() };
  if (data.scriptCode) {
    updateData.scriptCode = sanitizeScript(data.scriptCode);
  }

  await db.update(marketingIntegrations).set(updateData).where(eq(marketingIntegrations.id, id));
  return getById(id);
};

export const remove = async (id: number) => {
  await db.delete(marketingIntegrations).where(eq(marketingIntegrations.id, id));
  return { success: true };
};

export const logEvent = async (eventName: string, platform: string, payload: any, status: "success" | "failed" = "success", errorMessage?: string) => {
  await db.insert(trackingLogs).values({
    eventName,
    platform,
    payload,
    status,
    errorMessage: errorMessage || null,
  });
};

export const getRecentLogs = async (limit: number = DEFAULT_LOG_LIMIT) => {
  return db.select().from(trackingLogs).orderBy(desc(trackingLogs.createdAt)).limit(limit);
};
