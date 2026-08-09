import { Request, Response } from "express";
import * as paymentService from "./payment.service";
import { AppError } from "../../utils/AppError";

export const getActiveMethods = async (_req: Request, res: Response) => {
  const data = await paymentService.getActive();
  res.json({ success: true, data });
};

export const getAll = async (_req: Request, res: Response) => {
  const data = await paymentService.getAll();
  res.json({ success: true, data });
};

export const getById = async (req: Request, res: Response) => {
  const data = await paymentService.getById(Number(req.params.id));
  if (!data) throw new AppError(404, "Payment method not found");
  res.json({ success: true, data });
};

export const create = async (req: Request, res: Response) => {
  const data = await paymentService.create(req.body);
  res.status(201).json({ success: true, data });
};

export const update = async (req: Request, res: Response) => {
  const data = await paymentService.update(Number(req.params.id), req.body);
  res.json({ success: true, data });
};

export const updateStatuses = async (req: Request, res: Response) => {
  const { ids, enabled } = req.body;
  const data = await paymentService.setStatuses(ids, enabled);
  res.json({ success: true, data });
};

export const remove = async (req: Request, res: Response) => {
  await paymentService.remove(Number(req.params.id));
  res.json({ success: true, message: "Payment method deleted" });
};
