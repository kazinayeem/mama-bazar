import { db } from "../../config/db";
import { coupons } from "../../config/schema";
import { eq, and, desc } from "drizzle-orm";
import { CreateCouponInput, UpdateCouponInput } from "./coupon.interface";
import { AppError } from "../../utils/AppError";

const PERCENTAGE = "percentage";
const ACTIVE_STATUS = "active";

export const getAll = async () => {
  return db.select().from(coupons).orderBy(desc(coupons.createdAt));
};

export const getById = async (id: number) => {
  const rows = await db.select().from(coupons).where(eq(coupons.id, id)).limit(1);
  return rows[0] || null;
};

export const validate = async (code: string, subtotal: number) => {
  const rows = await db.select().from(coupons).where(
    and(eq(coupons.code, code), eq(coupons.status, ACTIVE_STATUS))
  ).limit(1);
  const coupon = rows[0];

  if (!coupon) throw new AppError(400, "Invalid coupon code");
  if (coupon.expiryDate && new Date(coupon.expiryDate) < new Date()) {
    throw new AppError(400, "Coupon has expired");
  }
  if (coupon.minOrderAmount && subtotal < Number(coupon.minOrderAmount)) {
    throw new AppError(400, `Minimum order amount is ${coupon.minOrderAmount} Tk`);
  }

  let discount = 0;
  if (coupon.discountType === PERCENTAGE) {
    discount = (subtotal * Number(coupon.discountValue)) / 100;
  } else {
    discount = Number(coupon.discountValue);
  }

  return {
    valid: true,
    discount,
    discountType: coupon.discountType,
    discountValue: coupon.discountValue,
  };
};

export const create = async (data: CreateCouponInput) => {
  const result = await db.insert(coupons).values({
    ...data,
    expiryDate: data.expiryDate ? new Date(data.expiryDate) : null,
  });
  return getById(result[0].insertId);
};

export const update = async (id: number, data: UpdateCouponInput) => {
  const updateData: any = { ...data };
  if (data.expiryDate) updateData.expiryDate = new Date(data.expiryDate);
  await db.update(coupons).set(updateData).where(eq(coupons.id, id));
  return getById(id);
};

export const remove = async (id: number) => {
  await db.delete(coupons).where(eq(coupons.id, id));
  return { success: true };
};
