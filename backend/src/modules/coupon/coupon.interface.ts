export interface ICoupon {
  id: number;
  code: string;
  discountType: "percentage" | "fixed";
  discountValue: string;
  minOrderAmount?: string | null;
  expiryDate?: Date | null;
  status: "active" | "inactive";
  createdAt: Date;
}

export interface CreateCouponInput {
  code: string;
  discountType: "percentage" | "fixed";
  discountValue: string;
  minOrderAmount?: string;
  expiryDate?: string;
  status?: "active" | "inactive";
}

export interface UpdateCouponInput {
  code?: string;
  discountType?: "percentage" | "fixed";
  discountValue?: string;
  minOrderAmount?: string;
  expiryDate?: string;
  status?: "active" | "inactive";
}
