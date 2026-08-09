import { Request, Response } from "express";
import * as bannerService from "./banner.service";
import { AppError } from "../../utils/AppError";
import { uploadBuffer } from "../../utils/cloud";

const persistImages = async (
  req: Request
): Promise<Record<string, string>> => {
  const files = (req.files || {}) as Record<string, Express.Multer.File[]>;
  const pick = (field: string) => files[field]?.[0];
  const result: Record<string, string> = {};

  for (const field of ["image", "imageTablet", "imageMobile"]) {
    const file = pick(field);
    if (!file) continue;
    const uploaded = await uploadBuffer(file.buffer, {
      folder: "banners",
      filename: file.originalname,
      mimeType: file.mimetype,
    });
    const key = field === "imageTablet" ? "imageTablet" : field === "imageMobile" ? "imageMobile" : "image";
    result[key] = uploaded.url;
  }

  return result;
};

export const getAll = async (_req: Request, res: Response) => {
  const data = await bannerService.getAll();
  res.json({ success: true, data });
};

export const getById = async (req: Request, res: Response) => {
  const data = await bannerService.getById(Number(req.params.id));
  if (!data) throw new AppError(404, "Banner not found");
  res.json({ success: true, data });
};

export const create = async (req: Request, res: Response) => {
  const { title, subtitle, link, position, buttonText, priority, status } = req.body;
  const images = await persistImages(req);

  if (!images.image) {
    const url = (["image", "imageTablet", "imageMobile"] as const).find((k) => req.body[k]);
    if (url) images[url] = req.body[url];
  }

  if (!images.image) throw new AppError(400, "Banner image is required");

  const data = await bannerService.create({
    title,
    subtitle,
    link,
    position: position || "hero",
    buttonText,
    priority: Number(priority) || 0,
    status: status || "active",
    image: images.image,
    imageMobile: images.imageMobile,
    imageTablet: images.imageTablet,
  });
  res.status(201).json({ success: true, data });
};

export const update = async (req: Request, res: Response) => {
  const id = Number(req.params.id);
  const { title, subtitle, link, position, buttonText, priority, status } = req.body;
  const images = await persistImages(req);

  const updateData: any = {};
  if (title !== undefined) updateData.title = title;
  if (subtitle !== undefined) updateData.subtitle = subtitle;
  if (link !== undefined) updateData.link = link;
  if (position !== undefined) updateData.position = position;
  if (buttonText !== undefined) updateData.buttonText = buttonText;
  if (priority !== undefined) updateData.priority = Number(priority);
  if (status !== undefined) updateData.status = status;
  for (const field of ["image", "imageMobile", "imageTablet"] as const) {
    if (images[field]) updateData[field] = images[field];
    else if (req.body[field]) updateData[field] = req.body[field];
  }

  const data = await bannerService.update(id, updateData);
  res.json({ success: true, data });
};

export const remove = async (req: Request, res: Response) => {
  await bannerService.remove(Number(req.params.id));
  res.json({ success: true, message: "Banner deleted" });
};
