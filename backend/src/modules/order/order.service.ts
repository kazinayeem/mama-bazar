import { db } from "../../config/db";
import { orders, orderItems, products, productVariants, coupons, users, userAddresses, orderStatusHistory, shippingMethods, paymentMethods } from "../../config/schema";
import { eq, desc, asc, sql, and, or, like, inArray } from "drizzle-orm";
import { CreateOrderInput, UpdateOrderStatusInput, VerifyPaymentInput, OrderStatus, PaymentMethod } from "./order.interface";
import { AppError } from "../../utils/AppError";
import bcrypt from "bcryptjs";
import jwt from "jsonwebtoken";
import { env } from "../../config/env";

const DEFAULT_STATUS = "pending";
const PERCENTAGE = "percentage";
const DELIVERED_STATUS = "delivered";
const DEFAULT_ROLE = "user";
const SALT_ROUNDS = 12;
const TOKEN_EXPIRY = "7d";
const MAX_TAX_RATE = 25;

const ORDER_PROGRESS_FLOW: OrderStatus[] = [
  "pending",
  "payment_pending",
  "payment_verification",
  "confirmed",
  "processing",
  "packed",
  "shipped",
  "out_for_delivery",
  "delivered",
];

const getBackfillChainForStatus = (status: OrderStatus): OrderStatus[] => {
  if (status === "cancelled") {
    return ["pending", "confirmed", "cancelled"];
  }
  if (status === "refunded") {
    return ["pending", "confirmed", "delivered", "refunded"];
  }
  if (status === "returned") {
    return ["pending", "confirmed", "delivered", "returned"];
  }

  const index = ORDER_PROGRESS_FLOW.indexOf(status);
  if (index === -1) return [status];
  return ORDER_PROGRESS_FLOW.slice(0, index + 1);
};

const STATUS_TRANSITIONS: Record<OrderStatus, OrderStatus[]> = {
  pending: ["pending", "confirmed", "payment_pending", "cancelled"],
  payment_pending: ["payment_pending", "payment_verification", "confirmed", "cancelled"],
  payment_verification: ["payment_verification", "confirmed", "cancelled"],
  confirmed: ["confirmed", "processing", "cancelled"],
  processing: ["processing", "packed", "cancelled"],
  packed: ["packed", "shipped", "cancelled"],
  shipped: ["shipped", "out_for_delivery", "cancelled"],
  out_for_delivery: ["out_for_delivery", "delivered", "cancelled", "returned"],
  delivered: ["delivered", "returned", "refunded"],
  returned: ["returned", "refunded", "cancelled"],
  cancelled: ["cancelled"],
  refunded: ["refunded"],
};

const ONLINE_PAYMENT_METHODS: PaymentMethod[] = ["bkash", "nagad", "rocket", "bank", "stripe", "sslcommerz", "paypal"];

type CheckoutAuth = {
  token: string;
  user: {
    id: number;
    name: string;
    phone: string;
    role: "admin" | "manager" | "user";
  };
};

