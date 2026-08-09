import { Request, Response } from "express";
import * as settingsService from "./settings.service";

export const getAll = async (req: Request, res: Response) => {
  const data = await settingsService.getAll();
  res.json({ success: true, data });
};

export const get = async (req: Request, res: Response) => {
  const data = await settingsService.get(req.params.key);
  res.json({ success: true, data });
};

export const set = async (req: Request, res: Response) => {
  const { key, value } = req.body;
  const data = await settingsService.set(key, value);
  res.json({ success: true, data });
};

// Slider management
const SLIDER_KEY = "hero_slides";

const getSlides = (): string[] => {
  // Will be fetched async, this is a helper
  return [];
};

export const getHeroSlides = async (req: Request, res: Response) => {
  const setting = await settingsService.get(SLIDER_KEY);
  const slides: string[] = setting?.value ? JSON.parse(setting.value) : [];
  res.json({ success: true, data: slides });
};

export const addHeroSlide = async (req: Request, res: Response) => {
  if (!req.file) {
    res.status(400).json({ success: false, message: "Image is required" });
    return;
  }
  // Store an absolute URL so the browser can load it from the frontend origin.
  const origin = `${req.protocol}://${req.get("host") || "localhost:5000"}`;
  const imageUrl = `${origin}/uploads/${req.file.filename}`;
  const setting = await settingsService.get(SLIDER_KEY);
  const slides: string[] = setting?.value ? JSON.parse(setting.value) : [];
  slides.push(imageUrl);
  await settingsService.set(SLIDER_KEY, JSON.stringify(slides));
  res.json({ success: true, data: slides });
};

export const deleteHeroSlide = async (req: Request, res: Response) => {
  const { index } = req.params;
  const setting = await settingsService.get(SLIDER_KEY);
  const slides: string[] = setting?.value ? JSON.parse(setting.value) : [];
  const idx = parseInt(index, 10);
  if (idx < 0 || idx >= slides.length) {
    res.status(400).json({ success: false, message: "Invalid slide index" });
    return;
  }
  slides.splice(idx, 1);
  await settingsService.set(SLIDER_KEY, JSON.stringify(slides));
  res.json({ success: true, data: slides });
};

  export const addHeroSlideByLink = async (req: Request, res: Response) => {
    const { link } = req.body;
    if (!link || typeof link !== "string") {
      res.status(400).json({ success: false, message: "Image link is required" });
      return;
    }
    const setting = await settingsService.get(SLIDER_KEY);
    const slides: string[] = setting?.value ? JSON.parse(setting.value) : [];
    slides.push(link);
    await settingsService.set(SLIDER_KEY, JSON.stringify(slides));
    res.json({ success: true, data: slides });
  };
