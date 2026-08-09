"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.getRecentLogs = exports.logEvent = exports.remove = exports.update = exports.create = exports.getTrackingConfig = exports.getById = exports.getActive = exports.getAll = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const DEFAULT_STATUS = "active";
const DEFAULT_LOG_LIMIT = 50;
const PIXEL_ID_PATTERNS = {
    facebook_pixel: /^\d{10,20}$/,
    google_analytics: /^G-[A-Z0-9]{6,14}$/,
    google_tag_manager: /^GTM-[A-Z0-9]{4,12}$/,
    tiktok_pixel: /^[A-Z0-9]{10,30}$/i,
};
function sanitizeScript(script) {
    return script
        .replace(/<\/?iframe[^>]*>/gi, "")
        .replace(/on\w+\s*=/gi, "")
        .replace(/javascript:/gi, "")
        .replace(/eval\s*\(/gi, "")
        .replace(/document\.cookie/gi, "")
        .replace(/document\.write/gi, "")
        .replace(/window\.location\s*=/gi, "");
}
function validatePixelId(type, pixelId) {
    const pattern = PIXEL_ID_PATTERNS[type];
    if (!pattern)
        return true;
    return pattern.test(pixelId);
}
const getAll = async () => {
    return db_1.db.select().from(schema_1.marketingIntegrations).orderBy((0, drizzle_orm_1.desc)(schema_1.marketingIntegrations.createdAt));
};
exports.getAll = getAll;
const getActive = async () => {
    return db_1.db.select().from(schema_1.marketingIntegrations).where((0, drizzle_orm_1.eq)(schema_1.marketingIntegrations.status, DEFAULT_STATUS));
};
exports.getActive = getActive;
const getById = async (id) => {
    const rows = await db_1.db.select().from(schema_1.marketingIntegrations).where((0, drizzle_orm_1.eq)(schema_1.marketingIntegrations.id, id)).limit(1);
    return rows[0] || null;
};
exports.getById = getById;
const getTrackingConfig = async () => {
    const active = await (0, exports.getActive)();
    const config = {
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
                if (row.scriptCode)
                    config.customHeadScripts.push(row.scriptCode);
                break;
        }
    }
    return config;
};
exports.getTrackingConfig = getTrackingConfig;
const create = async (data) => {
    if (data.pixelId && !validatePixelId(data.type, data.pixelId)) {
        throw new Error(`Invalid pixel ID format for ${data.type}`);
    }
    const insertData = {
        name: data.name,
        type: data.type,
        pixelId: data.pixelId || null,
        scriptCode: data.type === "custom_script" && data.scriptCode ? sanitizeScript(data.scriptCode) : null,
        accessToken: data.accessToken || null,
        testEventCode: data.testEventCode || null,
        status: data.status || DEFAULT_STATUS,
    };
    const result = await db_1.db.insert(schema_1.marketingIntegrations).values(insertData);
    return (0, exports.getById)(result[0].insertId);
};
exports.create = create;
const update = async (id, data) => {
    if (data.pixelId && data.type && !validatePixelId(data.type, data.pixelId)) {
        throw new Error(`Invalid pixel ID format for ${data.type}`);
    }
    const updateData = { ...data, updatedAt: new Date() };
    if (data.scriptCode) {
        updateData.scriptCode = sanitizeScript(data.scriptCode);
    }
    await db_1.db.update(schema_1.marketingIntegrations).set(updateData).where((0, drizzle_orm_1.eq)(schema_1.marketingIntegrations.id, id));
    return (0, exports.getById)(id);
};
exports.update = update;
const remove = async (id) => {
    await db_1.db.delete(schema_1.marketingIntegrations).where((0, drizzle_orm_1.eq)(schema_1.marketingIntegrations.id, id));
    return { success: true };
};
exports.remove = remove;
const logEvent = async (eventName, platform, payload, status = "success", errorMessage) => {
    await db_1.db.insert(schema_1.trackingLogs).values({
        eventName,
        platform,
        payload,
        status,
        errorMessage: errorMessage || null,
    });
};
exports.logEvent = logEvent;
const getRecentLogs = async (limit = DEFAULT_LOG_LIMIT) => {
    return db_1.db.select().from(schema_1.trackingLogs).orderBy((0, drizzle_orm_1.desc)(schema_1.trackingLogs.createdAt)).limit(limit);
};
exports.getRecentLogs = getRecentLogs;
//# sourceMappingURL=tracking.service.js.map