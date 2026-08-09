import * as trackingService from "../tracking/tracking.service";
import crypto from "crypto";

const FB_GRAPH_API_VERSION = "v21.0";
const FB_EVENT_NAME = "Purchase";
const FB_PLATFORM = "facebook_capi";
const FB_ACTION_SOURCE = "website";
const DEFAULT_CURRENCY = "BDT";
const DEFAULT_CONTENT_TYPE = "product";

interface PurchaseEventData {
  eventId: string;
  currency?: string;
  value: number;
  contentIds: string[];
  contentType?: string;
  userIp: string;
  userAgent: string;
  fbp?: string;
  fbc?: string;
  email?: string;
  phone?: string;
}

function hashSHA256(value: string): string {
  return crypto.createHash("sha256").update(value.trim().toLowerCase()).digest("hex");
}

export const sendFacebookPurchaseEvent = async (data: PurchaseEventData) => {
  const config = await trackingService.getTrackingConfig();

  if (!config.facebookPixelId || !config.facebookAccessToken) {
    return { sent: false, reason: "Facebook Conversion API not configured" };
  }

  const currency = data.currency || DEFAULT_CURRENCY;
  const contentType = data.contentType || DEFAULT_CONTENT_TYPE;

  const eventData: any = {
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

  if (data.fbp) eventData.user_data.fbp = data.fbp;
  if (data.fbc) eventData.user_data.fbc = data.fbc;
  if (data.email) eventData.user_data.em = [hashSHA256(data.email)];
  if (data.phone) eventData.user_data.ph = [hashSHA256(data.phone)];

  const payload: any = { data: [eventData] };
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
  } catch (error: any) {
    await trackingService.logEvent(FB_EVENT_NAME, FB_PLATFORM, payload, "failed", error.message);
    return { sent: false, error: error.message };
  }
};
