import { db } from "../../config/db";
import { users, userAddresses, orders, orderItems, products, orderStatusHistory } from "../../config/schema";
import { eq, desc, asc, and, or, isNull } from "drizzle-orm";
import bcrypt from "bcryptjs";
import jwt from "jsonwebtoken";
import crypto from "crypto";
import { CreateUserInput, LoginInput, PasswordResetRequestInput, PasswordResetInput, ChangePasswordInput, UpdateProfileInput } from "./user.interface";
import { env } from "../../config/env";
import { AppError } from "../../utils/AppError";
import {
  DEV_ADMIN_PHONE,
  DEV_ADMIN_PASSWORD,
  DEV_CUSTOMER_PHONE,
  DEV_CUSTOMER_PASSWORD,
} from "../../config/dev-credentials";

type OrderStatus =
  | "pending"
  | "confirmed"
  | "processing"
  | "packed"
  | "shipped"
  | "out_for_delivery"
  | "delivered"
  | "cancelled";

const ORDER_PROGRESS_FLOW: OrderStatus[] = [
  "pending",
  "confirmed",
  "processing",
  "packed",
  "shipped",
  "out_for_delivery",
  "delivered",
];

const getBackfillChainForStatus = (status: OrderStatus): OrderStatus[] => {
  if (status === "cancelled") {
    return ["pending", "confirmed", "processing", "cancelled"];
  }

  const index = ORDER_PROGRESS_FLOW.indexOf(status);
  if (index === -1) return [status];
  return ORDER_PROGRESS_FLOW.slice(0, index + 1);
};
const SALT_ROUNDS = 12;
const TOKEN_EXPIRY = "7d";
const RESET_TOKEN_EXPIRY_MINUTES = 30;
const DEFAULT_ROLE = "user";
const MAX_USER_ADDRESSES = 5;

export const register = async (data: CreateUserInput) => {
  const existingRows = await db.select().from(users).where(eq(users.phone, data.phone)).limit(1);
  if (existingRows[0]) throw new AppError(409, "Phone number already registered");

  const hashedPassword = await bcrypt.hash(data.password, SALT_ROUNDS);
  const result = await db.insert(users).values({
    name: data.name,
    phone: data.phone,
    password: hashedPassword,
    role: data.role || DEFAULT_ROLE,
  });

  return { id: result[0].insertId, name: data.name, phone: data.phone };
};

export const login = async (data: LoginInput) => {
  const rows = await db.select().from(users).where(eq(users.phone, data.phone)).limit(1);
  const user = rows[0];
  if (!user) throw new AppError(401, "Invalid credentials");

  const isMatch = await bcrypt.compare(data.password, user.password);
  if (!isMatch) throw new AppError(401, "Invalid credentials");

  if (user.status === "inactive") {
    throw new AppError(403, "Account is inactive");
  }

  const token = jwt.sign(
    { id: user.id, phone: user.phone, role: user.role },
    env.JWT_SECRET,
    { expiresIn: TOKEN_EXPIRY }
  );

  return {
    token,
    user: { id: user.id, name: user.name, phone: user.phone, role: user.role },
  };
};

const DEV_ROLE_CREDENTIALS: Record<string, { phone: string; password: string }> = {
  SUPER_ADMIN: { phone: DEV_ADMIN_PHONE, password: DEV_ADMIN_PASSWORD },
  admin: { phone: DEV_ADMIN_PHONE, password: DEV_ADMIN_PASSWORD },
  USER: { phone: DEV_CUSTOMER_PHONE, password: DEV_CUSTOMER_PASSWORD },
  user: { phone: DEV_CUSTOMER_PHONE, password: DEV_CUSTOMER_PASSWORD },
};

/**
 * Development-only quick login. It authenticates the seeded development
 * account through the exact same `login` path (DB lookup -> bcrypt verify ->
 * account status -> real JWT), and is never available in production.
 */
