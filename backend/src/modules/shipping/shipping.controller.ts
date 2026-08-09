import { Request, Response } from "express";
import * as shippingService from "./shipping.service";
import { AppError } from "../../utils/AppError";

export const getActiveMethods = async (_req: Request, res: Response) => {
  const data = await shippingService.getActive();
  res.json({ success: true, data });
};

export const estimateShipping = async (req: Request, res: Response) => {
  const { subtotal } = req.body;
  const data = await shippingService.estimate(Number(subtotal));
  res.json({ success: true, data });
};

export const getAll = async (_req: Request, res: Response) => {
  const data = await shippingService.getAll();
  res.json({ success: true, data });
};

export const getById = async (req: Request, res: Response) => {
  const data = await shippingService.getById(Number(req.params.id));
  if (!data) throw new AppError(404, "Shipping method not found");
  res.json({ success: true, data });
};

export const create = async (req: Request, res: Response) => {
  const data = await shippingService.create(req.body);
  res.status(201).json({ success: true, data });
};

export const update = async (req: Request, res: Response) => {
  const data = await shippingService.update(Number(req.params.id), req.body);
  res.json({ success: true, data });
};

export const remove = async (req: Request, res: Response) => {
  await shippingService.remove(Number(req.params.id));
  res.json({ success: true, message: "Shipping method deleted" });
};
