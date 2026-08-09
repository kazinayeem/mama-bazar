import { Request, Response } from "express";
import * as reviewService from "./review.service";
import { AppError } from "../../utils/AppError";

export const getAll = async (req: Request, res: Response) => {
  const { page, limit, productId, search } = req.query;
  const result = await reviewService.getAll({
    page: page ? Number(page) : undefined,
    limit: limit ? Number(limit) : undefined,
    productId: productId ? Number(productId) : undefined,
    search: search ? String(search) : undefined,
    status: "approved",
  });
  res.json({ success: true, data: result.data, pagination: result.pagination });
};

export const getAllAdmin = async (req: Request, res: Response) => {
  const { page, limit, status, search } = req.query;
  const result = await reviewService.getAll({
    page: page ? Number(page) : undefined,
    limit: limit ? Number(limit) : undefined,
    status: (status as "pending" | "approved" | "rejected") || undefined,
    search: search ? String(search) : undefined,
  });
  res.json({ success: true, data: result.data, pagination: result.pagination });
};

export const create = async (req: Request, res: Response) => {
  const { productId, rating, title, comment } = req.body;
  const user = (req as any).user;
  const data = await reviewService.create({
    productId: Number(productId),
    userId: user?.id ?? null,
    customerName: user?.name ?? req.body.customerName,
    rating: Number(rating),
    title,
    comment,
  });
  res.status(201).json({ success: true, data, message: "Review submitted and pending approval" });
};

export const updateStatus = async (req: Request, res: Response) => {
  const { status } = req.body;
  const data = await reviewService.updateStatus(Number(req.params.id), status);
  res.json({ success: true, data });
};

export const remove = async (req: Request, res: Response) => {
  await reviewService.remove(Number(req.params.id));
  res.json({ success: true, message: "Review deleted" });
};

export const getById = async (req: Request, res: Response) => {
  const data = await reviewService.getById(Number(req.params.id));
  if (!data) throw new AppError(404, "Review not found");
  res.json({ success: true, data });
};
