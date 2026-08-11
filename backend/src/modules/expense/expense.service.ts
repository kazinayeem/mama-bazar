import { db } from "../../config/db";
import { expenses, expenseCategories, users, orders, orderItems, products } from "../../config/schema";
import { eq, and, like, ne, gte, lte, desc, sql, isNull } from "drizzle-orm";
import { AppError } from "../../utils/AppError";

const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 20;

const toNumber = (v: unknown) => {
  const n = Number(v);
  return Number.isNaN(n) ? 0 : n;
};

const todayStart = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")} 00:00:00`;
};

const monthStart = (year: number, month: number) =>
  `${year}-${String(month).padStart(2, "0")}-01 00:00:00`;

const nextMonthStart = (year: number, month: number) => {
  const d = new Date(year, month, 1);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-01 00:00:00`;
};

const weekStart = () => {
  const d = new Date();
  const day = d.getDay();
  const diff = (day === 0 ? 6 : day - 1);
  d.setDate(d.getDate() - diff);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")} 00:00:00`;
};

const normalizeDateInput = (input: string) => {
  const trimmed = input.trim().replace(" ", "T");
  return trimmed.length <= 10 ? `${trimmed} 00:00:00` : trimmed.replace("T", " ");
};

const dateSql = (input: string) =>
  sql`STR_TO_DATE(${normalizeDateInput(input)}, '%Y-%m-%d %H:%i:%s')`;

export interface ExpenseQuery {
  page?: number;
  limit?: number;
  search?: string;
  status?: string;
  memberId?: string;
  categoryId?: string;
  paymentMethod?: string;
  dateFrom?: string;
  dateTo?: string;
  amountMin?: string;
  amountMax?: string;
}

const buildWhere = (query: ExpenseQuery) => {
  const conditions: any[] = [ne(expenses.status, "rejected")];
  if (query.status) {
    conditions.push(eq(expenses.status, query.status as "pending" | "approved" | "rejected"));
  }
  if (query.memberId) conditions.push(eq(expenses.memberId, Number(query.memberId)));
  if (query.categoryId) conditions.push(eq(expenses.categoryId, Number(query.categoryId)));
  if (query.paymentMethod) conditions.push(eq(expenses.paymentMethod, query.paymentMethod));
  if (query.dateFrom) conditions.push(gte(expenses.expenseDate, dateSql(query.dateFrom)));
  if (query.dateTo) conditions.push(lte(expenses.expenseDate, dateSql(`${query.dateTo} 23:59:59`)));
  if (query.amountMin) conditions.push(gte(expenses.amount, String(query.amountMin)));
  if (query.amountMax) conditions.push(lte(expenses.amount, String(query.amountMax)));
  if (query.search) {
    const q = `%${query.search.toLowerCase()}%`;
    conditions.push(
      sql`(LOWER(${expenses.title}) LIKE ${q} OR LOWER(${expenses.description}) LIKE ${q} OR LOWER(${expenses.memberName}) LIKE ${q} OR LOWER(${expenses.referenceNumber}) LIKE ${q} OR LOWER(${expenses.vendor}) LIKE ${q})`,
    );
  }
  return conditions.length > 0 ? and(...conditions) : undefined;
};

const expenseSelect = {
  id: expenses.id,
  title: expenses.title,
  description: expenses.description,
  categoryId: expenses.categoryId,
  categoryName: expenseCategories.name,
  amount: expenses.amount,
  paymentMethod: expenses.paymentMethod,
  vendor: expenses.vendor,
  memberId: expenses.memberId,
  memberName: expenses.memberName,
  expenseDate: expenses.expenseDate,
  referenceNumber: expenses.referenceNumber,
  attachmentUrl: expenses.attachmentUrl,
  notes: expenses.notes,
  status: expenses.status,
  createdById: expenses.createdById,
  createdByName: users.name,
  createdAt: expenses.createdAt,
  updatedAt: expenses.updatedAt,
};

