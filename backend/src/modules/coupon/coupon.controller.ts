import { Request, Response } from "express";
import * as couponService from "./coupon.service";
import { AppError } from "../../utils/AppError";

export const validateCoupon = async (req: Request, res: Response) => {
  const { code, subtotal } = req.body;
  const result = await couponService.validate(code, Number(subtotal));
  res.json({ success: true, data: result });
};

export const getAll = async (req: Request, res: Response) => {
  const data = await couponService.getAll();
  res.json({ success: true, data });
};

export const getById = async (req: Request, res: Response) => {
  const data = await couponService.getById(Number(req.params.id));
  if (!data) throw new AppError(404, "Coupon not found");
  res.json({ success: true, data });
};

export const create = async (req: Request, res: Response) => {
  const { code, discountType, discountValue, minOrderAmount, expiryDate, status } = req.body;
  const data = await couponService.create({
    code: code.toUpperCase(),
    discountType,
    discountValue: String(discountValue),
    minOrderAmount: minOrderAmount ? String(minOrderAmount) : undefined,
    expiryDate,
    status,
  });
  res.status(201).json({ success: true, data });
};

export const update = async (req: Request, res: Response) => {
  const data = await couponService.update(Number(req.params.id), req.body);
  res.json({ success: true, data });
};

export const remove = async (req: Request, res: Response) => {
  await couponService.remove(Number(req.params.id));
  res.json({ success: true, message: "Coupon deleted" });
};
