import { Request, Response } from "express";
import * as pagesService from "./pages.service";
import { AppError } from "../../utils/AppError";

export const getBySlug = async (req: Request, res: Response) => {
  const page = await pagesService.getPublishedBySlug(req.params.slug);
  if (!page) throw new AppError(404, "Page not found");
  res.json({ success: true, data: page });
};

export const getAll = async (req: Request, res: Response) => {
  const pages = await pagesService.getAll();
  res.json({ success: true, data: pages });
};

export const create = async (req: Request, res: Response) => {
  const userId = Number((req as any).user?.id);
  const { slug, title, content, status } = req.body;
  const data = await pagesService.create({ slug, title, content, status, updatedBy: userId });
  res.status(201).json({ success: true, data });
};

export const update = async (req: Request, res: Response) => {
  const userId = Number((req as any).user?.id);
  const data = await pagesService.update(Number(req.params.id), { ...req.body, updatedBy: userId });
  res.json({ success: true, data });
};

export const remove = async (req: Request, res: Response) => {
  const data = await pagesService.remove(Number(req.params.id));
  res.json({ success: true, data });
};

export const submitContact = async (req: Request, res: Response) => {
  const data = await pagesService.createContactMessage(req.body);
  res.status(201).json({ success: true, data, message: "আপনার বার্তাটি পেয়েছি, শীঘ্রই যোগাযোগ করব।" });
};

export const getContactMessages = async (req: Request, res: Response) => {
  const messages = await pagesService.getContactMessages();
  res.json({ success: true, data: messages });
};

export const updateContactStatus = async (req: Request, res: Response) => {
  const data = await pagesService.setContactMessageStatus(Number(req.params.id), req.body.status);
  res.json({ success: true, data });
};