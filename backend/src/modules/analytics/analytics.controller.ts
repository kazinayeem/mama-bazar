import { Request, Response } from "express";
import crypto from "crypto";
import * as analyticsService from "./analytics.service";

export const trackPurchase = async (req: Request, res: Response) => {
  const { currency, value, contentIds, contentType, fbp, fbc, email, phone } = req.body;

  const result = await analyticsService.sendFacebookPurchaseEvent({
    eventId: crypto.randomUUID(),
    currency,
    value: Number(value),
    contentIds: Array.isArray(contentIds) ? contentIds : [contentIds],
    contentType,
    userIp: (req.headers["x-forwarded-for"] as string)?.split(",")[0]?.trim() || req.socket.remoteAddress || "",
    userAgent: req.headers["user-agent"] || "",
    fbp,
    fbc,
    email,
    phone,
  });

  res.json({ success: true, data: result });
};