export const devLogin = async (role: string) => {
  if (env.NODE_ENV === "production") {
    throw new AppError(404, "Development login is unavailable");
  }
  const credentials = DEV_ROLE_CREDENTIALS[role];
  if (!credentials) {
    throw new AppError(400, "Unknown development account role");
  }
  return login(credentials);
};

export const getAll = async () => {
  const allUsers = await db.select().from(users).orderBy(desc(users.createdAt));
  return allUsers.map(({ password, ...rest }) => rest);
};

export const getById = async (id: number) => {
  const rows = await db.select().from(users).where(eq(users.id, id)).limit(1);
  if (!rows[0]) return null;
  const { password, ...rest } = rows[0];
  return rest;
};

export const remove = async (id: number) => {
  await db.delete(users).where(eq(users.id, id));
  return { success: true };
};

export const requestPasswordReset = async (data: PasswordResetRequestInput) => {
  const rows = await db.select().from(users).where(eq(users.phone, data.phone)).limit(1);
  const user = rows[0];
  
  // Don't reveal if phone exists or not (security best practice)
  if (!user) return { success: true, message: "If phone exists, reset link sent" };

  // Generate reset token
  const resetToken = crypto.randomBytes(32).toString("hex");
  const resetTokenHash = crypto.createHash("sha256").update(resetToken).digest("hex");
  const expiresAt = new Date(Date.now() + RESET_TOKEN_EXPIRY_MINUTES * 60 * 1000);

  // Store hashed token and expiry in database
  await db.update(users)
    .set({
      resetTokenHash,
      resetTokenExpiresAt: expiresAt,
    })
    .where(eq(users.id, user.id));

  // TODO: Send SMS with reset link
  // const resetLink = `${env.FRONTEND_URL}/reset-password/${resetToken}`;
  // await sendResetSMS(user.phone, resetLink);

  // For now, return token in response (remove in production)
  if (env.NODE_ENV === "development") {
    return { resetToken, expiresAt };
  }

  return { success: true, message: "Password reset link sent to phone" };
};

export const resetPassword = async (data: PasswordResetInput) => {
  // Hash the token to compare with stored hash
  const tokenHash = crypto.createHash("sha256").update(data.token).digest("hex");

  const rows = await db.select().from(users)
    .where(eq(users.resetTokenHash, tokenHash))
    .limit(1);
  const user = rows[0];

  if (!user) throw new AppError(400, "Invalid or expired reset token");

  // Check if token has expired
  if (!user.resetTokenExpiresAt || user.resetTokenExpiresAt < new Date()) {
    throw new AppError(400, "Reset token has expired");
  }

  // Hash new password
  const hashedPassword = await bcrypt.hash(data.newPassword, SALT_ROUNDS);

  // Update password and clear reset fields
  await db.update(users)
    .set({
      password: hashedPassword,
      resetTokenHash: null,
      resetTokenExpiresAt: null,
    })
    .where(eq(users.id, user.id));

  return { success: true, message: "Password reset successfully" };
};

export const changePassword = async (userId: number, data: ChangePasswordInput) => {
  const rows = await db.select().from(users).where(eq(users.id, userId)).limit(1);
  const user = rows[0];

  if (!user) throw new AppError(404, "User not found");

  // Verify old password
  const isMatch = await bcrypt.compare(data.oldPassword, user.password);
  if (!isMatch) throw new AppError(401, "Old password is incorrect");

  // Hash new password
  const hashedPassword = await bcrypt.hash(data.newPassword, SALT_ROUNDS);

  // Update password
  await db.update(users)
    .set({ password: hashedPassword })
    .where(eq(users.id, userId));

  return { success: true, message: "Password changed successfully" };
};

