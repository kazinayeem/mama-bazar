import { Request, Response } from "express";
import * as rentalService from "./rental.service";

export const list = async (req: Request, res: Response) => {
  const result = await rentalService.listRentals({
    page: Number(req.query.page),
    limit: Number(req.query.limit),
    search: req.query.search as string | undefined,
    status: req.query.status as string | undefined,
    paymentStatus: req.query.paymentStatus as string | undefined,
  });
  res.json({ success: true, ...result });
};

export const getById = async (req: Request, res: Response) => {
  const data = await rentalService.getRental(Number(req.params.id));
  if (!data) return res.status(404).json({ success: false, message: "Rental not found" });
  res.json({ success: true, data });
};

export const create = async (req: Request, res: Response) => {
  const data = await rentalService.createRental(req.body);
  res.status(201).json({ success: true, data });
};

export const update = async (req: Request, res: Response) => {
  const data = await rentalService.updateRental(Number(req.params.id), req.body);
  res.json({ success: true, data });
};

export const remove = async (req: Request, res: Response) => {
  await rentalService.deleteRental(Number(req.params.id));
  res.json({ success: true, message: "Rental deleted" });
};
