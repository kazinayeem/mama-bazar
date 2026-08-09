import { Request, Response } from "express";
import * as costService from "./cost.service";

export const list = async (req: Request, res: Response) => {
  const result = await costService.listCosts({
    page: Number(req.query.page),
    limit: Number(req.query.limit),
    search: req.query.search as string | undefined,
    costType: req.query.costType as string | undefined,
  });
  res.json({ success: true, ...result });
};

export const getById = async (req: Request, res: Response) => {
  const data = await costService.getCost(Number(req.params.id));
  if (!data) return res.status(404).json({ success: false, message: "Cost not found" });
  res.json({ success: true, data });
};

export const create = async (req: Request, res: Response) => {
  const data = await costService.createCost(req.body);
  res.status(201).json({ success: true, data });
};

export const update = async (req: Request, res: Response) => {
  const data = await costService.updateCost(Number(req.params.id), req.body);
  res.json({ success: true, data });
};

export const remove = async (req: Request, res: Response) => {
  await costService.deleteCost(Number(req.params.id));
  res.json({ success: true, message: "Cost deleted" });
};
