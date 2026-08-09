import { Request, Response } from "express";
import jwt from "jsonwebtoken";
import { env } from "../../config/env";
import * as homepageService from "./homepage.service";

const getOptionalUserId = (req: Request): number | null => {
  const authHeader = req.headers.authorization;
  if (!authHeader || !authHeader.startsWith("Bearer ")) return null;
  try {
    const decoded = jwt.verify(authHeader.split(" ")[1], env.JWT_SECRET) as any;
    return decoded?.id ? Number(decoded.id) : null;
  } catch {
    return null;
  }
};

export const getHomepage = async (req: Request, res: Response) => {
  const data = await homepageService.getHomepage(getOptionalUserId(req));
  res.json({ success: true, data });
};

export const getConfig = async (req: Request, res: Response) => {
  const data = await homepageService.getConfig();
  res.json({ success: true, data });
};

export const saveConfig = async (req: Request, res: Response) => {
  const data = await homepageService.saveConfig(req.body);
  res.json({ success: true, data });
};

export const resetConfig = async (req: Request, res: Response) => {
  const data = await homepageService.resetConfig();
  res.json({ success: true, data });
};

export const subscribeNewsletter = async (req: Request, res: Response) => {
  const { email, source } = req.body || {};
  const data = await homepageService.subscribeNewsletter(email, source);
  res.status(data.alreadySubscribed ? 200 : 201).json({ success: true, data });
};

export const getSubscribers = async (req: Request, res: Response) => {
  const data = await homepageService.getSubscribers();
  res.json({ success: true, data });
};
