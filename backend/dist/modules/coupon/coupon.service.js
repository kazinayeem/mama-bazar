"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.remove = exports.update = exports.create = exports.validate = exports.getById = exports.getAll = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const AppError_1 = require("../../utils/AppError");
const PERCENTAGE = "percentage";
const ACTIVE_STATUS = "active";
const getAll = async () => {
    return db_1.db.select().from(schema_1.coupons).orderBy((0, drizzle_orm_1.desc)(schema_1.coupons.createdAt));
};
exports.getAll = getAll;
const getById = async (id) => {
    const rows = await db_1.db.select().from(schema_1.coupons).where((0, drizzle_orm_1.eq)(schema_1.coupons.id, id)).limit(1);
    return rows[0] || null;
};
exports.getById = getById;
const validate = async (code, subtotal) => {
    const rows = await db_1.db.select().from(schema_1.coupons).where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.coupons.code, code), (0, drizzle_orm_1.eq)(schema_1.coupons.status, ACTIVE_STATUS))).limit(1);
    const coupon = rows[0];
    if (!coupon)
        throw new AppError_1.AppError(400, "Invalid coupon code");
    if (coupon.expiryDate && new Date(coupon.expiryDate) < new Date()) {
        throw new AppError_1.AppError(400, "Coupon has expired");
    }
    if (coupon.minOrderAmount && subtotal < Number(coupon.minOrderAmount)) {
        throw new AppError_1.AppError(400, `Minimum order amount is ${coupon.minOrderAmount} Tk`);
    }
    let discount = 0;
    if (coupon.discountType === PERCENTAGE) {
        discount = (subtotal * Number(coupon.discountValue)) / 100;
    }
    else {
        discount = Number(coupon.discountValue);
    }
    return {
        valid: true,
        discount,
        discountType: coupon.discountType,
        discountValue: coupon.discountValue,
    };
};
exports.validate = validate;
const create = async (data) => {
    const result = await db_1.db.insert(schema_1.coupons).values({
        ...data,
        expiryDate: data.expiryDate ? new Date(data.expiryDate) : null,
    });
    return (0, exports.getById)(result[0].insertId);
};
exports.create = create;
const update = async (id, data) => {
    const updateData = { ...data };
    if (data.expiryDate)
        updateData.expiryDate = new Date(data.expiryDate);
    await db_1.db.update(schema_1.coupons).set(updateData).where((0, drizzle_orm_1.eq)(schema_1.coupons.id, id));
    return (0, exports.getById)(id);
};
exports.update = update;
const remove = async (id) => {
    await db_1.db.delete(schema_1.coupons).where((0, drizzle_orm_1.eq)(schema_1.coupons.id, id));
    return { success: true };
};
exports.remove = remove;
//# sourceMappingURL=coupon.service.js.map