"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.getDashboard = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const toNumber = (v) => (v === null || v === undefined ? 0 : Number(v));
const startOfDay = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate());
const daysAgo = (n) => new Date(Date.now() - n * 24 * 60 * 60 * 1000);
const getDashboard = async (query) => {
    const days = query.range === "7" ? 7 : query.range === "30" ? 30 : query.range === "365" ? 365 : 30;
    const since = daysAgo(days);
    const todayStart = startOfDay(new Date());
    const orderStatuses = [
        "pending",
        "confirmed",
        "processing",
        "packed",
        "shipped",
        "out_for_delivery",
        "delivered",
        "cancelled",
    ];
    const [totalOrdersAgg, revenueAgg, todayAgg, avgAgg, deliveredAgg, cancelledAgg, lowStockAgg, outStockAgg, usersAgg, productsAgg] = await Promise.all([
        db_1.db
            .select({ count: (0, drizzle_orm_1.sql) `count(*)`, revenue: (0, drizzle_orm_1.sql) `COALESCE(SUM(total_price), 0)` })
            .from(schema_1.orders)
            .where((0, drizzle_orm_1.eq)(schema_1.orders.status, "delivered")),
        db_1.db
            .select({ revenue: (0, drizzle_orm_1.sql) `COALESCE(SUM(total_price), 0)` })
            .from(schema_1.orders)
            .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.gte)(schema_1.orders.createdAt, since), (0, drizzle_orm_1.lte)(schema_1.orders.createdAt, new Date()))),
        db_1.db
            .select({ count: (0, drizzle_orm_1.sql) `count(*)` })
            .from(schema_1.orders)
            .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.gte)(schema_1.orders.createdAt, todayStart), (0, drizzle_orm_1.lte)(schema_1.orders.createdAt, new Date()))),
        db_1.db
            .select({ avg: (0, drizzle_orm_1.sql) `COALESCE(AVG(total_price), 0)` })
            .from(schema_1.orders)
            .where((0, drizzle_orm_1.eq)(schema_1.orders.status, "delivered")),
        db_1.db
            .select({ count: (0, drizzle_orm_1.sql) `count(*)` })
            .from(schema_1.orders)
            .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.orders.status, "delivered"), (0, drizzle_orm_1.gte)(schema_1.orders.createdAt, since))),
        db_1.db
            .select({ count: (0, drizzle_orm_1.sql) `count(*)` })
            .from(schema_1.orders)
            .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.orders.status, "cancelled"), (0, drizzle_orm_1.gte)(schema_1.orders.createdAt, since))),
        db_1.db
            .select({ count: (0, drizzle_orm_1.sql) `count(*)` })
            .from(schema_1.products)
            .where((0, drizzle_orm_1.sql) `stock > 0 AND stock <= 10`),
        db_1.db
            .select({ count: (0, drizzle_orm_1.sql) `count(*)` })
            .from(schema_1.products)
            .where((0, drizzle_orm_1.sql) `stock <= 0`),
        db_1.db
            .select({ count: (0, drizzle_orm_1.sql) `count(*)` })
            .from(schema_1.users)
            .where((0, drizzle_orm_1.eq)(schema_1.users.role, "user")),
        db_1.db
            .select({ count: (0, drizzle_orm_1.sql) `count(*)` })
            .from(schema_1.products),
    ]);
    // Sales by day for the chart
    const salesRows = await db_1.db
        .select({
        day: (0, drizzle_orm_1.sql) `DATE(created_at)`,
        revenue: (0, drizzle_orm_1.sql) `COALESCE(SUM(total_price), 0)`,
        count: (0, drizzle_orm_1.sql) `count(*)`,
    })
        .from(schema_1.orders)
        .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.gte)(schema_1.orders.createdAt, since), (0, drizzle_orm_1.lte)(schema_1.orders.createdAt, new Date())))
        .groupBy((0, drizzle_orm_1.sql) `DATE(created_at)`)
        .orderBy((0, drizzle_orm_1.sql) `DATE(created_at)`);
    const salesByDay = new Map(salesRows.map((r) => [String(r.day), { revenue: toNumber(r.revenue), count: Number(r.count) }]));
    const revenueChart = [];
    for (let i = days - 1; i >= 0; i--) {
        const d = new Date(Date.now() - i * 24 * 60 * 60 * 1000);
        const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
        const row = salesByDay.get(key);
        revenueChart.push({
            date: key,
            revenue: row?.revenue ?? 0,
            orders: row?.count ?? 0,
        });
    }
    // Order status breakdown
    const statusRows = await db_1.db
        .select({ status: schema_1.orders.status, count: (0, drizzle_orm_1.sql) `count(*)` })
        .from(schema_1.orders)
        .groupBy(schema_1.orders.status);
    const statusCounts = {};
    for (const s of orderStatuses)
        statusCounts[s] = 0;
    for (const r of statusRows)
        statusCounts[r.status] = Number(r.count);
    // Payment method breakdown
    const paymentRows = await db_1.db
        .select({ method: schema_1.orders.paymentMethod, count: (0, drizzle_orm_1.sql) `count(*)`, revenue: (0, drizzle_orm_1.sql) `COALESCE(SUM(total_price), 0)` })
        .from(schema_1.orders)
        .groupBy(schema_1.orders.paymentMethod);
    // Recent orders (latest 8)
    const recentRows = await db_1.db
        .select()
        .from(schema_1.orders)
        .orderBy((0, drizzle_orm_1.desc)(schema_1.orders.createdAt))
        .limit(8);
    // Top products by quantity sold
    const topProducts = await db_1.db
        .select({
        productId: schema_1.orderItems.productId,
        title: schema_1.products.title,
        slug: schema_1.products.slug,
        image: schema_1.products.images,
        quantity: (0, drizzle_orm_1.sql) `SUM(${schema_1.orderItems.quantity})`,
        revenue: (0, drizzle_orm_1.sql) `SUM(${schema_1.orderItems.quantity} * ${schema_1.orderItems.price})`,
    })
        .from(schema_1.orderItems)
        .leftJoin(schema_1.products, (0, drizzle_orm_1.eq)(schema_1.orderItems.productId, schema_1.products.id))
        .groupBy(schema_1.orderItems.productId, schema_1.products.title, schema_1.products.slug, schema_1.products.images)
        .orderBy((0, drizzle_orm_1.sql) `SUM(${schema_1.orderItems.quantity}) DESC`)
        .limit(5);
    // Top categories
    const topCategories = await db_1.db
        .select({
        categoryId: schema_1.products.categoryId,
        name: schema_1.categories.name,
        count: (0, drizzle_orm_1.sql) `COUNT(${schema_1.products.id})`,
    })
        .from(schema_1.products)
        .leftJoin(schema_1.categories, (0, drizzle_orm_1.eq)(schema_1.products.categoryId, schema_1.categories.id))
        .where((0, drizzle_orm_1.sql) `${schema_1.products.categoryId} IS NOT NULL`)
        .groupBy(schema_1.products.categoryId, schema_1.categories.name)
        .orderBy((0, drizzle_orm_1.sql) `COUNT(${schema_1.products.id}) DESC`)
        .limit(6);
    // Low stock products
    const lowStock = await db_1.db
        .select({
        id: schema_1.products.id,
        title: schema_1.products.title,
        slug: schema_1.products.slug,
        stock: schema_1.products.stock,
        image: schema_1.products.images,
        price: schema_1.products.price,
    })
        .from(schema_1.products)
        .where((0, drizzle_orm_1.sql) `stock <= 10`)
        .orderBy((0, drizzle_orm_1.asc)(schema_1.products.stock))
        .limit(10);
    const totalRevenue = toNumber(totalOrdersAgg[0].revenue);
    const periodRevenue = toNumber(revenueAgg[0].revenue);
    const deliveredCount = Number(deliveredAgg[0].count);
    const avgOrderValue = deliveredCount > 0 ? totalRevenue / deliveredCount : 0;
    return {
        kpis: {
            totalRevenue,
            totalOrders: Number(totalOrdersAgg[0].count),
            avgOrderValue: Math.round(avgOrderValue * 100) / 100,
            totalCustomers: Number(usersAgg[0].count),
            totalProducts: Number(productsAgg[0].count),
            todayOrders: Number(todayAgg[0].count),
            periodRevenue,
            periodOrders: revenueChart.reduce((sum, r) => sum + r.orders, 0),
            deliveredThisPeriod: deliveredCount,
            cancelledThisPeriod: Number(cancelledAgg[0].count),
            lowStock: Number(lowStockAgg[0].count),
            outOfStock: Number(outStockAgg[0].count),
            conversionRate: 0,
            periodVisitors: 0,
        },
        revenueChart,
        statusBreakdown: statusCounts,
        paymentBreakdown: paymentRows.map((r) => ({ method: r.method, count: Number(r.count), revenue: toNumber(r.revenue) })),
        recentOrders: recentRows,
        topProducts: topProducts.map((r) => ({
            id: r.productId,
            title: r.title,
            slug: r.slug,
            image: r.image?.[0] || null,
            quantity: Number(r.quantity),
            revenue: toNumber(r.revenue),
        })),
        topCategories: topCategories.map((r) => ({ id: r.categoryId, name: r.name, count: Number(r.count) })),
        lowStockProducts: lowStock.map((r) => ({
            id: r.id,
            title: r.title,
            slug: r.slug,
            stock: Number(r.stock),
            image: r.image?.[0] || null,
            price: r.price,
        })),
    };
};
exports.getDashboard = getDashboard;
//# sourceMappingURL=admin.service.js.map