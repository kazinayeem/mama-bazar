"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.getStats = exports.remove = exports.getInvoice = exports.trackOrder = exports.getMyOrders = exports.updateAdminNotes = exports.verifyPayment = exports.updateStatus = exports.getById = exports.getAll = exports.create = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const AppError_1 = require("../../utils/AppError");
const bcryptjs_1 = __importDefault(require("bcryptjs"));
const jsonwebtoken_1 = __importDefault(require("jsonwebtoken"));
const env_1 = require("../../config/env");
const DEFAULT_STATUS = "pending";
const PERCENTAGE = "percentage";
const DELIVERED_STATUS = "delivered";
const DEFAULT_ROLE = "user";
const SALT_ROUNDS = 12;
const TOKEN_EXPIRY = "7d";
const MAX_TAX_RATE = 25;
const ORDER_PROGRESS_FLOW = [
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
const getBackfillChainForStatus = (status) => {
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
    if (index === -1)
        return [status];
    return ORDER_PROGRESS_FLOW.slice(0, index + 1);
};
const STATUS_TRANSITIONS = {
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
const ONLINE_PAYMENT_METHODS = ["bkash", "nagad", "rocket", "bank", "stripe", "sslcommerz", "paypal"];
function generateOrderId() {
    const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    let result = "GHB-";
    for (let i = 0; i < 6; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}
const toNum = (v, fallback = 0) => {
    const n = Number(v);
    return Number.isFinite(n) ? n : fallback;
};
const getOrderHistory = async (orderId) => {
    const logs = await db_1.db
        .select()
        .from(schema_1.orderStatusHistory)
        .where((0, drizzle_orm_1.eq)(schema_1.orderStatusHistory.orderId, orderId))
        .orderBy((0, drizzle_orm_1.asc)(schema_1.orderStatusHistory.createdAt));
    return logs.map((log) => ({
        id: log.id,
        status: log.status,
        note: log.note,
        createdAt: log.createdAt,
        createdByUserId: log.createdByUserId,
    }));
};
const getTimelineWithFallback = async (orderId, fallbackStatus, fallbackCreatedAt) => {
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
        const lastStatus = lastLog.status;
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
    if (logs.length > 0)
        return logs;
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
const attachItemsAndHistory = async (order) => {
    const items = await db_1.db
        .select({
        id: schema_1.orderItems.id,
        orderId: schema_1.orderItems.orderId,
        productId: schema_1.orderItems.productId,
        size: schema_1.orderItems.size,
        color: schema_1.orderItems.color,
        quantity: schema_1.orderItems.quantity,
        price: schema_1.orderItems.price,
        productTitle: schema_1.products.title,
        productImages: schema_1.products.images,
    })
        .from(schema_1.orderItems)
        .leftJoin(schema_1.products, (0, drizzle_orm_1.eq)(schema_1.orderItems.productId, schema_1.products.id))
        .where((0, drizzle_orm_1.eq)(schema_1.orderItems.orderId, order.id));
    const statusHistory = await getTimelineWithFallback(order.id, order.status, order.createdAt);
    return {
        ...order,
        statusHistory,
        historyCount: statusHistory.length,
        items: items.map((i) => {
            let image = null;
            if (Array.isArray(i.productImages))
                image = i.productImages[0] || null;
            else if (typeof i.productImages === "string" && i.productImages) {
                try {
                    const parsed = JSON.parse(i.productImages);
                    image = Array.isArray(parsed) ? parsed[0] || null : null;
                }
                catch {
                    image = null;
                }
            }
            return {
                id: i.id,
                orderId: i.orderId,
                productId: i.productId,
                size: i.size,
                color: i.color,
                quantity: i.quantity,
                price: i.price,
                product: i.productTitle ? { title: i.productTitle, image } : null,
            };
        }),
    };
};
// ==================== CREATE (FULL CHECKOUT) ====================
const create = async (input) => {
    // ---------- 1. Validate items & compute subtotal ----------
    let subtotal = 0;
    const itemsWithPrice = [];
    for (const item of input.items) {
        const productRows = await db_1.db.select().from(schema_1.products).where((0, drizzle_orm_1.eq)(schema_1.products.id, item.productId)).limit(1);
        const product = productRows[0];
        if (!product)
            throw new AppError_1.AppError(400, `Product ${item.productId} not found`);
        if (product.stock < item.quantity) {
            throw new AppError_1.AppError(400, `Insufficient stock for ${product.title}`);
        }
        const discountRate = Math.min(Number(product.discount || 0), 100);
        const actualPrice = Math.round(Number(product.price) * (1 - discountRate / 100));
        const itemTotal = actualPrice * item.quantity;
        subtotal += itemTotal;
        itemsWithPrice.push({
            productId: item.productId,
            quantity: item.quantity,
            size: item.size,
            color: item.color,
            price: actualPrice,
        });
    }
    // ---------- 2. Shipping method ----------
    let shippingCost = Number(input.shippingCost) || 0;
    let shippingMethodName = null;
    let shippingMethodId = null;
    if (input.shippingMethodId) {
        const methodRows = await db_1.db
            .select()
            .from(schema_1.shippingMethods)
            .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.shippingMethods.id, Number(input.shippingMethodId)), (0, drizzle_orm_1.eq)(schema_1.shippingMethods.status, "active")))
            .limit(1);
        const method = methodRows[0];
        if (!method)
            throw new AppError_1.AppError(400, "Shipping method is not available");
        if (input.paymentMethod === "cod" && !method.codAvailable) {
            throw new AppError_1.AppError(400, `${method.name} does not support Cash on Delivery`);
        }
        shippingMethodId = method.id;
        shippingMethodName = method.name;
        const freeMin = method.freeShippingMinAmount === null ? null : toNum(method.freeShippingMinAmount);
        shippingCost = freeMin !== null && subtotal >= freeMin ? 0 : toNum(method.charge);
    }
    // ---------- 3. Payment method ----------
    const paymentMethodCode = input.paymentMethod || "cod";
    const paymentMethodRows = await db_1.db
        .select()
        .from(schema_1.paymentMethods)
        .where((0, drizzle_orm_1.eq)(schema_1.paymentMethods.code, paymentMethodCode))
        .limit(1);
    const paymentMethod = paymentMethodRows[0];
    if (!paymentMethod)
        throw new AppError_1.AppError(400, "Payment method is not available");
    if (!paymentMethod.enabled || paymentMethod.maintenanceMode) {
        throw new AppError_1.AppError(400, `${paymentMethod.name} is currently unavailable`);
    }
    // ---------- 4. Coupon ----------
    let discount = 0;
    if (input.couponCode) {
        const couponRows = await db_1.db.select().from(schema_1.coupons).where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.coupons.code, input.couponCode), (0, drizzle_orm_1.eq)(schema_1.coupons.status, "active"))).limit(1);
        const coupon = couponRows[0];
        if (coupon) {
            if (coupon.expiryDate && new Date(coupon.expiryDate) < new Date()) {
                throw new AppError_1.AppError(400, "Coupon has expired");
            }
            if (coupon.minOrderAmount && subtotal < Number(coupon.minOrderAmount)) {
                throw new AppError_1.AppError(400, `Minimum order amount is ${coupon.minOrderAmount} Tk`);
            }
            if (coupon.discountType === PERCENTAGE) {
                discount = (subtotal * Number(coupon.discountValue)) / 100;
            }
            else {
                discount = Number(coupon.discountValue);
            }
            discount = Math.min(discount, subtotal);
        }
    }
    // ---------- 5. Tax ----------
    let tax = Number(input.taxAmount) || 0;
    if (tax < 0)
        tax = 0;
    if (tax > (subtotal - discount) * (MAX_TAX_RATE / 100)) {
        tax = (subtotal - discount) * (MAX_TAX_RATE / 100);
    }
    const totalPrice = subtotal - discount + tax + shippingCost;
    // ---------- 6. Payment state ----------
    const isOnline = ONLINE_PAYMENT_METHODS.includes(paymentMethodCode);
    let paymentStatus;
    let orderStatus;
    if (paymentMethodCode === "cod") {
        paymentStatus = "success";
        orderStatus = "pending";
    }
    else if (input.paymentScreenshot) {
        paymentStatus = "payment_verification";
        orderStatus = "payment_verification";
    }
    else if (input.transactionId) {
        paymentStatus = "verified";
        orderStatus = "pending";
    }
    else {
        paymentStatus = "payment_pending";
        orderStatus = "payment_pending";
    }
    // ---------- 7. Resolve user (guest auto-create) ----------
    let resolvedUserId = input.userId;
    let checkoutAuth = null;
    if (!resolvedUserId) {
        const existingRows = await db_1.db.select().from(schema_1.users).where((0, drizzle_orm_1.eq)(schema_1.users.phone, input.phone)).limit(1);
        const existingUser = existingRows[0];
        if (existingUser) {
            resolvedUserId = existingUser.id;
            const canAutoLogin = await bcryptjs_1.default.compare(input.phone, existingUser.password);
            if (canAutoLogin) {
                const token = jsonwebtoken_1.default.sign({ id: existingUser.id, phone: existingUser.phone, role: existingUser.role }, env_1.env.JWT_SECRET, { expiresIn: TOKEN_EXPIRY });
                checkoutAuth = {
                    token,
                    user: {
                        id: existingUser.id,
                        name: existingUser.name,
                        phone: existingUser.phone,
                        role: existingUser.role,
                    },
                };
            }
        }
        else {
            const hashedPassword = await bcryptjs_1.default.hash(input.phone, SALT_ROUNDS);
            const userInsert = await db_1.db.insert(schema_1.users).values({
                name: input.name,
                phone: input.phone,
                password: hashedPassword,
                role: DEFAULT_ROLE,
                shippingArea: input.shippingArea,
                shippingAddress: input.address,
            });
            resolvedUserId = userInsert[0].insertId;
            const token = jsonwebtoken_1.default.sign({ id: resolvedUserId, phone: input.phone, role: DEFAULT_ROLE }, env_1.env.JWT_SECRET, { expiresIn: TOKEN_EXPIRY });
            checkoutAuth = {
                token,
                user: {
                    id: resolvedUserId,
                    name: input.name,
                    phone: input.phone,
                    role: DEFAULT_ROLE,
                },
            };
        }
    }
    // ---------- 8. Update user + save address ----------
    if (resolvedUserId) {
        await db_1.db
            .update(schema_1.users)
            .set({
            shippingArea: input.shippingArea,
            shippingAddress: input.address,
        })
            .where((0, drizzle_orm_1.eq)(schema_1.users.id, resolvedUserId));
        const existingAddresses = await db_1.db
            .select()
            .from(schema_1.userAddresses)
            .where((0, drizzle_orm_1.eq)(schema_1.userAddresses.userId, resolvedUserId));
        const alreadyExists = existingAddresses.some((a) => a.address === input.address && a.shippingArea === input.shippingArea && a.phone === input.phone);
        if (!alreadyExists && existingAddresses.length < 5) {
            await db_1.db.insert(schema_1.userAddresses).values({
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
    const orderResult = await db_1.db.insert(schema_1.orders).values({
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
        paymentStatus: paymentStatus,
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
    await db_1.db.insert(schema_1.orderStatusHistory).values({
        orderId,
        status: orderStatus,
        note: historyNote,
        createdByUserId: resolvedUserId || null,
    });
    // ---------- 10. Items + stock ----------
    for (const item of itemsWithPrice) {
        await db_1.db.insert(schema_1.orderItems).values({
            orderId,
            productId: item.productId,
            size: item.size || null,
            color: item.color || null,
            quantity: item.quantity,
            price: String(item.price),
        });
        await db_1.db
            .update(schema_1.products)
            .set({ stock: (0, drizzle_orm_1.sql) `${schema_1.products.stock} - ${item.quantity}` })
            .where((0, drizzle_orm_1.eq)(schema_1.products.id, item.productId));
    }
    const order = await (0, exports.getById)(orderId);
    return { order, auth: checkoutAuth };
};
exports.create = create;
// ==================== ADMIN LIST ====================
const getAll = async (page = 1, limit = 20, status, search) => {
    const offset = (page - 1) * limit;
    const conditions = [];
    if (status && status !== "all")
        conditions.push((0, drizzle_orm_1.eq)(schema_1.orders.status, status));
    if (search && search.trim()) {
        const term = `%${search.trim()}%`;
        conditions.push((0, drizzle_orm_1.or)((0, drizzle_orm_1.like)(schema_1.orders.orderId, term), (0, drizzle_orm_1.like)(schema_1.orders.phone, term), (0, drizzle_orm_1.like)(schema_1.orders.customerName, term), (0, drizzle_orm_1.like)(schema_1.orders.transactionId, term)));
    }
    const where = conditions.length > 0 ? (0, drizzle_orm_1.and)(...conditions) : undefined;
    const data = await db_1.db
        .select()
        .from(schema_1.orders)
        .where(where)
        .orderBy((0, drizzle_orm_1.desc)(schema_1.orders.createdAt))
        .limit(limit)
        .offset(offset);
    const ordersWithItems = await Promise.all(data.map((order) => attachItemsAndHistory(order)));
    const countResult = await db_1.db
        .select({ count: (0, drizzle_orm_1.sql) `count(*)` })
        .from(schema_1.orders)
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
exports.getAll = getAll;
const getById = async (id) => {
    const rows = await db_1.db.select().from(schema_1.orders).where((0, drizzle_orm_1.eq)(schema_1.orders.id, id)).limit(1);
    if (!rows[0])
        return null;
    return attachItemsAndHistory(rows[0]);
};
exports.getById = getById;
// ==================== STATUS MANAGEMENT ====================
const syncPaymentStatusOnStatusChange = (order, newStatus) => {
    if (newStatus === "delivered" && order.paymentMethod === "cod")
        return "success";
    if (newStatus === "cancelled" && order.paymentMethod !== "cod")
        return "failed";
    if (newStatus === "refunded")
        return "refunded";
    return undefined;
};
const updateStatus = async (id, input) => {
    const rows = await db_1.db.select().from(schema_1.orders).where((0, drizzle_orm_1.eq)(schema_1.orders.id, id)).limit(1);
    const order = rows[0];
    if (!order)
        throw new AppError_1.AppError(404, "Order not found");
    if (order.status === input.status && !input.note) {
        return (0, exports.getById)(id);
    }
    const allowedNext = STATUS_TRANSITIONS[order.status] || [order.status];
    if (!allowedNext.includes(input.status)) {
        throw new AppError_1.AppError(400, `Invalid status transition from ${order.status} to ${input.status}`);
    }
    const updateData = {
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
    await db_1.db.update(schema_1.orders).set(updateData).where((0, drizzle_orm_1.eq)(schema_1.orders.id, id));
    const timelineNote = [
        input.note?.trim(),
        input.trackingNumber?.trim() ? `Courier tracking: ${input.trackingNumber.trim()}` : null,
        syncedPaymentStatus ? `Payment status: ${syncedPaymentStatus}` : null,
    ]
        .filter(Boolean)
        .join(" | ");
    await db_1.db.insert(schema_1.orderStatusHistory).values({
        orderId: id,
        status: input.status,
        note: timelineNote || null,
        createdByUserId: input.userId || null,
    });
    return (0, exports.getById)(id);
};
exports.updateStatus = updateStatus;
// ==================== PAYMENT VERIFICATION ====================
const verifyPayment = async (id, input) => {
    const rows = await db_1.db.select().from(schema_1.orders).where((0, drizzle_orm_1.eq)(schema_1.orders.id, id)).limit(1);
    const order = rows[0];
    if (!order)
        throw new AppError_1.AppError(404, "Order not found");
    if (order.paymentMethod === "cod") {
        throw new AppError_1.AppError(400, "Payment verification is not applicable for Cash on Delivery orders");
    }
    if (input.action === "verified") {
        await db_1.db
            .update(schema_1.orders)
            .set({ paymentStatus: "verified", paymentDate: new Date(), status: "confirmed" })
            .where((0, drizzle_orm_1.eq)(schema_1.orders.id, id));
        await db_1.db.insert(schema_1.orderStatusHistory).values({
            orderId: id,
            status: "confirmed",
            note: input.note?.trim() || "Payment verified — order confirmed",
            createdByUserId: input.userId || null,
        });
    }
    else {
        await db_1.db
            .update(schema_1.orders)
            .set({ paymentStatus: "rejected", status: "payment_pending" })
            .where((0, drizzle_orm_1.eq)(schema_1.orders.id, id));
        await db_1.db.insert(schema_1.orderStatusHistory).values({
            orderId: id,
            status: "payment_pending",
            note: input.note?.trim() || "Payment rejected — please submit a valid screenshot",
            createdByUserId: input.userId || null,
        });
    }
    return (0, exports.getById)(id);
};
exports.verifyPayment = verifyPayment;
// ==================== ADMIN NOTES ====================
const updateAdminNotes = async (id, note, userId) => {
    const rows = await db_1.db.select().from(schema_1.orders).where((0, drizzle_orm_1.eq)(schema_1.orders.id, id)).limit(1);
    const order = rows[0];
    if (!order)
        throw new AppError_1.AppError(404, "Order not found");
    const existing = order.adminNotes?.trim() || "";
    const stamp = `[${new Date().toLocaleString()}]`;
    const updated = existing ? `${existing}\n${stamp} ${note}` : `${stamp} ${note}`;
    await db_1.db.update(schema_1.orders).set({ adminNotes: updated }).where((0, drizzle_orm_1.eq)(schema_1.orders.id, id));
    return (0, exports.getById)(id);
};
exports.updateAdminNotes = updateAdminNotes;
// ==================== CUSTOMER ENDPOINTS ====================
const getMyOrders = async (userId) => {
    const data = await db_1.db
        .select()
        .from(schema_1.orders)
        .where((0, drizzle_orm_1.eq)(schema_1.orders.userId, userId))
        .orderBy((0, drizzle_orm_1.desc)(schema_1.orders.createdAt))
        .limit(50);
    return Promise.all(data.map((order) => attachItemsAndHistory(order)));
};
exports.getMyOrders = getMyOrders;
const trackOrder = async (orderId, phone) => {
    const rows = await db_1.db
        .select()
        .from(schema_1.orders)
        .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.orders.orderId, orderId), (0, drizzle_orm_1.eq)(schema_1.orders.phone, phone)))
        .limit(1);
    const order = rows[0];
    if (!order)
        throw new AppError_1.AppError(404, "Order not found");
    return attachItemsAndHistory(order);
};
exports.trackOrder = trackOrder;
// ==================== INVOICE ====================
const getInvoice = async (id) => {
    const order = await (0, exports.getById)(id);
    if (!order)
        throw new AppError_1.AppError(404, "Order not found");
    return order;
};
exports.getInvoice = getInvoice;
// ==================== DELETE ====================
const remove = async (id) => {
    await db_1.db.delete(schema_1.orderStatusHistory).where((0, drizzle_orm_1.eq)(schema_1.orderStatusHistory.orderId, id));
    await db_1.db.delete(schema_1.orderItems).where((0, drizzle_orm_1.eq)(schema_1.orderItems.orderId, id));
    await db_1.db.delete(schema_1.orders).where((0, drizzle_orm_1.eq)(schema_1.orders.id, id));
    return { success: true };
};
exports.remove = remove;
// ==================== STATS ====================
const getStats = async () => {
    const totalOrders = await db_1.db.select({ count: (0, drizzle_orm_1.sql) `count(*)` }).from(schema_1.orders);
    const totalRevenue = await db_1.db
        .select({ total: (0, drizzle_orm_1.sql) `COALESCE(SUM(total_price), 0)` })
        .from(schema_1.orders)
        .where((0, drizzle_orm_1.eq)(schema_1.orders.status, DELIVERED_STATUS));
    const pendingOrders = await db_1.db
        .select({ count: (0, drizzle_orm_1.sql) `count(*)` })
        .from(schema_1.orders)
        .where((0, drizzle_orm_1.eq)(schema_1.orders.status, DEFAULT_STATUS));
    const pendingPayments = await db_1.db
        .select({ count: (0, drizzle_orm_1.sql) `count(*)` })
        .from(schema_1.orders)
        .where((0, drizzle_orm_1.eq)(schema_1.orders.paymentStatus, "payment_verification"));
    return {
        totalOrders: Number(totalOrders[0].count),
        totalRevenue: Number(totalRevenue[0].total),
        pendingOrders: Number(pendingOrders[0].count),
        pendingPayments: Number(pendingPayments[0].count),
    };
};
exports.getStats = getStats;
//# sourceMappingURL=order.service.js.map