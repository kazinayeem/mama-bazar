import { Request, Response } from "express";
import * as checkoutNoticeService from "./checkout-notice.service";
import { AppError } from "../../utils/AppError";

export const getActiveNotices = async (_req: Request, res: Response) => {
  const data = await checkoutNoticeService.getActive();
  res.json({ success: true, data });
};

export const getAll = async (_req: Request, res: Response) => {
  const data = await checkoutNoticeService.getAll();
  res.json({ success: true, data });
};

export const getById = async (req: Request, res: Response) => {
  const data = await checkoutNoticeService.getById(Number(req.params.id));
  if (!data) throw new AppError(404, "Checkout notice not found");
  res.json({ success: true, data });
};

export const create = async (req: Request, res: Response) => {
  const data = await checkoutNoticeService.create(req.body);
  res.status(201).json({ success: true, data });
};

export const update = async (req: Request, res: Response) => {
  const data = await checkoutNoticeService.update(Number(req.params.id), req.body);
  res.json({ success: true, data });
};

export const remove = async (req: Request, res: Response) => {
  await checkoutNoticeService.remove(Number(req.params.id));
  res.json({ success: true, message: "Checkout notice deleted" });
};