function generateOrderId(): string {
  const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
  let result = "GHB-";
  for (let i = 0; i < 6; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return result;
}

const toNum = (v: string | null | undefined, fallback = 0) => {
  const n = Number(v);
  return Number.isFinite(n) ? n : fallback;
};

const getOrderHistory = async (orderId: number) => {
  const logs = await db
    .select()
    .from(orderStatusHistory)
    .where(eq(orderStatusHistory.orderId, orderId))
    .orderBy(asc(orderStatusHistory.createdAt));

  return logs.map((log) => ({
    id: log.id,
    status: log.status,
    note: log.note,
    createdAt: log.createdAt,
    createdByUserId: log.createdByUserId,
  }));
};

const getTimelineWithFallback = async (
  orderId: number,
  fallbackStatus: OrderStatus,
  fallbackCreatedAt: Date
) => {
  const logs = await getOrderHistory(orderId);

  if (logs.length === 1 && fallbackStatus !== "pending") {
    const syntheticChain = getBackfillChainForStatus(fallbackStatus);

    return syntheticChain.map((status, index) => ({
      id: index + 1,
      status,
      note: index === 0 ? "Order created" : `Backfilled ${status} step`,
      createdAt: new Date(fallbackCreatedAt.getTime() + index * 20 * 60 * 1000),
      createdByUserId: null,
    }));
  }

  if (logs.length > 0) {
    const lastLog = logs[logs.length - 1];
    const lastStatus = lastLog.status as OrderStatus;

    if (fallbackStatus === "cancelled" || fallbackStatus === "refunded" || fallbackStatus === "returned") {
      if (lastStatus !== fallbackStatus) {
        const stepTime = new Date(new Date(lastLog.createdAt).getTime() + 20 * 60 * 1000);
        return [
          ...logs,
          {
            id: Number(lastLog.id) + 1,
            status: fallbackStatus,
            note: `Backfilled ${fallbackStatus} step`,
            createdAt: stepTime,
            createdByUserId: null,
          },
        ];
      }
      return logs;
    }

    const lastIndex = ORDER_PROGRESS_FLOW.indexOf(lastStatus);
    const targetIndex = ORDER_PROGRESS_FLOW.indexOf(fallbackStatus);

    if (lastIndex !== -1 && targetIndex > lastIndex) {
      const padded = [...logs];
      let currentTime = new Date(new Date(lastLog.createdAt).getTime());
      let nextId = Number(lastLog.id) + 1;

      for (let index = lastIndex + 1; index <= targetIndex; index++) {
        currentTime = new Date(currentTime.getTime() + 20 * 60 * 1000);
        padded.push({
          id: nextId,
          status: ORDER_PROGRESS_FLOW[index],
          note: `Backfilled ${ORDER_PROGRESS_FLOW[index]} step`,
          createdAt: currentTime,
          createdByUserId: null,
        });
        nextId += 1;
      }

      return padded;
    }
  }

  if (logs.length > 0) return logs;

  return [
    {
      id: 0,
      status: fallbackStatus,
      note: "Order created",
      createdAt: fallbackCreatedAt,
      createdByUserId: null,
    },
  ];
};

const attachItemsAndHistory = async (order: any) => {
  const items = await db
    .select({
      id: orderItems.id,
      orderId: orderItems.orderId,
      productId: orderItems.productId,
      variantId: orderItems.variantId,
      size: orderItems.size,
      color: orderItems.color,
      quantity: orderItems.quantity,
      price: orderItems.price,
      productTitle: products.title,
      productImages: products.images,
      variantName: productVariants.name,
    })
    .from(orderItems)
    .leftJoin(products, eq(orderItems.productId, products.id))
    .leftJoin(productVariants, eq(orderItems.variantId, productVariants.id))
    .where(eq(orderItems.orderId, order.id));

  const statusHistory = await getTimelineWithFallback(order.id, order.status as OrderStatus, order.createdAt);

  return {
    ...order,
    statusHistory,
    historyCount: statusHistory.length,
    items: items.map((i) => {
      let image: string | null = null;
      if (Array.isArray(i.productImages)) image = i.productImages[0] || null;
      else if (typeof i.productImages === "string" && i.productImages) {
        try {
          const parsed = JSON.parse(i.productImages);
          image = Array.isArray(parsed) ? parsed[0] || null : null;
        } catch {
          image = null;
        }
      }
      return {
        id: i.id,
        orderId: i.orderId,
        productId: i.productId,
        variantId: i.variantId,
        size: i.size,
        color: i.color,
        quantity: i.quantity,
        price: i.price,
        variantName: i.variantName || null,
        product: i.productTitle ? { title: i.productTitle, image } : null,
      };
    }),
  };
};

// ==================== CREATE (FULL CHECKOUT) ====================

export const create = async (input: CreateOrderInput) => {
  // ---------- 1. Validate items & compute subtotal ----------
  let subtotal = 0;
  const itemsWithPrice: { productId: number; variantId?: number; quantity: number; size?: string; color?: string; price: number }[] = [];

  // Batch fetch all products upfront to avoid N+1 queries
  const productIds = [...new Set(input.items.map(item => item.productId))];
  const allProducts = await db.select().from(products).where(inArray(products.id, productIds));
  const productMap = new Map(allProducts.map(p => [p.id, p]));

  // Collect all variant IDs that are explicitly provided
  const explicitVariantIds = input.items
    .filter(item => item.variantId)
    .map(item => item.variantId!);

  // Batch fetch explicit variants
  const allExplicitVariants = explicitVariantIds.length > 0
    ? await db.select().from(productVariants).where(inArray(productVariants.id, explicitVariantIds))
    : [];
  const explicitVariantMap = new Map(allExplicitVariants.map(v => [v.id, v]));

  // Collect product IDs that need size/color variant lookup
  const productIdsNeedingVariantLookup = input.items
    .filter(item => !item.variantId && (item.size || item.color))
    .map(item => item.productId);

  // Batch fetch variants for size/color lookup
  const allSizeColorVariants = productIdsNeedingVariantLookup.length > 0
    ? await db.select().from(productVariants).where(
        and(
          inArray(productVariants.productId, productIdsNeedingVariantLookup),
          eq(productVariants.status, "active")
        )
      )
    : [];
  // Group variants by product ID for efficient lookup
  const variantsByProduct = new Map<number, typeof allSizeColorVariants>();
  for (const v of allSizeColorVariants) {
    const existing = variantsByProduct.get(v.productId) || [];
    existing.push(v);
    variantsByProduct.set(v.productId, existing);
  }

  for (const item of input.items) {
    const product = productMap.get(item.productId);
    if (!product) throw new AppError(400, `Product ${item.productId} not found`);

    let itemPrice: number;

    if (item.variantId) {
      const variant = explicitVariantMap.get(item.variantId);
      if (!variant) throw new AppError(400, `Variant not found for product ${product.title}`);
      if (variant.availability === false) throw new AppError(400, `Variant "${variant.name}" is not available`);
      if (variant.stock < item.quantity) throw new AppError(400, `Insufficient stock for ${product.title} - ${variant.name}`);
      itemPrice = variant.discountPrice ? Number(variant.discountPrice) : (variant.price ? Number(variant.price) : Number(product.price));
    } else if (item.size || item.color) {
      const productVariantsList = variantsByProduct.get(item.productId) || [];
      const variant = productVariantsList.find((v) => {
        const opts = v.options as Record<string, string>;
        const sizeMatch = !item.size || Object.values(opts).some((val) => val.toLowerCase() === item.size!.toLowerCase());
        const colorMatch = !item.color || Object.values(opts).some((val) => val.toLowerCase() === item.color!.toLowerCase());
        return sizeMatch && colorMatch;
      });
      if (!variant) throw new AppError(400, `Variant (${item.size}, ${item.color}) not found for ${product.title}`);
      if (variant.availability === false) throw new AppError(400, `Variant "${variant.name}" is not available`);
      if (variant.stock < item.quantity) throw new AppError(400, `Insufficient stock for ${product.title} - ${variant.name}`);
      itemPrice = variant.discountPrice ? Number(variant.discountPrice) : (variant.price ? Number(variant.price) : Number(product.price));
      // Store the resolved variant ID
      item.variantId = variant.id;
    } else {
      if (product.stock < item.quantity) throw new AppError(400, `Insufficient stock for ${product.title}`);
      const discountRate = Math.min(Number(product.discount || 0), 100);
      itemPrice = Math.round(Number(product.price) * (1 - discountRate / 100));
    }

    const itemTotal = itemPrice * item.quantity;
    subtotal += itemTotal;
    itemsWithPrice.push({
      productId: item.productId,
      variantId: item.variantId,
      quantity: item.quantity,
      size: item.size,
      color: item.color,
      price: itemPrice,
    });
  }

  // ---------- 2. Shipping method ----------
  let shippingCost = Number(input.shippingCost) || 0;
  let shippingMethodName: string | null = null;
  let shippingMethodId: number | null = null;

  if (input.shippingMethodId) {
    const methodRows = await db
      .select()
      .from(shippingMethods)
      .where(and(eq(shippingMethods.id, Number(input.shippingMethodId)), eq(shippingMethods.status, "active")))
      .limit(1);
    const method = methodRows[0];
    if (!method) throw new AppError(400, "Shipping method is not available");

    if (input.paymentMethod === "cod" && !method.codAvailable) {
      throw new AppError(400, `${method.name} does not support Cash on Delivery`);
    }

    shippingMethodId = method.id;
    shippingMethodName = method.name;
    const freeMin = method.freeShippingMinAmount === null ? null : toNum(method.freeShippingMinAmount);
    shippingCost = freeMin !== null && subtotal >= freeMin ? 0 : toNum(method.charge);
  }

  // ---------- 3. Payment method ----------
  const paymentMethodCode = input.paymentMethod || "cod";
  const paymentMethodRows = await db
    .select()
    .from(paymentMethods)
    .where(eq(paymentMethods.code, paymentMethodCode))
    .limit(1);
  const paymentMethod = paymentMethodRows[0];
  if (!paymentMethod) throw new AppError(400, "Payment method is not available");
  if (!paymentMethod.enabled || paymentMethod.maintenanceMode) {
    throw new AppError(400, `${paymentMethod.name} is currently unavailable`);
  }

  // ---------- 4. Coupon ----------
  let discount = 0;
  if (input.couponCode) {
    const couponRows = await db.select().from(coupons).where(
      and(
        eq(coupons.code, input.couponCode),
        eq(coupons.status, "active")
      )
    ).limit(1);
    const coupon = couponRows[0];

    if (coupon) {
      if (coupon.expiryDate && new Date(coupon.expiryDate) < new Date()) {
        throw new AppError(400, "Coupon has expired");
      }
      if (coupon.minOrderAmount && subtotal < Number(coupon.minOrderAmount)) {
        throw new AppError(400, `Minimum order amount is ${coupon.minOrderAmount} Tk`);
      }
      if (coupon.discountType === PERCENTAGE) {
        discount = (subtotal * Number(coupon.discountValue)) / 100;
      } else {
        discount = Number(coupon.discountValue);
      }
      discount = Math.min(discount, subtotal);
    }
  }

  // ---------- 5. Tax ----------
  let tax = Number(input.taxAmount) || 0;
  if (tax < 0) tax = 0;
  if (tax > (subtotal - discount) * (MAX_TAX_RATE / 100)) {
    tax = (subtotal - discount) * (MAX_TAX_RATE / 100);
  }

  const totalPrice = subtotal - discount + tax + shippingCost;

  // ---------- 6. Payment state ----------
  const isOnline = ONLINE_PAYMENT_METHODS.includes(paymentMethodCode);
  let paymentStatus: string;
  let orderStatus: OrderStatus;

  if (paymentMethodCode === "cod") {
    paymentStatus = "success";
    orderStatus = "pending";
  } else if (input.paymentScreenshot) {
    paymentStatus = "payment_verification";
    orderStatus = "payment_verification";
  } else if (input.transactionId) {
    paymentStatus = "verified";
    orderStatus = "pending";
  } else {
    paymentStatus = "payment_pending";
    orderStatus = "payment_pending";
  }

  // ---------- 7. Resolve user (true guest checkout - no auto-create) ----------
  let resolvedUserId = input.userId || null;
  let checkoutAuth: CheckoutAuth | null = null;

  // Only auto-login existing users; never auto-create accounts for guests
  if (!resolvedUserId && input.phone) {
    const existingRows = await db.select().from(users).where(eq(users.phone, input.phone)).limit(1);
    const existingUser = existingRows[0];

    if (existingUser) {
      // User exists but is not logged in - link the order to their account
      resolvedUserId = existingUser.id;
      // Do NOT auto-login - guest remains a guest
    }
    // If no user exists, resolvedUserId stays null - true guest order
  }

  // ---------- 8. Update user + save address (only for logged-in users) ----------
  if (resolvedUserId) {
    await db
      .update(users)
      .set({
        shippingArea: input.shippingArea,
        shippingAddress: input.address,
      })
      .where(eq(users.id, resolvedUserId));

    const existingAddresses = await db
      .select()
      .from(userAddresses)
      .where(eq(userAddresses.userId, resolvedUserId));

    const alreadyExists = existingAddresses.some(
      (a) => a.address === input.address && a.shippingArea === input.shippingArea && a.phone === input.phone
    );

    if (!alreadyExists && existingAddresses.length < 5) {
      await db.insert(userAddresses).values({
        userId: resolvedUserId,
        recipientName: input.name,
        phone: input.phone,
        alternativePhone: input.alternativePhone || null,
        email: input.email || null,
        country: input.country || null,
        division: input.division || null,
        district: input.district || null,
        upazila: input.upazila || null,
        area: input.area || null,
        shippingArea: input.shippingArea,
        address: input.address,
        apartment: input.apartment || null,
        postalCode: input.postalCode || null,
        isDefault: existingAddresses.length === 0,
      });
    }
  }

  // ---------- 9. Insert order ----------
  const orderResult = await db.insert(orders).values({
    orderId: generateOrderId(),
    userId: resolvedUserId || null,
    customerName: input.name,
    phone: input.phone,
    alternativePhone: input.alternativePhone || null,
    email: input.email || null,
    country: input.country || null,
    division: input.division || null,
    district: input.district || null,
    upazila: input.upazila || null,
    area: input.area || null,
    address: input.address,
    apartment: input.apartment || null,
    postalCode: input.postalCode || null,
    shippingMethodId,
    shippingMethodName,
    shippingCost: String(shippingCost),
    subtotal: String(subtotal),
    couponCode: input.couponCode || null,
    discount: String(discount),
    tax: String(tax),
    orderNote: input.orderNote || null,
    checkoutNotes: input.checkoutNotes || null,
    totalPrice: String(totalPrice),
    paymentMethod: paymentMethodCode,
    transactionId: input.transactionId || null,
    senderNumber: input.senderNumber || null,
    paymentScreenshot: input.paymentScreenshot || null,
    amountSent: input.amountSent !== undefined ? String(input.amountSent) : null,
    paymentInstructions: input.paymentInstructions || null,
    paymentStatus: paymentStatus as any,
    status: orderStatus,
  });

  const orderId = orderResult[0].insertId;

  const historyNote = [
    "Order created",
    discount > 0 ? `with discount Tk ${discount}` : null,
    orderStatus === "payment_pending" ? "- awaiting payment" : null,
    orderStatus === "payment_verification" ? "- payment submitted, awaiting verification" : null,
  ]
    .filter(Boolean)
    .join(" ");

  await db.insert(orderStatusHistory).values({
    orderId,
    status: orderStatus,
    note: historyNote,
    createdByUserId: resolvedUserId || null,
  });

  // ---------- 10. Items + stock (bulk operations) ----------
  // Bulk insert order items
  if (itemsWithPrice.length > 0) {
    await db.insert(orderItems).values(
      itemsWithPrice.map(item => ({
        orderId,
        productId: item.productId,
        variantId: item.variantId || null,
        size: item.size || null,
        color: item.color || null,
        quantity: item.quantity,
        price: String(item.price),
      }))
    );
  }

  // Bulk update product stock
  const productIdsToUpdate = [...new Set(itemsWithPrice.map(item => item.productId))];
  for (const productId of productIdsToUpdate) {
    const totalQuantity = itemsWithPrice
      .filter(item => item.productId === productId)
      .reduce((sum, item) => sum + item.quantity, 0);
    await db
      .update(products)
      .set({ stock: sql`${products.stock} - ${totalQuantity}` })
      .where(eq(products.id, productId));
  }

  // Bulk update variant stock
  const variantIdsToUpdate = itemsWithPrice
    .filter(item => item.variantId)
    .map(item => item.variantId!);
  for (const variantId of variantIdsToUpdate) {
    const totalQuantity = itemsWithPrice
      .filter(item => item.variantId === variantId)
      .reduce((sum, item) => sum + item.quantity, 0);
    await db
      .update(productVariants)
      .set({ stock: sql`${productVariants.stock} - ${totalQuantity}` })
      .where(eq(productVariants.id, variantId));
  }

  const order = await getById(orderId);
  return { order, auth: checkoutAuth };
};

// ==================== ADMIN LIST ====================

export const getAll = async (page: number = 1, limit: number = 20, status?: string, search?: string) => {
  const offset = (page - 1) * limit;
  const conditions: any[] = [];
  if (status && status !== "all") conditions.push(eq(orders.status, status as any));
  if (search && search.trim()) {
    const term = `%${search.trim()}%`;
    conditions.push(
      or(
        like(orders.orderId, term),
        like(orders.phone, term),
        like(orders.customerName, term),
        like(orders.transactionId, term)
      ) as any
    );
  }

  const where = conditions.length > 0 ? and(...conditions) : undefined;

  const data = await db
    .select()
    .from(orders)
    .where(where)
    .orderBy(desc(orders.createdAt))
    .limit(limit)
    .offset(offset);

  const ordersWithItems = await Promise.all(data.map((order) => attachItemsAndHistory(order)));

  const countResult = await db
    .select({ count: sql<number>`count(*)` })
    .from(orders)
    .where(where);

  return {
    data: ordersWithItems,
    pagination: {
      page,
      limit,
      total: Number(countResult[0].count),
      totalPages: Math.ceil(Number(countResult[0].count) / limit),
    },
  };
};

export const getById = async (id: number) => {
  const rows = await db.select().from(orders).where(eq(orders.id, id)).limit(1);
  if (!rows[0]) return null;
  return attachItemsAndHistory(rows[0]);
};

// ==================== STATUS MANAGEMENT ====================

const syncPaymentStatusOnStatusChange = (order: any, newStatus: OrderStatus): string | undefined => {
  if (newStatus === "delivered" && order.paymentMethod === "cod") return "success";
  if (newStatus === "cancelled" && order.paymentMethod !== "cod") return "failed";
  if (newStatus === "refunded") return "refunded";
  return undefined;
};

export const updateStatus = async (id: number, input: UpdateOrderStatusInput) => {
  const rows = await db.select().from(orders).where(eq(orders.id, id)).limit(1);
  const order = rows[0];
  if (!order) throw new AppError(404, "Order not found");

  if (order.status === input.status && !input.note) {
    return getById(id);
  }

  const allowedNext = STATUS_TRANSITIONS[order.status as OrderStatus] || [order.status as OrderStatus];
  if (!allowedNext.includes(input.status)) {
    throw new AppError(400, `Invalid status transition from ${order.status} to ${input.status}`);
  }

  const updateData: Record<string, unknown> = {
    status: input.status,
    courierTrackingNumber: input.trackingNumber || order.courierTrackingNumber || null,
  };

  const syncedPaymentStatus = syncPaymentStatusOnStatusChange(order, input.status);
  if (syncedPaymentStatus) {
    updateData.paymentStatus = syncedPaymentStatus;
    if (syncedPaymentStatus === "success" && order.paymentMethod === "cod") {
      updateData.paymentDate = new Date();
    }
  }

  await db.update(orders).set(updateData).where(eq(orders.id, id));

  const timelineNote = [
    input.note?.trim(),
    input.trackingNumber?.trim() ? `Courier tracking: ${input.trackingNumber.trim()}` : null,
    syncedPaymentStatus ? `Payment status: ${syncedPaymentStatus}` : null,
  ]
    .filter(Boolean)
    .join(" | ");

  await db.insert(orderStatusHistory).values({
    orderId: id,
    status: input.status,
    note: timelineNote || null,
    createdByUserId: input.userId || null,
  });

  return getById(id);
};

// ==================== PAYMENT VERIFICATION ====================

export const verifyPayment = async (id: number, input: VerifyPaymentInput) => {
  const rows = await db.select().from(orders).where(eq(orders.id, id)).limit(1);
  const order = rows[0];
  if (!order) throw new AppError(404, "Order not found");

  if (order.paymentMethod === "cod") {
    throw new AppError(400, "Payment verification is not applicable for Cash on Delivery orders");
  }

  if (input.action === "verified") {
    await db
      .update(orders)
      .set({ paymentStatus: "verified", paymentDate: new Date(), status: "confirmed" })
      .where(eq(orders.id, id));
    await db.insert(orderStatusHistory).values({
      orderId: id,
      status: "confirmed",
      note: input.note?.trim() || "Payment verified — order confirmed",
      createdByUserId: input.userId || null,
    });
  } else {
    await db
      .update(orders)
      .set({ paymentStatus: "rejected", status: "payment_pending" })
      .where(eq(orders.id, id));
    await db.insert(orderStatusHistory).values({
      orderId: id,
      status: "payment_pending",
      note: input.note?.trim() || "Payment rejected — please submit a valid screenshot",
      createdByUserId: input.userId || null,
    });
  }

  return getById(id);
};

// ==================== ADMIN NOTES ====================

export const updateAdminNotes = async (id: number, note: string, userId?: number) => {
  const rows = await db.select().from(orders).where(eq(orders.id, id)).limit(1);
  const order = rows[0];
  if (!order) throw new AppError(404, "Order not found");

  const existing = order.adminNotes?.trim() || "";
  const stamp = `[${new Date().toLocaleString()}]`;
  const updated = existing ? `${existing}\n${stamp} ${note}` : `${stamp} ${note}`;

  await db.update(orders).set({ adminNotes: updated }).where(eq(orders.id, id));
  return getById(id);
};

// ==================== CUSTOMER ENDPOINTS ====================

export const getMyOrders = async (userId: number) => {
  const data = await db
    .select()
    .from(orders)
    .where(eq(orders.userId, userId))
    .orderBy(desc(orders.createdAt))
    .limit(50);

  return Promise.all(data.map((order) => attachItemsAndHistory(order)));
};

export const trackOrder = async (orderId: string, phone: string) => {
  const rows = await db
    .select()
    .from(orders)
    .where(and(eq(orders.orderId, orderId), eq(orders.phone, phone)))
    .limit(1);
  const order = rows[0];
  if (!order) throw new AppError(404, "Order not found");
  return attachItemsAndHistory(order);
};

// ==================== INVOICE ====================

export const getInvoice = async (id: number) => {
  const order = await getById(id);
  if (!order) throw new AppError(404, "Order not found");
  return order;
};

// ==================== DELETE ====================

export const remove = async (id: number) => {
  await db.delete(orderStatusHistory).where(eq(orderStatusHistory.orderId, id));
  await db.delete(orderItems).where(eq(orderItems.orderId, id));
  await db.delete(orders).where(eq(orders.id, id));
  return { success: true };
};

// ==================== STATS ====================

export const getStats = async () => {
  const totalOrders = await db.select({ count: sql<number>`count(*)` }).from(orders);
  const totalRevenue = await db
    .select({ total: sql<number>`COALESCE(SUM(total_price), 0)` })
    .from(orders)
    .where(eq(orders.status, DELIVERED_STATUS));
  const pendingOrders = await db
    .select({ count: sql<number>`count(*)` })
    .from(orders)
    .where(eq(orders.status, DEFAULT_STATUS));
  const pendingPayments = await db
    .select({ count: sql<number>`count(*)` })
    .from(orders)
    .where(eq(orders.paymentStatus, "payment_verification"));

  return {
    totalOrders: Number(totalOrders[0].count),
    totalRevenue: Number(totalRevenue[0].total),
    pendingOrders: Number(pendingOrders[0].count),
    pendingPayments: Number(pendingPayments[0].count),
  };
};
