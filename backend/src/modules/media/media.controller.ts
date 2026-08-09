import { Request, Response } from "express";
import * as mediaService from "./media.service";
import { AppError } from "../../utils/AppError";
import { cloudinaryConfig } from "../../utils/cloud";

const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 48;
const DEFAULT_FOLDER = "general";

export const upload = async (req: Request, res: Response) => {
  if (!req.file) throw new AppError(400, "No file uploaded");

  const uploaderId = (req as any).user?.id;
  const folder = (req.body.folder as string) || DEFAULT_FOLDER;
  const alt = req.body.alt as string | undefined;

  const data = await mediaService.saveMedia({
    buffer: req.file.buffer,
    filename: req.file.originalname,
    mimeType: req.file.mimetype,
    size: req.file.size,
    folder,
    alt,
    uploaderId,
  });

  res.status(201).json({ success: true, data });
};

export const uploadMultiple = async (req: Request, res: Response) => {
  const files = req.files as Express.Multer.File[];
  if (!files || files.length === 0) throw new AppError(400, "No files uploaded");

  const uploaderId = (req as any).user?.id;
  const folder = (req.body.folder as string) || DEFAULT_FOLDER;

  const results = [];
  for (const file of files) {
    const data = await mediaService.saveMedia({
      buffer: file.buffer,
      filename: file.originalname,
      mimeType: file.mimetype,
      size: file.size,
      folder,
      uploaderId,
    });
    results.push(data);
  }

  res.status(201).json({ success: true, data: results });
};

export const getAll = async (req: Request, res: Response) => {
  const result = await mediaService.getAll({
    page: Number(req.query.page) || DEFAULT_PAGE,
    limit: Number(req.query.limit) || DEFAULT_LIMIT,
    folder: req.query.folder as string | undefined,
    search: req.query.search as string | undefined,
  });
  res.json({ success: true, ...result });
};

export const getFolders = async (_req: Request, res: Response) => {
  const data = await mediaService.getFolders();
  res.json({ success: true, data });
};

export const getById = async (req: Request, res: Response) => {
  const data = await mediaService.getById(Number(req.params.id));
  if (!data) throw new AppError(404, "Media not found");
  res.json({ success: true, data });
};

export const update = async (req: Request, res: Response) => {
  const data = await mediaService.updateAlt(Number(req.params.id), req.body.alt);
  if (!data) throw new AppError(404, "Media not found");
  res.json({ success: true, data });
};

export const remove = async (req: Request, res: Response) => {
  const result = await mediaService.remove(Number(req.params.id));
  if (!result.success) throw new AppError(404, "Media not found");
  res.json({ success: true, message: "Media deleted" });
};

export const config = async (_req: Request, res: Response) => {
  res.json({ success: true, data: cloudinaryConfig });
};
