import { Request, Response } from "express";
import * as orderService from "./order.service";
import jwt from "jsonwebtoken";
import { env } from "../../config/env";
import { AppError } from "../../utils/AppError";
import { CreateOrderInput } from "./order.interface";

const DEFAULT_PAYMENT_METHOD = "cod";
const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 20;

const extractUserId = (req: Request): number | undefined => {
  const authHeader = req.headers.authorization;
  if (authHeader?.startsWith("Bearer ")) {
    const token = authHeader.split(" ")[1];
    try {
      const decoded = jwt.verify(token, env.JWT_SECRET) as any;
      return decoded.id;
    } catch {
      // Token is invalid or expired, proceed as guest
    }
  }
  return undefined;
};

const str = (v: any) => (v === undefined || v === null || v === "" ? undefined : String(v));

export const create = async (req: Request, res: Response) => {
  const body = req.body;
  const input: CreateOrderInput = {
    userId: extractUserId(req),
    name: body.name,
    phone: body.phone,
    alternativePhone: str(body.alternativePhone),
    email: str(body.email),
    country: str(body.country),
    division: str(body.division),
    district: str(body.district),
    upazila: str(body.upazila),
    area: str(body.area),
    apartment: str(body.apartment),
    postalCode: str(body.postalCode),
    address: body.address,
    shippingArea: body.shippingArea,
    shippingCost: body.shippingCost !== undefined ? Number(body.shippingCost) : undefined,
    shippingMethodId: body.shippingMethodId !== undefined ? Number(body.shippingMethodId) : undefined,
    couponCode: str(body.couponCode),
    orderNote: str(body.orderNote),
    checkoutNotes: str(body.checkoutNotes),
    paymentMethod: body.paymentMethod || DEFAULT_PAYMENT_METHOD,
    transactionId: str(body.transactionId),
    senderNumber: str(body.senderNumber),
    paymentScreenshot: str(body.paymentScreenshot),
    amountSent: body.amountSent !== undefined ? Number(body.amountSent) : undefined,
    paymentInstructions: str(body.paymentInstructions),
    taxAmount: body.taxAmount !== undefined ? Number(body.taxAmount) : undefined,
    items: body.items,
  };

  const result = await orderService.create(input);

  const message = result.auth
    ? "Order placed successfully"
    : "Order placed successfully";

  res.status(201).json({ success: true, data: result, message });
};

export const getAll = async (req: Request, res: Response) => {
  const result = await orderService.getAll(
    Number(req.query.page) || DEFAULT_PAGE,
    Number(req.query.limit) || DEFAULT_LIMIT,
    req.query.status as string,
    req.query.search as string
  );
  res.json({ success: true, ...result });
};

export const getById = async (req: Request, res: Response) => {
  const order = await orderService.getById(Number(req.params.id));
  if (!order) throw new AppError(404, "Order not found");
  res.json({ success: true, data: order });
};

export const updateStatus = async (req: Request, res: Response) => {
  const actorUserId = (req as any).user?.id;
  const order = await orderService.updateStatus(Number(req.params.id), {
    status: req.body.status,
    note: req.body.note,
    trackingNumber: req.body.trackingNumber,
    userId: actorUserId,
  });
  res.json({ success: true, data: order });
};

export const verifyPayment = async (req: Request, res: Response) => {
  const actorUserId = (req as any).user?.id;
  const order = await orderService.verifyPayment(Number(req.params.id), {
    action: req.body.action,
    note: req.body.note,
    userId: actorUserId,
  });
  res.json({ success: true, data: order });
};

export const addAdminNote = async (req: Request, res: Response) => {
  const actorUserId = (req as any).user?.id;
  const order = await orderService.updateAdminNotes(Number(req.params.id), req.body.note, actorUserId);
  res.json({ success: true, data: order });
};

export const getMyOrders = async (req: Request, res: Response) => {
  const userId = (req as any).user?.id;
  if (!userId) throw new AppError(401, "Please sign in to view your orders");
  const data = await orderService.getMyOrders(userId);
  res.json({ success: true, data });
};

export const trackOrder = async (req: Request, res: Response) => {
  const { orderId, phone } = req.body;
  const data = await orderService.trackOrder(orderId, phone);
  res.json({ success: true, data });
};

export const getInvoice = async (req: Request, res: Response) => {
  const order = await orderService.getInvoice(Number(req.params.id));
  res.json({ success: true, data: order });
};

export const remove = async (req: Request, res: Response) => {
  await orderService.remove(Number(req.params.id));
  res.json({ success: true, message: "Order deleted" });
};

export const getStats = async (_req: Request, res: Response) => {
  const stats = await orderService.getStats();
  res.json({ success: true, data: stats });
};
