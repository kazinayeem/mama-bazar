import { z } from "zod";
import { PAYMENT_METHODS, ORDER_STATUSES } from "./order.interface";

const BD_PHONE_REGEX = /^(\+880|0)[1-9]\d{9}$/;

const optStr = z.string().optional().nullable();

const orderItemSchema = z.object({
  productId: z.number().int().positive(),
  variantId: z.number().int().positive().optional().nullable(),
  quantity: z.number().int().positive(),
  size: z.string().optional().nullable(),
  color: z.string().optional().nullable(),
});

export const createOrderSchema = z.object({
  body: z.object({
    name: z.string().min(1, "Name is required"),
    phone: z.string().regex(BD_PHONE_REGEX, "Invalid phone number format"),
    alternativePhone: z.string().regex(BD_PHONE_REGEX, "Invalid alternative phone format").optional().nullable(),
    email: z.string().email("Invalid email format").optional().nullable(),
    country: optStr,
    division: optStr,
    district: optStr,
    upazila: optStr,
    area: optStr,
    apartment: optStr,
    postalCode: optStr,
    address: z.string().min(1, "Address is required"),
    shippingArea: z.string().min(1, "Shipping area is required"),
    shippingCost: z.union([z.string(), z.number()]).optional().nullable(),
    shippingMethodId: z.union([z.string(), z.number()]).optional().nullable(),
    couponCode: optStr,
    orderNote: z.string().max(1000).optional().nullable(),
    checkoutNotes: optStr,
    paymentMethod: z.enum(PAYMENT_METHODS).optional(),
    transactionId: optStr,
    senderNumber: z.string().max(30).optional().nullable(),
    paymentScreenshot: z.string().url("Invalid screenshot URL").optional().nullable(),
    amountSent: z.union([z.string(), z.number()]).optional().nullable(),
    paymentInstructions: optStr,
    taxAmount: z.union([z.string(), z.number()]).optional().nullable(),
    items: z.array(orderItemSchema).min(1, "At least one item is required"),
  }),
});

export const updateOrderStatusSchema = z.object({
  params: z.object({ id: z.string() }),
  body: z.object({
    status: z.enum(ORDER_STATUSES),
    note: z.string().max(500).optional(),
    trackingNumber: z.string().max(120).optional(),
  }),
});

export const verifyPaymentSchema = z.object({
  params: z.object({ id: z.string() }),
  body: z.object({
    action: z.enum(["verified", "rejected"]),
    note: z.string().max(500).optional(),
  }),
});

export const adminNoteSchema = z.object({
  params: z.object({ id: z.string() }),
  body: z.object({
    note: z.string().min(1, "Note is required").max(1000),
  }),
});

export const orderIdSchema = z.object({
  params: z.object({ id: z.string() }),
});

export const orderListSchema = z.object({
  query: z.object({
    page: z.string().optional(),
    limit: z.string().optional(),
    status: z.string().optional(),
    search: z.string().optional(),
  }),
});

export const trackOrderSchema = z.object({
  body: z.object({
    orderId: z.string().min(1, "Order ID is required"),
    phone: z.string().regex(BD_PHONE_REGEX, "Invalid phone number format"),
  }),
});