export const updateProfile = async (userId: number, data: UpdateProfileInput) => {
  const rows = await db.select().from(users).where(eq(users.id, userId)).limit(1);
  if (!rows[0]) throw new AppError(404, "User not found");

  const updateData: any = {};
  if (data.name) updateData.name = data.name;
  if (data.phone) updateData.phone = data.phone;
  if (data.shippingArea) updateData.shippingArea = data.shippingArea;
  if (data.shippingAddress) updateData.shippingAddress = data.shippingAddress;

  await db.update(users).set(updateData).where(eq(users.id, userId));

  const updatedRows = await db.select().from(users).where(eq(users.id, userId)).limit(1);
  const { password, resetTokenHash, resetTokenExpiresAt, ...userProfile } = updatedRows[0];
  
  return userProfile;
};

export const getProfile = async (userId: number) => {
  const rows = await db.select().from(users).where(eq(users.id, userId)).limit(1);
  if (!rows[0]) throw new AppError(404, "User not found");

  const { password, resetTokenHash, resetTokenExpiresAt, ...userProfile } = rows[0];
  return userProfile;
};

export const getOrderHistory = async (userId: number) => {
  const userRows = await db.select().from(users).where(eq(users.id, userId)).limit(1);
  const user = userRows[0];
  if (!user) throw new AppError(404, "User not found");

  // Include legacy guest orders that were saved with phone but without user_id.
  const userOrders = await db
    .select()
    .from(orders)
    .where(
      or(
        eq(orders.userId, userId),
        and(isNull(orders.userId), eq(orders.phone, user.phone))
      )
    )
    .orderBy(desc(orders.createdAt));

  // Get order items for each order
  const ordersWithItems = await Promise.all(
    userOrders.map(async (order) => {
      const items = await db.select().from(orderItems)
        .where(eq(orderItems.orderId, order.id));
      
      // Get product details for each item
      const itemsWithProducts = await Promise.all(
        items.map(async (item) => {
          const productRows = await db.select().from(products)
            .where(eq(products.id, item.productId))
            .limit(1);
          return {
            id: item.id,
            productId: item.productId,
            product: productRows[0] || null,
            quantity: item.quantity,
            price: item.price,
          };
        })
      );

      const statusHistoryRows = await db
        .select()
        .from(orderStatusHistory)
        .where(eq(orderStatusHistory.orderId, order.id))
        .orderBy(asc(orderStatusHistory.createdAt));

      const statusHistory =
        statusHistoryRows.length === 1 && order.status !== "pending"
          ? (() => {
              const chain = getBackfillChainForStatus(order.status as OrderStatus);

              return chain.map((status, index) => ({
                id: index + 1,
                orderId: order.id,
                status,
                note: index === 0 ? "Order created" : `Backfilled ${status} step`,
                createdByUserId: null,
                createdAt: new Date(order.createdAt.getTime() + index * 20 * 60 * 1000),
              }));
            })()
          : statusHistoryRows.length > 0
            ? (() => {
                const lastHistory = statusHistoryRows[statusHistoryRows.length - 1];
                const lastStatus = lastHistory.status as OrderStatus;
                const orderStatus = order.status as OrderStatus;

                if (orderStatus === "cancelled") {
                  if (lastStatus !== "cancelled") {
                    return [
                      ...statusHistoryRows,
                      {
                        id: Number(lastHistory.id) + 1,
                        orderId: order.id,
                        status: "cancelled",
                        note: "Backfilled cancelled step",
                        createdByUserId: null,
                        createdAt: new Date(new Date(lastHistory.createdAt).getTime() + 20 * 60 * 1000),
                      },
                    ];
                  }

                  return statusHistoryRows;
                }

                const lastIndex = ORDER_PROGRESS_FLOW.indexOf(lastStatus);
                const targetIndex = ORDER_PROGRESS_FLOW.indexOf(orderStatus);

                if (lastIndex !== -1 && targetIndex > lastIndex) {
                  const padded = [...statusHistoryRows];
                  let currentTime = new Date(new Date(lastHistory.createdAt).getTime());
                  let nextId = Number(lastHistory.id) + 1;

                  for (let index = lastIndex + 1; index <= targetIndex; index++) {
                    currentTime = new Date(currentTime.getTime() + 20 * 60 * 1000);
                    padded.push({
                      id: nextId,
                      orderId: order.id,
                      status: ORDER_PROGRESS_FLOW[index],
                      note: `Backfilled ${ORDER_PROGRESS_FLOW[index]} step`,
                      createdByUserId: null,
                      createdAt: currentTime,
                    });
                    nextId += 1;
                  }

                  return padded;
                }

                return statusHistoryRows;
              })()
            : [
                {
                  id: 0,
                  orderId: order.id,
                  status: order.status,
                  note: "Legacy order (timeline started later)",
                  createdByUserId: null,
                  createdAt: order.createdAt,
                },
              ];

      return {
        ...order,
        statusHistory,
        items: itemsWithProducts,
      };
    })
  );

  return ordersWithItems;
};