export const listExpenses = async (query: ExpenseQuery) => {
  const page = Math.max(1, query.page || DEFAULT_PAGE);
  const limit = Math.max(1, query.limit || DEFAULT_LIMIT);
  const offset = (page - 1) * limit;
  const where = buildWhere(query);

  const data = await db
    .select(expenseSelect)
    .from(expenses)
    .leftJoin(expenseCategories, eq(expenses.categoryId, expenseCategories.id))
    .leftJoin(users, eq(expenses.createdById, users.id))
    .where(where)
    .orderBy(desc(expenses.expenseDate), desc(expenses.id))
    .limit(limit)
    .offset(offset);

  const countResult = await db
    .select({ count: sql<number>`count(*)` })
    .from(expenses)
    .where(where);

  const total = Number(countResult[0].count);
  return {
    data,
    pagination: { page, limit, total, totalPages: Math.ceil(total / limit) },
  };
};

export const getExpense = async (id: number) => {
  const rows = await db
    .select(expenseSelect)
    .from(expenses)
    .leftJoin(expenseCategories, eq(expenses.categoryId, expenseCategories.id))
    .leftJoin(users, eq(expenses.createdById, users.id))
    .where(eq(expenses.id, id))
    .limit(1);
  return rows[0] || null;
};

const resolveMemberName = async (memberId: number | null | undefined, fallback: string | null | undefined) => {
  if (fallback) return fallback;
  if (!memberId) return null;
  const rows = await db
    .select({ name: users.name })
    .from(users)
    .where(eq(users.id, memberId))
    .limit(1);
  return rows[0]?.name || null;
};

export const createExpense = async (
  input: {
    title: string;
    description?: string | null;
    categoryId?: number | null;
    amount: string | number;
    paymentMethod?: string;
    vendor?: string | null;
    memberId?: number | null;
    memberName?: string | null;
    expenseDate: string;
    referenceNumber?: string | null;
    attachmentUrl?: string | null;
    notes?: string | null;
    status?: string;
  },
  authUser?: { id?: number; name?: string },
) => {
  const amount = String(input.amount ?? 0);
  const memberName = await resolveMemberName(input.memberId, input.memberName);
  const [header] = await db.insert(expenses).values({
    title: input.title,
    description: input.description || null,
    categoryId: input.categoryId || null,
    amount,
    paymentMethod: input.paymentMethod || "cash",
    vendor: input.vendor || null,
    memberId: input.memberId || null,
    memberName,
    expenseDate: sql`STR_TO_DATE(${normalizeDateInput(input.expenseDate)}, '%Y-%m-%d %H:%i:%s')`,
    referenceNumber: input.referenceNumber || null,
    attachmentUrl: input.attachmentUrl || null,
    notes: input.notes || null,
    status: (input.status as "pending" | "approved" | "rejected") || "approved",
    createdById: authUser?.id || null,
  });
  return getExpense(header.insertId);
};

export const updateExpense = async (
  id: number,
  input: Record<string, unknown>,
  authUser?: { id?: number; name?: string },
) => {
  const existing = await getExpense(id);
  if (!existing) throw new AppError(404, "Expense not found");

  const set: Record<string, unknown> = {
    updatedAt: sql`NOW()`,
  };
  if (input.title !== undefined) set.title = String(input.title);
  if (input.description !== undefined) set.description = (input.description as string | null) || null;
  if (input.categoryId !== undefined) set.categoryId = Number(input.categoryId) || null;
  if (input.amount !== undefined) set.amount = String(input.amount);
  if (input.paymentMethod !== undefined) set.paymentMethod = String(input.paymentMethod);
  if (input.vendor !== undefined) set.vendor = (input.vendor as string | null) || null;
  if (input.memberId !== undefined) {
    const memberId = Number(input.memberId) || null;
    set.memberId = memberId;
    set.memberName = await resolveMemberName(memberId, input.memberName as string | null | undefined);
  }
  if (input.expenseDate !== undefined)
    set.expenseDate = sql`STR_TO_DATE(${normalizeDateInput(String(input.expenseDate))}, '%Y-%m-%d %H:%i:%s')`;
  if (input.referenceNumber !== undefined) set.referenceNumber = (input.referenceNumber as string | null) || null;
  if (input.attachmentUrl !== undefined) set.attachmentUrl = (input.attachmentUrl as string | null) || null;
  if (input.notes !== undefined) set.notes = (input.notes as string | null) || null;
  if (input.status !== undefined) set.status = input.status as string;

  await db.update(expenses).set(set).where(eq(expenses.id, id));
  return getExpense(id);
};

