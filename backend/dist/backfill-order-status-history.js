"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const db_1 = require("./config/db");
const schema_1 = require("./config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const STATUS_SEQUENCE = [
    "pending",
    "confirmed",
    "processing",
    "packed",
    "shipped",
    "out_for_delivery",
    "delivered",
    "cancelled",
];
const getStatusChain = (status) => {
    const index = STATUS_SEQUENCE.indexOf(status);
    if (index === -1)
        return [status];
    return STATUS_SEQUENCE.slice(0, index + 1);
};
const buildTimelineRows = (orderId, status, createdAt, userId) => {
    const chain = getStatusChain(status);
    const stepMinutes = Math.max(5, Math.floor(60 / Math.max(chain.length, 1)));
    return chain.map((step, index) => ({
        orderId,
        status: step,
        note: index === 0 ? "Backfilled initial timeline" : `Backfilled ${step} step`,
        createdByUserId: userId,
        createdAt: new Date(createdAt.getTime() + index * stepMinutes * 60 * 1000),
    }));
};
const buildChainRowsFromStart = (orderId, status, createdAt, userId) => {
    const chain = getStatusChain(status);
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
        const allOrders = await db_1.db.select().from(schema_1.orders);
        const rows = [];
        for (const order of allOrders) {
            const existing = await db_1.db
                .select()
                .from(schema_1.orderStatusHistory)
                .where((0, drizzle_orm_1.eq)(schema_1.orderStatusHistory.orderId, order.id))
                .orderBy(schema_1.orderStatusHistory.createdAt);
            if (existing.length === 0) {
                rows.push(...buildTimelineRows(order.id, order.status, order.createdAt, order.userId || null));
                continue;
            }
            const hasFallbackOnly = existing.length === 1 && order.status !== 'pending';
            if (!hasFallbackOnly)
                continue;
            await db_1.db.delete(schema_1.orderStatusHistory).where((0, drizzle_orm_1.eq)(schema_1.orderStatusHistory.orderId, order.id));
            rows.push(...buildChainRowsFromStart(order.id, order.status, order.createdAt, order.userId || null));
        }
        if (rows.length > 0) {
            await db_1.db.insert(schema_1.orderStatusHistory).values(rows);
        }
        console.log(`✓ Backfilled missing order timeline rows (${rows.length} entries)`);
    }
    catch (error) {
        console.error("✗ Failed to backfill order timeline rows", error);
        process.exitCode = 1;
    }
};
run();
//# sourceMappingURL=backfill-order-status-history.js.map