export const getAddresses = async (userId: number) => {
  return db
    .select()
    .from(userAddresses)
    .where(eq(userAddresses.userId, userId))
    .orderBy(desc(userAddresses.isDefault), desc(userAddresses.createdAt));
};

export const createAddress = async (
  userId: number,
  data: {
    recipientName: string;
    phone: string;
    shippingArea: string;
    address: string;
    isDefault?: boolean;
  }
) => {
  const existing = await db.select().from(userAddresses).where(eq(userAddresses.userId, userId));
  if (existing.length >= MAX_USER_ADDRESSES) {
    throw new AppError(400, `Maximum ${MAX_USER_ADDRESSES} addresses allowed`);
  }

  const shouldBeDefault = data.isDefault || existing.length === 0;
  if (shouldBeDefault) {
    await db.update(userAddresses).set({ isDefault: false }).where(eq(userAddresses.userId, userId));
  }

  await db.insert(userAddresses).values({
    userId,
    recipientName: data.recipientName,
    phone: data.phone,
    shippingArea: data.shippingArea,
    address: data.address,
    isDefault: shouldBeDefault,
  });

  return getAddresses(userId);
};

export const updateAddress = async (
  userId: number,
  addressId: number,
  data: {
    recipientName?: string;
    phone?: string;
    shippingArea?: string;
    address?: string;
    isDefault?: boolean;
  }
) => {
  const rows = await db
    .select()
    .from(userAddresses)
    .where(and(eq(userAddresses.id, addressId), eq(userAddresses.userId, userId)))
    .limit(1);
  if (!rows[0]) throw new AppError(404, "Address not found");

  if (data.isDefault) {
    await db.update(userAddresses).set({ isDefault: false }).where(eq(userAddresses.userId, userId));
  }

  await db
    .update(userAddresses)
    .set({
      recipientName: data.recipientName,
      phone: data.phone,
      shippingArea: data.shippingArea,
      address: data.address,
      isDefault: data.isDefault,
    })
    .where(and(eq(userAddresses.id, addressId), eq(userAddresses.userId, userId)));

  return getAddresses(userId);
};

export const deleteAddress = async (userId: number, addressId: number) => {
  const rows = await db
    .select()
    .from(userAddresses)
    .where(and(eq(userAddresses.id, addressId), eq(userAddresses.userId, userId)))
    .limit(1);
  const target = rows[0];
  if (!target) throw new AppError(404, "Address not found");

  await db.delete(userAddresses).where(and(eq(userAddresses.id, addressId), eq(userAddresses.userId, userId)));

  if (target.isDefault) {
    const remaining = await db
      .select()
      .from(userAddresses)
      .where(eq(userAddresses.userId, userId))
      .orderBy(desc(userAddresses.createdAt))
      .limit(1);

    if (remaining[0]) {
      await db.update(userAddresses).set({ isDefault: true }).where(eq(userAddresses.id, remaining[0].id));
    }
  }

  return getAddresses(userId);
};
