import { db } from "../../config/db";
import { orders, orderItems, products, users, categories } from "../../config/schema";
import { eq, and, gte, lte, sql, desc, asc } from "drizzle-orm";

const toNumber = (v: any) => (v === null || v === undefined ? 0 : Number(v));

const startOfDay = (d: Date) => new Date(d.getFullYear(), d.getMonth(), d.getDate());
const daysAgo = (n: number) => new Date(Date.now() - n * 24 * 60 * 60 * 1000);

export interface DashboardQuery {
  range?: string;
}

export const getDashboard = async (query: DashboardQuery) => {
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
  ] as const;

  const [totalOrdersAgg, revenueAgg, todayAgg, avgAgg, deliveredAgg, cancelledAgg, lowStockAgg, outStockAgg, usersAgg, productsAgg] =
    await Promise.all([
      db
        .select({ count: sql<number>`count(*)`, revenue: sql<string>`COALESCE(SUM(total_price), 0)` })
        .from(orders)
        .where(eq(orders.status, "delivered")),
      db
        .select({ revenue: sql<string>`COALESCE(SUM(total_price), 0)` })
        .from(orders)
        .where(and(gte(orders.createdAt, since), lte(orders.createdAt, new Date()))),
      db
        .select({ count: sql<number>`count(*)` })
        .from(orders)
        .where(and(gte(orders.createdAt, todayStart), lte(orders.createdAt, new Date()))),
      db
        .select({ avg: sql<string>`COALESCE(AVG(total_price), 0)` })
        .from(orders)
        .where(eq(orders.status, "delivered")),
      db
        .select({ count: sql<number>`count(*)` })
        .from(orders)
        .where(and(eq(orders.status, "delivered"), gte(orders.createdAt, since))),
      db
        .select({ count: sql<number>`count(*)` })
        .from(orders)
        .where(and(eq(orders.status, "cancelled"), gte(orders.createdAt, since))),
      db
        .select({ count: sql<number>`count(*)` })
        .from(products)
        .where(sql`stock > 0 AND stock <= 10`),
      db
        .select({ count: sql<number>`count(*)` })
        .from(products)
        .where(sql`stock <= 0`),
      db
        .select({ count: sql<number>`count(*)` })
        .from(users)
        .where(eq(users.role, "user")),
      db
        .select({ count: sql<number>`count(*)` })
        .from(products),
    ]);

  // Sales by day for the chart
  const salesRows = await db
    .select({
      day: sql<string>`DATE(created_at)`,
      revenue: sql<string>`COALESCE(SUM(total_price), 0)`,
      count: sql<number>`count(*)`,
    })
    .from(orders)
    .where(and(gte(orders.createdAt, since), lte(orders.createdAt, new Date())))
    .groupBy(sql`DATE(created_at)`)
    .orderBy(sql`DATE(created_at)`);

  const salesByDay = new Map(salesRows.map((r) => [String(r.day), { revenue: toNumber(r.revenue), count: Number(r.count) }]));

  const revenueChart: { date: string; revenue: number; orders: number }[] = [];
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
  const statusRows = await db
    .select({ status: orders.status, count: sql<number>`count(*)` })
    .from(orders)
    .groupBy(orders.status);

  const statusCounts: Record<string, number> = {};
  for (const s of orderStatuses) statusCounts[s] = 0;
  for (const r of statusRows) statusCounts[r.status] = Number(r.count);

  // Payment method breakdown
  const paymentRows = await db
    .select({ method: orders.paymentMethod, count: sql<number>`count(*)`, revenue: sql<string>`COALESCE(SUM(total_price), 0)` })
    .from(orders)
    .groupBy(orders.paymentMethod);

  // Recent orders (latest 8)
  const recentRows = await db
    .select()
    .from(orders)
    .orderBy(desc(orders.createdAt))
    .limit(8);

  // Top products by quantity sold
  const topProducts = await db
    .select({
      productId: orderItems.productId,
      title: products.title,
      slug: products.slug,
      image: products.images,
      quantity: sql<number>`SUM(${orderItems.quantity})`,
      revenue: sql<string>`SUM(${orderItems.quantity} * ${orderItems.price})`,
    })
    .from(orderItems)
    .leftJoin(products, eq(orderItems.productId, products.id))
    .groupBy(orderItems.productId, products.title, products.slug, products.images)
    .orderBy(sql`SUM(${orderItems.quantity}) DESC`)
    .limit(5);

  // Top categories
  const topCategories = await db
    .select({
      categoryId: products.categoryId,
      name: categories.name,
      count: sql<number>`COUNT(${products.id})`,
    })
    .from(products)
    .leftJoin(categories, eq(products.categoryId, categories.id))
    .where(sql`${products.categoryId} IS NOT NULL`)
    .groupBy(products.categoryId, categories.name)
    .orderBy(sql`COUNT(${products.id}) DESC`)
    .limit(6);

  // Low stock products
  const lowStock = await db
    .select({
      id: products.id,
      title: products.title,
      slug: products.slug,
      stock: products.stock,
      image: products.images,
      price: products.price,
    })
    .from(products)
    .where(sql`stock <= 10`)
    .orderBy(asc(products.stock))
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
