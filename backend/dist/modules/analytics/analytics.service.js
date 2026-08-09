"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || (function () {
    var ownKeys = function(o) {
        ownKeys = Object.getOwnPropertyNames || function (o) {
            var ar = [];
            for (var k in o) if (Object.prototype.hasOwnProperty.call(o, k)) ar[ar.length] = k;
            return ar;
        };
        return ownKeys(o);
    };
    return function (mod) {
        if (mod && mod.__esModule) return mod;
        var result = {};
        if (mod != null) for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
        __setModuleDefault(result, mod);
        return result;
    };
})();
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.sendFacebookPurchaseEvent = void 0;
const trackingService = __importStar(require("../tracking/tracking.service"));
const crypto_1 = __importDefault(require("crypto"));
const FB_GRAPH_API_VERSION = "v21.0";
const FB_EVENT_NAME = "Purchase";
const FB_PLATFORM = "facebook_capi";
const FB_ACTION_SOURCE = "website";
const DEFAULT_CURRENCY = "BDT";
const DEFAULT_CONTENT_TYPE = "product";
function hashSHA256(value) {
    return crypto_1.default.createHash("sha256").update(value.trim().toLowerCase()).digest("hex");
}
const sendFacebookPurchaseEvent = async (data) => {
    const config = await trackingService.getTrackingConfig();
    if (!config.facebookPixelId || !config.facebookAccessToken) {
        return { sent: false, reason: "Facebook Conversion API not configured" };
    }
    const currency = data.currency || DEFAULT_CURRENCY;
    const contentType = data.contentType || DEFAULT_CONTENT_TYPE;
    const eventData = {
        event_name: FB_EVENT_NAME,
        event_time: Math.floor(Date.now() / 1000),
        event_id: data.eventId,
        action_source: FB_ACTION_SOURCE,
        user_data: {
            client_ip_address: data.userIp,
            client_user_agent: data.userAgent,
        },
        custom_data: {
            currency,
            value: data.value,
            content_ids: data.contentIds,
            content_type: contentType,
        },
    };
    if (data.fbp)
        eventData.user_data.fbp = data.fbp;
    if (data.fbc)
        eventData.user_data.fbc = data.fbc;
    if (data.email)
        eventData.user_data.em = [hashSHA256(data.email)];
    if (data.phone)
        eventData.user_data.ph = [hashSHA256(data.phone)];
    const payload = { data: [eventData] };
    if (config.facebookTestEventCode) {
        payload.test_event_code = config.facebookTestEventCode;
    }
    const url = `https://graph.facebook.com/${FB_GRAPH_API_VERSION}/${config.facebookPixelId}/events?access_token=${encodeURIComponent(config.facebookAccessToken)}`;
    try {
        const response = await fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
        });
        const result = await response.json();
        if (!response.ok) {
            await trackingService.logEvent(FB_EVENT_NAME, FB_PLATFORM, payload, "failed", JSON.stringify(result));
            return { sent: false, error: result };
        }
        await trackingService.logEvent(FB_EVENT_NAME, FB_PLATFORM, payload, "success");
        return { sent: true, result };
    }
    catch (error) {
        await trackingService.logEvent(FB_EVENT_NAME, FB_PLATFORM, payload, "failed", error.message);
        return { sent: false, error: error.message };
    }
};
exports.sendFacebookPurchaseEvent = sendFacebookPurchaseEvent;
//# sourceMappingURL=analytics.service.js.map