export const deleteExpense = async (id: number) => {
  const existing = await getExpense(id);
  if (!existing) throw new AppError(404, "Expense not found");
  await db.delete(expenses).where(eq(expenses.id, id));
  return { success: true };
};

// ==================== EXPENSE CATEGORIES ====================

export const listExpenseCategories = async () => {
  return db
    .select({
      id: expenseCategories.id,
      name: expenseCategories.name,
      description: expenseCategories.description,
      status: expenseCategories.status,
      sortOrder: expenseCategories.sortOrder,
      createdAt: expenseCategories.createdAt,
    })
    .from(expenseCategories)
    .orderBy(expenseCategories.sortOrder, expenseCategories.id);
};

export const createExpenseCategory = async (input: {
  name: string;
  description?: string | null;
  sortOrder?: number;
  status?: string;
}) => {
  const [header] = await db.insert(expenseCategories).values({
    name: input.name,
    description: input.description || null,
    sortOrder: input.sortOrder || 0,
    status: (input.status as "active" | "inactive") || "active",
  });
  const rows = await db
    .select()
    .from(expenseCategories)
    .where(eq(expenseCategories.id, header.insertId));
  return rows[0] || null;
};

export const updateExpenseCategory = async (id: number, input: Record<string, unknown>) => {
  const existing = await db
    .select()
    .from(expenseCategories)
    .where(eq(expenseCategories.id, id))
    .limit(1);
  if (!existing[0]) throw new AppError(404, "Expense category not found");

  await db
    .update(expenseCategories)
    .set({
      name: input.name !== undefined ? String(input.name) : existing[0].name,
      description: input.description !== undefined ? (input.description as string | null) || null : existing[0].description,
      sortOrder: input.sortOrder !== undefined ? Number(input.sortOrder) : existing[0].sortOrder,
      status: input.status !== undefined ? (input.status as "active" | "inactive") : existing[0].status,
    })
    .where(eq(expenseCategories.id, id));

  const rows = await db
    .select()
    .from(expenseCategories)
    .where(eq(expenseCategories.id, id))
    .limit(1);
  return rows[0] || null;
};

export const deleteExpenseCategory = async (id: number) => {
  const existing = await db
    .select()
    .from(expenseCategories)
    .where(eq(expenseCategories.id, id))
    .limit(1);
  if (!existing[0]) throw new AppError(404, "Expense category not found");

  const usage = await db
    .select({ count: sql<number>`count(*)` })
    .from(expenses)
    .where(eq(expenses.categoryId, id));
  const usageCount = Number(usage[0].count);

  if (usageCount > 0) {
    return { success: false, usageCount, message: "Category is used by expenses" };
  }

  await db.delete(expenseCategories).where(eq(expenseCategories.id, id));
  return { success: true, usageCount: 0 };
};

// ==================== MEMBERS ====================

export const listMembers = async () => {
  const rows = await db
    .select({ id: users.id, name: users.name, phone: users.phone, role: users.role })
    .from(users)
    .where(sql`${users.role} IN ('admin', 'manager') AND ${users.status} = 'active'`)
    .orderBy(users.name);
  return rows;
};

// ==================== REPORTS ====================

const reportWhere = (params: {
  dateFrom?: string;
  dateTo?: string;
  memberId?: string;
  categoryId?: string;
  status?: string;
}) => {
  const conditions: any[] = [ne(expenses.status, "rejected")];
  if (params.status) conditions.push(eq(expenses.status, params.status as "pending" | "approved" | "rejected"));
  if (params.memberId) conditions.push(eq(expenses.memberId, Number(params.memberId)));
  if (params.categoryId) conditions.push(eq(expenses.categoryId, Number(params.categoryId)));
  if (params.dateFrom) conditions.push(gte(expenses.expenseDate, dateSql(params.dateFrom)));
  if (params.dateTo) conditions.push(lte(expenses.expenseDate, dateSql(`${params.dateTo} 23:59:59`)));
  return conditions.length > 0 ? and(...conditions) : undefined;
};

