"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.deleteAddress = exports.updateAddress = exports.createAddress = exports.getAddresses = exports.getOrderHistory = exports.getProfile = exports.updateProfile = exports.changePassword = exports.resetPassword = exports.requestPasswordReset = exports.remove = exports.getById = exports.getAll = exports.devLogin = exports.login = exports.createAdmin = exports.register = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const bcryptjs_1 = __importDefault(require("bcryptjs"));
const jsonwebtoken_1 = __importDefault(require("jsonwebtoken"));
const crypto_1 = __importDefault(require("crypto"));
const env_1 = require("../../config/env");
const AppError_1 = require("../../utils/AppError");
const dev_credentials_1 = require("../../config/dev-credentials");
const ORDER_PROGRESS_FLOW = [
    "pending",
    "confirmed",
    "processing",
    "packed",
    "shipped",
    "out_for_delivery",
    "delivered",
];
const getBackfillChainForStatus = (status) => {
    if (status === "cancelled") {
        return ["pending", "confirmed", "processing", "cancelled"];
    }
    const index = ORDER_PROGRESS_FLOW.indexOf(status);
    if (index === -1)
        return [status];
    return ORDER_PROGRESS_FLOW.slice(0, index + 1);
};
const SALT_ROUNDS = 12;
const TOKEN_EXPIRY = "7d";
const RESET_TOKEN_EXPIRY_MINUTES = 30;
const DEFAULT_ROLE = "user";
const MAX_USER_ADDRESSES = 5;
const register = async (data) => {
    const existingRows = await db_1.db.select().from(schema_1.users).where((0, drizzle_orm_1.eq)(schema_1.users.phone, data.phone)).limit(1);
    if (existingRows[0])
        throw new AppError_1.AppError(409, "Phone number already registered");
    const hashedPassword = await bcryptjs_1.default.hash(data.password, SALT_ROUNDS);
    const result = await db_1.db.insert(schema_1.users).values({
        name: data.name,
        phone: data.phone,
        password: hashedPassword,
        role: data.role || DEFAULT_ROLE,
    });
    return { id: result[0].insertId, name: data.name, phone: data.phone };
};
exports.register = register;
const createAdmin = async (data) => {
    const existingByPhone = await db_1.db.select().from(schema_1.users).where((0, drizzle_orm_1.eq)(schema_1.users.phone, data.phone)).limit(1);
    if (existingByPhone[0])
        throw new AppError_1.AppError(409, "An account with this phone number already exists");
    const existingByEmail = await db_1.db
        .select()
        .from(schema_1.users)
        .where((0, drizzle_orm_1.eq)(schema_1.users.email, data.email))
        .limit(1);
    if (existingByEmail[0])
        throw new AppError_1.AppError(409, "An admin with this email already exists");
    const hashedPassword = await bcryptjs_1.default.hash(data.password, SALT_ROUNDS);
    const result = await db_1.db.insert(schema_1.users).values({
        name: data.name,
        email: data.email,
        phone: data.phone,
        password: hashedPassword,
        role: data.role,
    });
    return {
        id: result[0].insertId,
        name: data.name,
        email: data.email,
        phone: data.phone,
        role: data.role,
    };
};
exports.createAdmin = createAdmin;
const login = async (data) => {
    const rows = await db_1.db.select().from(schema_1.users).where((0, drizzle_orm_1.eq)(schema_1.users.phone, data.phone)).limit(1);
    const user = rows[0];
    if (!user)
        throw new AppError_1.AppError(401, "Invalid credentials");
    const isMatch = await bcryptjs_1.default.compare(data.password, user.password);
    if (!isMatch)
        throw new AppError_1.AppError(401, "Invalid credentials");
    if (user.status === "inactive") {
        throw new AppError_1.AppError(403, "Account is inactive");
    }
    const token = jsonwebtoken_1.default.sign({ id: user.id, phone: user.phone, role: user.role }, env_1.env.JWT_SECRET, { expiresIn: TOKEN_EXPIRY });
    return {
        token,
        user: { id: user.id, name: user.name, phone: user.phone, role: user.role },
    };
};
exports.login = login;
const DEV_ROLE_CREDENTIALS = {
    SUPER_ADMIN: { phone: dev_credentials_1.DEV_ADMIN_PHONE, password: dev_credentials_1.DEV_ADMIN_PASSWORD },
    admin: { phone: dev_credentials_1.DEV_ADMIN_PHONE, password: dev_credentials_1.DEV_ADMIN_PASSWORD },
    USER: { phone: dev_credentials_1.DEV_CUSTOMER_PHONE, password: dev_credentials_1.DEV_CUSTOMER_PASSWORD },
    user: { phone: dev_credentials_1.DEV_CUSTOMER_PHONE, password: dev_credentials_1.DEV_CUSTOMER_PASSWORD },
};
/**
 * Development-only quick login. It authenticates the seeded development
 * account through the exact same `login` path (DB lookup -> bcrypt verify ->
 * account status -> real JWT), and is never available in production.
 */
