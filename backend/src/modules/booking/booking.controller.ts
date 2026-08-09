import { Request, Response } from "express";
import * as bookingService from "./booking.service";

export const list = async (req: Request, res: Response) => {
  const result = await bookingService.listBookings({
    page: Number(req.query.page),
    limit: Number(req.query.limit),
    search: req.query.search as string | undefined,
    status: req.query.status as string | undefined,
    paymentStatus: req.query.paymentStatus as string | undefined,
  });
  res.json({ success: true, ...result });
};

export const getById = async (req: Request, res: Response) => {
  const data = await bookingService.getBooking(Number(req.params.id));
  if (!data) return res.status(404).json({ success: false, message: "Booking not found" });
  res.json({ success: true, data });
};

export const create = async (req: Request, res: Response) => {
  const data = await bookingService.createBooking(req.body);
  res.status(201).json({ success: true, data });
};

export const update = async (req: Request, res: Response) => {
  const data = await bookingService.updateBooking(Number(req.params.id), req.body);
  res.json({ success: true, data });
};

export const remove = async (req: Request, res: Response) => {
  await bookingService.deleteBooking(Number(req.params.id));
  res.json({ success: true, message: "Booking deleted" });
};