export const getSummary = async (params: { dateFrom?: string; dateTo?: string } = {}) => {
  const where = reportWhere(params);
  const today = todayStart();
  const week = weekStart();
  const now = new Date();
  const monthStartStr = monthStart(now.getFullYear(), now.getMonth() + 1);

  const [totalAgg, monthAgg, weekAgg, todayAgg] = await Promise.all([
    db.select({ total: sql<string>`COALESCE(SUM(amount), 0)`, count: sql<number>`count(*)` }).from(expenses).where(where),
    db
      .select({ total: sql<string>`COALESCE(SUM(amount), 0)`, count: sql<number>`count(*)` })
      .from(expenses)
      .where(and(where, gte(expenses.expenseDate, dateSql(monthStartStr)))),
    db
      .select({ total: sql<string>`COALESCE(SUM(amount), 0)`, count: sql<number>`count(*)` })
      .from(expenses)
      .where(and(where, gte(expenses.expenseDate, dateSql(week)))),
    db
      .select({ total: sql<string>`COALESCE(SUM(amount), 0)`, count: sql<number>`count(*)` })
      .from(expenses)
      .where(and(where, gte(expenses.expenseDate, dateSql(today)))),
  ]);

  return {
    total: toNumber(totalAgg[0].total),
    totalCount: Number(totalAgg[0].count),
    thisMonth: toNumber(monthAgg[0].total),
    thisMonthCount: Number(monthAgg[0].count),
    thisWeek: toNumber(weekAgg[0].total),
    thisWeekCount: Number(weekAgg[0].count),
    today: toNumber(todayAgg[0].total),
    todayCount: Number(todayAgg[0].count),
  };
};

export const getExpenseByMember = async (params: { memberId?: string; dateFrom?: string; dateTo?: string }) => {
  const where = reportWhere(params);
  const rows = await db
    .select({
      memberId: expenses.memberId,
      memberName: expenses.memberName,
      total: sql<string>`COALESCE(SUM(${expenses.amount}), 0)`,
      count: sql<number>`count(*)`,
    })
    .from(expenses)
    .where(where)
    .groupBy(expenses.memberId, expenses.memberName)
    .orderBy(sql`SUM(${expenses.amount}) DESC`);
  return rows.map((r) => ({
    memberId: r.memberId,
    memberName: r.memberName || "Unassigned",
    total: toNumber(r.total),
    count: Number(r.count),
  }));
};

export const getExpenseByCategory = async (params: { dateFrom?: string; dateTo?: string; memberId?: string }) => {
  const where = reportWhere(params);
  const rows = await db
    .select({
      categoryId: expenses.categoryId,
      categoryName: expenseCategories.name,
      total: sql<string>`COALESCE(SUM(${expenses.amount}), 0)`,
      count: sql<number>`count(*)`,
    })
    .from(expenses)
    .leftJoin(expenseCategories, eq(expenses.categoryId, expenseCategories.id))
    .where(where)
    .groupBy(expenses.categoryId, expenseCategories.name)
    .orderBy(sql`SUM(${expenses.amount}) DESC`);
  return rows.map((r) => ({
    categoryId: r.categoryId,
    categoryName: r.categoryName || "Uncategorized",
    total: toNumber(r.total),
    count: Number(r.count),
  }));
};

export const getMonthlyReport = async (params: {
  year?: string;
  month?: string;
  memberId?: string;
  categoryId?: string;
  page?: number;
  limit?: number;
}) => {
  const now = new Date();
  const year = Number(params.year) || now.getFullYear();
  const month = Number(params.month) || now.getMonth() + 1;
  const from = monthStart(year, month);
  const to = nextMonthStart(year, month);

  const where = and(
    reportWhere({ memberId: params.memberId, categoryId: params.categoryId }),
    gte(expenses.expenseDate, dateSql(from)),
    lte(expenses.expenseDate, dateSql(to)),
  );

  const agg = await db
    .select({
      total: sql<string>`COALESCE(SUM(${expenses.amount}), 0)`,
      count: sql<number>`count(*)`,
      average: sql<string>`COALESCE(AVG(${expenses.amount}), 0)`,
      highest: sql<string>`COALESCE(MAX(${expenses.amount}), 0)`,
      lowest: sql<string>`COALESCE(MIN(${expenses.amount}), 0)`,
    })
    .from(expenses)
    .where(where);

  const page = Math.max(1, params.page || DEFAULT_PAGE);
  const limit = Math.max(1, params.limit || DEFAULT_LIMIT);

  const [expenseRows, memberRows, categoryRows] = await Promise.all([
    db
      .select(expenseSelect)
      .from(expenses)
      .leftJoin(expenseCategories, eq(expenses.categoryId, expenseCategories.id))
      .leftJoin(users, eq(expenses.createdById, users.id))
      .where(where)
      .orderBy(desc(expenses.expenseDate), desc(expenses.id))
      .limit(limit)
      .offset((page - 1) * limit),
    getExpenseByMember({ dateFrom: from.slice(0, 10), dateTo: to.slice(0, 10), memberId: params.memberId }),
    getExpenseByCategory({ dateFrom: from.slice(0, 10), dateTo: to.slice(0, 10), memberId: params.memberId }),
  ]);

  const countResult = await db.select({ count: sql<number>`count(*)` }).from(expenses).where(where);
  const totalCount = Number(countResult[0].count);

  return {
    year,
    month,
    total: toNumber(agg[0].total),
    count: Number(agg[0].count),
    average: Math.round(toNumber(agg[0].average) * 100) / 100,
    highest: toNumber(agg[0].highest),
    lowest: toNumber(agg[0].lowest),
    byMember: memberRows,
    byCategory: categoryRows,
    expenses: expenseRows,
    pagination: { page, limit, total: totalCount, totalPages: Math.ceil(totalCount / limit) },
  };
};