const devLogin = async (role) => {
    if (env_1.env.NODE_ENV === "production") {
        throw new AppError_1.AppError(404, "Development login is unavailable");
    }
    const credentials = DEV_ROLE_CREDENTIALS[role];
    if (!credentials) {
        throw new AppError_1.AppError(400, "Unknown development account role");
    }
    return (0, exports.login)(credentials);
};
exports.devLogin = devLogin;
const getAll = async () => {
    const allUsers = await db_1.db.select().from(schema_1.users).orderBy((0, drizzle_orm_1.desc)(schema_1.users.createdAt));
    return allUsers.map(({ password, ...rest }) => rest);
};
exports.getAll = getAll;
const getById = async (id) => {
    const rows = await db_1.db.select().from(schema_1.users).where((0, drizzle_orm_1.eq)(schema_1.users.id, id)).limit(1);
    if (!rows[0])
        return null;
    const { password, ...rest } = rows[0];
    return rest;
};
exports.getById = getById;
const remove = async (id) => {
    await db_1.db.delete(schema_1.users).where((0, drizzle_orm_1.eq)(schema_1.users.id, id));
    return { success: true };
};
exports.remove = remove;
const requestPasswordReset = async (data) => {
    const rows = await db_1.db.select().from(schema_1.users).where((0, drizzle_orm_1.eq)(schema_1.users.phone, data.phone)).limit(1);
    const user = rows[0];
    // Don't reveal if phone exists or not (security best practice)
    if (!user)
        return { success: true, message: "If phone exists, reset link sent" };
    // Generate reset token
    const resetToken = crypto_1.default.randomBytes(32).toString("hex");
    const resetTokenHash = crypto_1.default.createHash("sha256").update(resetToken).digest("hex");
    const expiresAt = new Date(Date.now() + RESET_TOKEN_EXPIRY_MINUTES * 60 * 1000);
    // Store hashed token and expiry in database
    await db_1.db.update(schema_1.users)
        .set({
        resetTokenHash,
        resetTokenExpiresAt: expiresAt,
    })
        .where((0, drizzle_orm_1.eq)(schema_1.users.id, user.id));
    // TODO: Send SMS with reset link
    // const resetLink = `${env.FRONTEND_URL}/reset-password/${resetToken}`;
    // await sendResetSMS(user.phone, resetLink);
    // For now, return token in response (remove in production)
    if (env_1.env.NODE_ENV === "development") {
        return { resetToken, expiresAt };
    }
    return { success: true, message: "Password reset link sent to phone" };
};
exports.requestPasswordReset = requestPasswordReset;
const resetPassword = async (data) => {
    // Hash the token to compare with stored hash
    const tokenHash = crypto_1.default.createHash("sha256").update(data.token).digest("hex");
    const rows = await db_1.db.select().from(schema_1.users)
        .where((0, drizzle_orm_1.eq)(schema_1.users.resetTokenHash, tokenHash))
        .limit(1);
    const user = rows[0];
    if (!user)
        throw new AppError_1.AppError(400, "Invalid or expired reset token");
    // Check if token has expired
    if (!user.resetTokenExpiresAt || user.resetTokenExpiresAt < new Date()) {
        throw new AppError_1.AppError(400, "Reset token has expired");
    }
    // Hash new password
    const hashedPassword = await bcryptjs_1.default.hash(data.newPassword, SALT_ROUNDS);
    // Update password and clear reset fields
    await db_1.db.update(schema_1.users)
        .set({
        password: hashedPassword,
        resetTokenHash: null,
        resetTokenExpiresAt: null,
    })
        .where((0, drizzle_orm_1.eq)(schema_1.users.id, user.id));
    return { success: true, message: "Password reset successfully" };
};
exports.resetPassword = resetPassword;
const changePassword = async (userId, data) => {
    const rows = await db_1.db.select().from(schema_1.users).where((0, drizzle_orm_1.eq)(schema_1.users.id, userId)).limit(1);
    const user = rows[0];
    if (!user)
        throw new AppError_1.AppError(404, "User not found");
    // Verify old password
    const isMatch = await bcryptjs_1.default.compare(data.oldPassword, user.password);
    if (!isMatch)
        throw new AppError_1.AppError(401, "Old password is incorrect");
    // Hash new password
    const hashedPassword = await bcryptjs_1.default.hash(data.newPassword, SALT_ROUNDS);
    // Update password
    await db_1.db.update(schema_1.users)
        .set({ password: hashedPassword })
        .where((0, drizzle_orm_1.eq)(schema_1.users.id, userId));
    return { success: true, message: "Password changed successfully" };
};
exports.changePassword = changePassword;
const updateProfile = async (userId, data) => {
    const rows = await db_1.db.select().from(schema_1.users).where((0, drizzle_orm_1.eq)(schema_1.users.id, userId)).limit(1);
    if (!rows[0])
        throw new AppError_1.AppError(404, "User not found");
    const updateData = {};
    if (data.name)
        updateData.name = data.name;
    if (data.phone)
        updateData.phone = data.phone;
    if (data.shippingArea)
        updateData.shippingArea = data.shippingArea;
    if (data.shippingAddress)
        updateData.shippingAddress = data.shippingAddress;
    await db_1.db.update(schema_1.users).set(updateData).where((0, drizzle_orm_1.eq)(schema_1.users.id, userId));
    const updatedRows = await db_1.db.select().from(schema_1.users).where((0, drizzle_orm_1.eq)(schema_1.users.id, userId)).limit(1);
    const { password, resetTokenHash, resetTokenExpiresAt, ...userProfile } = updatedRows[0];
    return userProfile;
};
exports.updateProfile = updateProfile;
const getProfile = async (userId) => {
    const rows = await db_1.db.select().from(schema_1.users).where((0, drizzle_orm_1.eq)(schema_1.users.id, userId)).limit(1);
    if (!rows[0])
        throw new AppError_1.AppError(404, "User not found");
    const { password, resetTokenHash, resetTokenExpiresAt, ...userProfile } = rows[0];
    return userProfile;
};
exports.getProfile = getProfile;
const getOrderHistory = async (userId) => {
    const userRows = await db_1.db.select().from(schema_1.users).where((0, drizzle_orm_1.eq)(schema_1.users.id, userId)).limit(1);
    const user = userRows[0];
    if (!user)
        throw new AppError_1.AppError(404, "User not found");
    // Include legacy guest orders that were saved with phone but without user_id.
    const userOrders = await db_1.db
        .select()
        .from(schema_1.orders)
        .where((0, drizzle_orm_1.or)((0, drizzle_orm_1.eq)(schema_1.orders.userId, userId), (0, drizzle_orm_1.and)((0, drizzle_orm_1.isNull)(schema_1.orders.userId), (0, drizzle_orm_1.eq)(schema_1.orders.phone, user.phone))))
        .orderBy((0, drizzle_orm_1.desc)(schema_1.orders.createdAt));
    // Get order items for each order
    const ordersWithItems = await Promise.all(userOrders.map(async (order) => {
        const items = await db_1.db.select().from(schema_1.orderItems)
            .where((0, drizzle_orm_1.eq)(schema_1.orderItems.orderId, order.id));
        // Get product details for each item
        const itemsWithProducts = await Promise.all(items.map(async (item) => {
            const productRows = await db_1.db.select().from(schema_1.products)
                .where((0, drizzle_orm_1.eq)(schema_1.products.id, item.productId))
                .limit(1);
            return {
                id: item.id,
                productId: item.productId,
                product: productRows[0] || null,
                quantity: item.quantity,
                price: item.price,
            };
        }));
        const statusHistoryRows = await db_1.db
            .select()
            .from(schema_1.orderStatusHistory)
            .where((0, drizzle_orm_1.eq)(schema_1.orderStatusHistory.orderId, order.id))
            .orderBy((0, drizzle_orm_1.asc)(schema_1.orderStatusHistory.createdAt));
        const statusHistory = statusHistoryRows.length === 1 && order.status !== "pending"
            ? (() => {
                const chain = getBackfillChainForStatus(order.status);
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
                    const lastStatus = lastHistory.status;
                    const orderStatus = order.status;
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
    }));
    return ordersWithItems;
};
exports.getOrderHistory = getOrderHistory;
const getAddresses = async (userId) => {
    return db_1.db
        .select()
        .from(schema_1.userAddresses)
        .where((0, drizzle_orm_1.eq)(schema_1.userAddresses.userId, userId))
        .orderBy((0, drizzle_orm_1.desc)(schema_1.userAddresses.isDefault), (0, drizzle_orm_1.desc)(schema_1.userAddresses.createdAt));
};
exports.getAddresses = getAddresses;
const createAddress = async (userId, data) => {
    const existing = await db_1.db.select().from(schema_1.userAddresses).where((0, drizzle_orm_1.eq)(schema_1.userAddresses.userId, userId));
    if (existing.length >= MAX_USER_ADDRESSES) {
        throw new AppError_1.AppError(400, `Maximum ${MAX_USER_ADDRESSES} addresses allowed`);
    }
    const shouldBeDefault = data.isDefault || existing.length === 0;
    if (shouldBeDefault) {
        await db_1.db.update(schema_1.userAddresses).set({ isDefault: false }).where((0, drizzle_orm_1.eq)(schema_1.userAddresses.userId, userId));
    }
    await db_1.db.insert(schema_1.userAddresses).values({
        userId,
        recipientName: data.recipientName,
        phone: data.phone,
        shippingArea: data.shippingArea,
        address: data.address,
        isDefault: shouldBeDefault,
    });
    return (0, exports.getAddresses)(userId);
};
exports.createAddress = createAddress;
const updateAddress = async (userId, addressId, data) => {
    const rows = await db_1.db
        .select()
        .from(schema_1.userAddresses)
        .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.userAddresses.id, addressId), (0, drizzle_orm_1.eq)(schema_1.userAddresses.userId, userId)))
        .limit(1);
    if (!rows[0])
        throw new AppError_1.AppError(404, "Address not found");
    if (data.isDefault) {
        await db_1.db.update(schema_1.userAddresses).set({ isDefault: false }).where((0, drizzle_orm_1.eq)(schema_1.userAddresses.userId, userId));
    }
    await db_1.db
        .update(schema_1.userAddresses)
        .set({
        recipientName: data.recipientName,
        phone: data.phone,
        shippingArea: data.shippingArea,
        address: data.address,
        isDefault: data.isDefault,
    })
        .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.userAddresses.id, addressId), (0, drizzle_orm_1.eq)(schema_1.userAddresses.userId, userId)));
    return (0, exports.getAddresses)(userId);
};
exports.updateAddress = updateAddress;
const deleteAddress = async (userId, addressId) => {
    const rows = await db_1.db
        .select()
        .from(schema_1.userAddresses)
        .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.userAddresses.id, addressId), (0, drizzle_orm_1.eq)(schema_1.userAddresses.userId, userId)))
        .limit(1);
    const target = rows[0];
    if (!target)
        throw new AppError_1.AppError(404, "Address not found");
    await db_1.db.delete(schema_1.userAddresses).where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.userAddresses.id, addressId), (0, drizzle_orm_1.eq)(schema_1.userAddresses.userId, userId)));
    if (target.isDefault) {
        const remaining = await db_1.db
            .select()
            .from(schema_1.userAddresses)
            .where((0, drizzle_orm_1.eq)(schema_1.userAddresses.userId, userId))
            .orderBy((0, drizzle_orm_1.desc)(schema_1.userAddresses.createdAt))
            .limit(1);
        if (remaining[0]) {
            await db_1.db.update(schema_1.userAddresses).set({ isDefault: true }).where((0, drizzle_orm_1.eq)(schema_1.userAddresses.id, remaining[0].id));
        }
    }
    return (0, exports.getAddresses)(userId);
};
exports.deleteAddress = deleteAddress;
//# sourceMappingURL=user.service.js.map