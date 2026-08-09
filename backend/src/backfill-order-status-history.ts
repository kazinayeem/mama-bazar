import { db } from "./config/db";
import { orderStatusHistory, orders } from "./config/schema";
import { eq } from "drizzle-orm";

const STATUS_SEQUENCE = [
  "pending",
  "confirmed",
  "processing",
  "packed",
  "shipped",
  "out_for_delivery",
  "delivered",
  "cancelled",
] as const;

const getStatusChain = (status: (typeof STATUS_SEQUENCE)[number]) => {
  const index = STATUS_SEQUENCE.indexOf(status);
  if (index === -1) return [status];
  return STATUS_SEQUENCE.slice(0, index + 1);
};

const buildTimelineRows = (orderId: number, status: string, createdAt: Date, userId: number | null) => {
  const chain = getStatusChain(status as (typeof STATUS_SEQUENCE)[number]);
  const stepMinutes = Math.max(5, Math.floor(60 / Math.max(chain.length, 1)));

  return chain.map((step, index) => ({
    orderId,
    status: step,
    note: index === 0 ? "Backfilled initial timeline" : `Backfilled ${step} step`,
    createdByUserId: userId,
    createdAt: new Date(createdAt.getTime() + index * stepMinutes * 60 * 1000),
  }));
};

const buildChainRowsFromStart = (
  orderId: number,
  status: string,
  createdAt: Date,
  userId: number | null,
) => {
  const chain = getStatusChain(status as (typeof STATUS_SEQUENCE)[number]);
  const stepMinutes = Math.max(5, Math.floor(60 / Math.max(chain.length, 1)));

  return chain.map((step, index) => ({
    orderId,
    status: step,
    note: index === 0 ? "Backfilled initial timeline" : `Backfilled ${step} step`,
    createdByUserId: userId,
    createdAt: new Date(createdAt.getTime() + index * stepMinutes * 60 * 1000),
  }));
};

const run = async () => {
  try {
    const allOrders = await db.select().from(orders);
    const rows = [] as Array<{
      orderId: number;
      status: (typeof STATUS_SEQUENCE)[number];
      note: string | null;
      createdByUserId: number | null;
      createdAt: Date;
    }>;

    for (const order of allOrders) {
      const existing = await db
        .select()
        .from(orderStatusHistory)
        .where(eq(orderStatusHistory.orderId, order.id))
        .orderBy(orderStatusHistory.createdAt);

      if (existing.length === 0) {
        rows.push(...buildTimelineRows(order.id, order.status as any, order.createdAt, order.userId || null));
        continue;
      }

      const hasFallbackOnly = existing.length === 1 && order.status !== 'pending';
      if (!hasFallbackOnly) continue;

      await db.delete(orderStatusHistory).where(eq(orderStatusHistory.orderId, order.id));
      rows.push(...buildChainRowsFromStart(order.id, order.status as any, order.createdAt, order.userId || null));
    }

    if (rows.length > 0) {
      await db.insert(orderStatusHistory).values(rows);
    }

    console.log(`✓ Backfilled missing order timeline rows (${rows.length} entries)`);
  } catch (error) {
    console.error("✗ Failed to backfill order timeline rows", error);
    process.exitCode = 1;
  }
};

run();