export const getMonthlyTrend = async (params: { year?: string }) => {
  const year = Number(params.year) || new Date().getFullYear();
  const monthExpr = sql<number>`MONTH(expense_date)`;
  const rows = await db
    .select({
      month: monthExpr,
      total: sql<string>`COALESCE(SUM(${expenses.amount}), 0)`,
      count: sql<number>`count(*)`,
    })
    .from(expenses)
    .where(
      and(
        ne(expenses.status, "rejected"),
        gte(expenses.expenseDate, dateSql(`${year}-01-01`)),
        lte(expenses.expenseDate, dateSql(`${year}-12-31 23:59:59`)),
      ),
    )
    .groupBy(monthExpr)
    .orderBy(monthExpr);

  const byMonth = new Map(rows.map((r) => [Number(r.month), { total: toNumber(r.total), count: Number(r.count) }]));
  const MONTH_NAMES = [
    "January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December",
  ];
  const result = [];
  for (let m = 1; m <= 12; m++) {
    const row = byMonth.get(m);
    result.push({
      month: m,
      label: MONTH_NAMES[m - 1],
      total: row?.total ?? 0,
      count: row?.count ?? 0,
    });
  }
  return { year, data: result };
};

export const getRangeReport = async (params: {
  dateFrom?: string;
  dateTo?: string;
  memberId?: string;
  categoryId?: string;
  status?: string;
}) => {
  const where = reportWhere(params);

  const agg = await db
    .select({
      total: sql<string>`COALESCE(SUM(${expenses.amount}), 0)`,
      count: sql<number>`count(*)`,
    })
    .from(expenses)
    .where(where);

  const [expenseRows, memberRows, categoryRows] = await Promise.all([
    db
      .select(expenseSelect)
      .from(expenses)
      .leftJoin(expenseCategories, eq(expenses.categoryId, expenseCategories.id))
      .leftJoin(users, eq(expenses.createdById, users.id))
      .where(where)
      .orderBy(desc(expenses.expenseDate), desc(expenses.id)),
    getExpenseByMember(params),
    getExpenseByCategory(params),
  ]);

  return {
    dateFrom: params.dateFrom || null,
    dateTo: params.dateTo || null,
    total: toNumber(agg[0].total),
    count: Number(agg[0].count),
    byMember: memberRows,
    byCategory: categoryRows,
    expenses: expenseRows,
  };
};

