import { Request, Response } from "express";
import * as trackingService from "./tracking.service";
import { AppError } from "../../utils/AppError";

const TYPES_NEEDING_PIXEL_ID = ["google_tag_manager", "google_analytics", "facebook_pixel", "tiktok_pixel"];
const DEFAULT_LOG_LIMIT = 50;

export const getConfig = async (req: Request, res: Response) => {
  const config = await trackingService.getTrackingConfig();
  const { facebookAccessToken, facebookTestEventCode, ...publicConfig } = config;
  res.json({ success: true, data: publicConfig });
};

export const getAll = async (req: Request, res: Response) => {
  const data = await trackingService.getAll();
  res.json({ success: true, data });
};

export const getById = async (req: Request, res: Response) => {
  const data = await trackingService.getById(Number(req.params.id));
  if (!data) throw new AppError(404, "Integration not found");
  res.json({ success: true, data });
};

export const create = async (req: Request, res: Response) => {
  const { name, type, pixelId, scriptCode, accessToken, testEventCode, status } = req.body;
  if (TYPES_NEEDING_PIXEL_ID.includes(type) && !pixelId) {
    throw new AppError(400, "Pixel/Measurement ID is required");
  }
  if (type === "custom_script" && !scriptCode) {
    throw new AppError(400, "Script code is required");
  }
  if (type === "facebook_conversion_api" && !accessToken) {
    throw new AppError(400, "Access token is required");
  }
  const data = await trackingService.create({ name, type, pixelId, scriptCode, accessToken, testEventCode, status });
  res.status(201).json({ success: true, data });
};

export const update = async (req: Request, res: Response) => {
  const data = await trackingService.update(Number(req.params.id), req.body);
  res.json({ success: true, data });
};

export const remove = async (req: Request, res: Response) => {
  await trackingService.remove(Number(req.params.id));
  res.json({ success: true, message: "Integration deleted" });
};

export const getLogs = async (req: Request, res: Response) => {
  const data = await trackingService.getRecentLogs(Number(req.query.limit) || DEFAULT_LOG_LIMIT);
  res.json({ success: true, data });
};