export const getProfitOverview = async (params: { year?: string; month?: string }) => {
  const now = new Date();
  const year = Number(params.year) || now.getFullYear();
  const month = Number(params.month) || now.getMonth() + 1;
  const from = monthStart(year, month);
  const to = nextMonthStart(year, month);
  const fromDate = new Date(from.replace(" ", "T"));
  const toDate = new Date(to.replace(" ", "T"));

  const [revenueAgg, costAgg, expenseAgg] = await Promise.all([
    db
      .select({ revenue: sql<string>`COALESCE(SUM(${orders.totalPrice}), 0)` })
      .from(orders)
      .where(
        and(
          eq(orders.status, "delivered"),
          gte(orders.createdAt, fromDate),
          lte(orders.createdAt, toDate),
        ),
      ),
    db
      .select({ cost: sql<string>`COALESCE(SUM(${orderItems.quantity} * COALESCE(${products.costPrice}, 0)), 0)` })
      .from(orderItems)
      .innerJoin(orders, eq(orderItems.orderId, orders.id))
      .innerJoin(products, eq(orderItems.productId, products.id))
      .where(
        and(
          eq(orders.status, "delivered"),
          gte(orders.createdAt, fromDate),
          lte(orders.createdAt, toDate),
        ),
      ),
    db
      .select({ total: sql<string>`COALESCE(SUM(${expenses.amount}), 0)` })
      .from(expenses)
      .where(
        and(
          ne(expenses.status, "rejected"),
          gte(expenses.expenseDate, dateSql(from)),
          lte(expenses.expenseDate, dateSql(to)),
        ),
      ),
  ]);

  const revenue = toNumber(revenueAgg[0].revenue);
  const productCost = toNumber(costAgg[0].cost);
  const operatingExpenses = toNumber(expenseAgg[0].total);
  const netProfit = revenue - productCost - operatingExpenses;

  return {
    year,
    month,
    revenue,
    productCost,
    operatingExpenses,
    netProfit: Math.round(netProfit * 100) / 100,
    hasRevenueData: revenue > 0,
  };
};

export const exportExpensesCsv = async (query: ExpenseQuery) => {
  const conditions: any[] = [ne(expenses.status, "rejected")];
  if (query.status) conditions.push(eq(expenses.status, query.status as "pending" | "approved" | "rejected"));
  if (query.memberId) conditions.push(eq(expenses.memberId, Number(query.memberId)));
  if (query.categoryId) conditions.push(eq(expenses.categoryId, Number(query.categoryId)));
  if (query.paymentMethod) conditions.push(eq(expenses.paymentMethod, query.paymentMethod));
  if (query.dateFrom) conditions.push(gte(expenses.expenseDate, dateSql(query.dateFrom)));
  if (query.dateTo) conditions.push(lte(expenses.expenseDate, dateSql(`${query.dateTo} 23:59:59`)));
  if (query.amountMin) conditions.push(gte(expenses.amount, String(query.amountMin)));
  if (query.amountMax) conditions.push(lte(expenses.amount, String(query.amountMax)));
  if (query.search) {
    const q = `%${query.search.toLowerCase()}%`;
    conditions.push(
      sql`(LOWER(${expenses.title}) LIKE ${q} OR LOWER(${expenses.description}) LIKE ${q} OR LOWER(${expenses.memberName}) LIKE ${q} OR LOWER(${expenses.referenceNumber}) LIKE ${q} OR LOWER(${expenses.vendor}) LIKE ${q})`,
    );
  }
  const where = conditions.length > 0 ? and(...conditions) : undefined;

  const rows = await db
    .select(expenseSelect)
    .from(expenses)
    .leftJoin(expenseCategories, eq(expenses.categoryId, expenseCategories.id))
    .leftJoin(users, eq(expenses.createdById, users.id))
    .where(where)
    .orderBy(desc(expenses.expenseDate), desc(expenses.id));

  const esc = (v: unknown) => {
    const s = v === null || v === undefined ? "" : String(v);
    return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
  };

  const formatCsvDate = (v: unknown) => {
    if (v === null || v === undefined) return "";
    if (v instanceof Date) {
      const d = v;
      return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
    }
    return String(v).slice(0, 10);
  };

  const header = ["Date", "Expense ID", "Member", "Category", "Title", "Description", "Amount", "Payment Method", "Reference", "Status"];
  const lines = rows.map((r) =>
    [
      formatCsvDate(r.expenseDate),
      `EXP-${String(r.id).padStart(5, "0")}`,
      r.memberName || "",
      r.categoryName || "",
      r.title,
      r.description || "",
      r.amount,
      r.paymentMethod,
      r.referenceNumber || "",
      r.status,
    ]
      .map(esc)
      .join(","),
  );

  const csv = [header.map(esc).join(","), ...lines].join("\n");
  return { csv: `\uFEFF${csv}`, count: rows.length };
};
