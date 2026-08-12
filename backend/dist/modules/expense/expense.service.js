"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.exportExpensesCsv = exports.getProfitOverview = exports.getRangeReport = exports.getMonthlyTrend = exports.getMonthlyReport = exports.getExpenseByCategory = exports.getExpenseByMember = exports.getSummary = exports.listMembers = exports.deleteExpenseCategory = exports.updateExpenseCategory = exports.createExpenseCategory = exports.listExpenseCategories = exports.deleteExpense = exports.updateExpense = exports.createExpense = exports.getExpense = exports.listExpenses = void 0;
const db_1 = require("../../config/db");
const schema_1 = require("../../config/schema");
const drizzle_orm_1 = require("drizzle-orm");
const AppError_1 = require("../../utils/AppError");
const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 20;
const toNumber = (v) => {
    const n = Number(v);
    return Number.isNaN(n) ? 0 : n;
};
const todayStart = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")} 00:00:00`;
};
const monthStart = (year, month) => `${year}-${String(month).padStart(2, "0")}-01 00:00:00`;
const nextMonthStart = (year, month) => {
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
const normalizeDateInput = (input) => {
    const trimmed = input.trim().replace(" ", "T");
    return trimmed.length <= 10 ? `${trimmed} 00:00:00` : trimmed.replace("T", " ");
};
const dateSql = (input) => (0, drizzle_orm_1.sql) `STR_TO_DATE(${normalizeDateInput(input)}, '%Y-%m-%d %H:%i:%s')`;
const buildWhere = (query) => {
    const conditions = [(0, drizzle_orm_1.ne)(schema_1.expenses.status, "rejected")];
    if (query.status) {
        conditions.push((0, drizzle_orm_1.eq)(schema_1.expenses.status, query.status));
    }
    if (query.memberId)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.expenses.memberId, Number(query.memberId)));
    if (query.categoryId)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.expenses.categoryId, Number(query.categoryId)));
    if (query.paymentMethod)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.expenses.paymentMethod, query.paymentMethod));
    if (query.dateFrom)
        conditions.push((0, drizzle_orm_1.gte)(schema_1.expenses.expenseDate, dateSql(query.dateFrom)));
    if (query.dateTo)
        conditions.push((0, drizzle_orm_1.lte)(schema_1.expenses.expenseDate, dateSql(`${query.dateTo} 23:59:59`)));
    if (query.amountMin)
        conditions.push((0, drizzle_orm_1.gte)(schema_1.expenses.amount, String(query.amountMin)));
    if (query.amountMax)
        conditions.push((0, drizzle_orm_1.lte)(schema_1.expenses.amount, String(query.amountMax)));
    if (query.search) {
        const q = `%${query.search.toLowerCase()}%`;
        conditions.push((0, drizzle_orm_1.sql) `(LOWER(${schema_1.expenses.title}) LIKE ${q} OR LOWER(${schema_1.expenses.description}) LIKE ${q} OR LOWER(${schema_1.expenses.memberName}) LIKE ${q} OR LOWER(${schema_1.expenses.referenceNumber}) LIKE ${q} OR LOWER(${schema_1.expenses.vendor}) LIKE ${q})`);
    }
    return conditions.length > 0 ? (0, drizzle_orm_1.and)(...conditions) : undefined;
};
const expenseSelect = {
    id: schema_1.expenses.id,
    title: schema_1.expenses.title,
    description: schema_1.expenses.description,
    categoryId: schema_1.expenses.categoryId,
    categoryName: schema_1.expenseCategories.name,
    amount: schema_1.expenses.amount,
    paymentMethod: schema_1.expenses.paymentMethod,
    vendor: schema_1.expenses.vendor,
    memberId: schema_1.expenses.memberId,
    memberName: schema_1.expenses.memberName,
    expenseDate: schema_1.expenses.expenseDate,
    referenceNumber: schema_1.expenses.referenceNumber,
    attachmentUrl: schema_1.expenses.attachmentUrl,
    notes: schema_1.expenses.notes,
    status: schema_1.expenses.status,
    createdById: schema_1.expenses.createdById,
    createdByName: schema_1.users.name,
    createdAt: schema_1.expenses.createdAt,
    updatedAt: schema_1.expenses.updatedAt,
};
const listExpenses = async (query) => {
    const page = Math.max(1, query.page || DEFAULT_PAGE);
    const limit = Math.max(1, query.limit || DEFAULT_LIMIT);
    const offset = (page - 1) * limit;
    const where = buildWhere(query);
    const data = await db_1.db
        .select(expenseSelect)
        .from(schema_1.expenses)
        .leftJoin(schema_1.expenseCategories, (0, drizzle_orm_1.eq)(schema_1.expenses.categoryId, schema_1.expenseCategories.id))
        .leftJoin(schema_1.users, (0, drizzle_orm_1.eq)(schema_1.expenses.createdById, schema_1.users.id))
        .where(where)
        .orderBy((0, drizzle_orm_1.desc)(schema_1.expenses.expenseDate), (0, drizzle_orm_1.desc)(schema_1.expenses.id))
        .limit(limit)
        .offset(offset);
    const countResult = await db_1.db
        .select({ count: (0, drizzle_orm_1.sql) `count(*)` })
        .from(schema_1.expenses)
        .where(where);
    const total = Number(countResult[0].count);
    return {
        data,
        pagination: { page, limit, total, totalPages: Math.ceil(total / limit) },
    };
};
exports.listExpenses = listExpenses;
const getExpense = async (id) => {
    const rows = await db_1.db
        .select(expenseSelect)
        .from(schema_1.expenses)
        .leftJoin(schema_1.expenseCategories, (0, drizzle_orm_1.eq)(schema_1.expenses.categoryId, schema_1.expenseCategories.id))
        .leftJoin(schema_1.users, (0, drizzle_orm_1.eq)(schema_1.expenses.createdById, schema_1.users.id))
        .where((0, drizzle_orm_1.eq)(schema_1.expenses.id, id))
        .limit(1);
    return rows[0] || null;
};
exports.getExpense = getExpense;
const resolveMemberName = async (memberId, fallback) => {
    if (fallback)
        return fallback;
    if (!memberId)
        return null;
    const rows = await db_1.db
        .select({ name: schema_1.users.name })
        .from(schema_1.users)
        .where((0, drizzle_orm_1.eq)(schema_1.users.id, memberId))
        .limit(1);
    return rows[0]?.name || null;
};
const createExpense = async (input, authUser) => {
    const amount = String(input.amount ?? 0);
    const memberName = await resolveMemberName(input.memberId, input.memberName);
    const [header] = await db_1.db.insert(schema_1.expenses).values({
        title: input.title,
        description: input.description || null,
        categoryId: input.categoryId || null,
        amount,
        paymentMethod: input.paymentMethod || "cash",
        vendor: input.vendor || null,
        memberId: input.memberId || null,
        memberName,
        expenseDate: (0, drizzle_orm_1.sql) `STR_TO_DATE(${normalizeDateInput(input.expenseDate)}, '%Y-%m-%d %H:%i:%s')`,
        referenceNumber: input.referenceNumber || null,
        attachmentUrl: input.attachmentUrl || null,
        notes: input.notes || null,
        status: input.status || "approved",
        createdById: authUser?.id || null,
    });
    return (0, exports.getExpense)(header.insertId);
};
exports.createExpense = createExpense;
const updateExpense = async (id, input, authUser) => {
    const existing = await (0, exports.getExpense)(id);
    if (!existing)
        throw new AppError_1.AppError(404, "Expense not found");
    const set = {
        updatedAt: (0, drizzle_orm_1.sql) `NOW()`,
    };
    if (input.title !== undefined)
        set.title = String(input.title);
    if (input.description !== undefined)
        set.description = input.description || null;
    if (input.categoryId !== undefined)
        set.categoryId = Number(input.categoryId) || null;
    if (input.amount !== undefined)
        set.amount = String(input.amount);
    if (input.paymentMethod !== undefined)
        set.paymentMethod = String(input.paymentMethod);
    if (input.vendor !== undefined)
        set.vendor = input.vendor || null;
    if (input.memberId !== undefined) {
        const memberId = Number(input.memberId) || null;
        set.memberId = memberId;
        set.memberName = await resolveMemberName(memberId, input.memberName);
    }
    if (input.expenseDate !== undefined)
        set.expenseDate = (0, drizzle_orm_1.sql) `STR_TO_DATE(${normalizeDateInput(String(input.expenseDate))}, '%Y-%m-%d %H:%i:%s')`;
    if (input.referenceNumber !== undefined)
        set.referenceNumber = input.referenceNumber || null;
    if (input.attachmentUrl !== undefined)
        set.attachmentUrl = input.attachmentUrl || null;
    if (input.notes !== undefined)
        set.notes = input.notes || null;
    if (input.status !== undefined)
        set.status = input.status;
    await db_1.db.update(schema_1.expenses).set(set).where((0, drizzle_orm_1.eq)(schema_1.expenses.id, id));
    return (0, exports.getExpense)(id);
};
exports.updateExpense = updateExpense;
const deleteExpense = async (id) => {
    const existing = await (0, exports.getExpense)(id);
    if (!existing)
        throw new AppError_1.AppError(404, "Expense not found");
    await db_1.db.delete(schema_1.expenses).where((0, drizzle_orm_1.eq)(schema_1.expenses.id, id));
    return { success: true };
};
exports.deleteExpense = deleteExpense;
// ==================== EXPENSE CATEGORIES ====================
const listExpenseCategories = async () => {
    return db_1.db
        .select({
        id: schema_1.expenseCategories.id,
        name: schema_1.expenseCategories.name,
        description: schema_1.expenseCategories.description,
        status: schema_1.expenseCategories.status,
        sortOrder: schema_1.expenseCategories.sortOrder,
        createdAt: schema_1.expenseCategories.createdAt,
    })
        .from(schema_1.expenseCategories)
        .orderBy(schema_1.expenseCategories.sortOrder, schema_1.expenseCategories.id);
};
exports.listExpenseCategories = listExpenseCategories;
const createExpenseCategory = async (input) => {
    const [header] = await db_1.db.insert(schema_1.expenseCategories).values({
        name: input.name,
        description: input.description || null,
        sortOrder: input.sortOrder || 0,
        status: input.status || "active",
    });
    const rows = await db_1.db
        .select()
        .from(schema_1.expenseCategories)
        .where((0, drizzle_orm_1.eq)(schema_1.expenseCategories.id, header.insertId));
    return rows[0] || null;
};
exports.createExpenseCategory = createExpenseCategory;
const updateExpenseCategory = async (id, input) => {
    const existing = await db_1.db
        .select()
        .from(schema_1.expenseCategories)
        .where((0, drizzle_orm_1.eq)(schema_1.expenseCategories.id, id))
        .limit(1);
    if (!existing[0])
        throw new AppError_1.AppError(404, "Expense category not found");
    await db_1.db
        .update(schema_1.expenseCategories)
        .set({
        name: input.name !== undefined ? String(input.name) : existing[0].name,
        description: input.description !== undefined ? input.description || null : existing[0].description,
        sortOrder: input.sortOrder !== undefined ? Number(input.sortOrder) : existing[0].sortOrder,
        status: input.status !== undefined ? input.status : existing[0].status,
    })
        .where((0, drizzle_orm_1.eq)(schema_1.expenseCategories.id, id));
    const rows = await db_1.db
        .select()
        .from(schema_1.expenseCategories)
        .where((0, drizzle_orm_1.eq)(schema_1.expenseCategories.id, id))
        .limit(1);
    return rows[0] || null;
};
exports.updateExpenseCategory = updateExpenseCategory;
const deleteExpenseCategory = async (id) => {
    const existing = await db_1.db
        .select()
        .from(schema_1.expenseCategories)
        .where((0, drizzle_orm_1.eq)(schema_1.expenseCategories.id, id))
        .limit(1);
    if (!existing[0])
        throw new AppError_1.AppError(404, "Expense category not found");
    const usage = await db_1.db
        .select({ count: (0, drizzle_orm_1.sql) `count(*)` })
        .from(schema_1.expenses)
        .where((0, drizzle_orm_1.eq)(schema_1.expenses.categoryId, id));
    const usageCount = Number(usage[0].count);
    if (usageCount > 0) {
        return { success: false, usageCount, message: "Category is used by expenses" };
    }
    await db_1.db.delete(schema_1.expenseCategories).where((0, drizzle_orm_1.eq)(schema_1.expenseCategories.id, id));
    return { success: true, usageCount: 0 };
};
exports.deleteExpenseCategory = deleteExpenseCategory;
// ==================== MEMBERS ====================
const listMembers = async () => {
    const rows = await db_1.db
        .select({ id: schema_1.users.id, name: schema_1.users.name, phone: schema_1.users.phone, role: schema_1.users.role })
        .from(schema_1.users)
        .where((0, drizzle_orm_1.sql) `${schema_1.users.role} IN ('admin', 'manager') AND ${schema_1.users.status} = 'active'`)
        .orderBy(schema_1.users.name);
    return rows;
};
exports.listMembers = listMembers;
// ==================== REPORTS ====================
const reportWhere = (params) => {
    const conditions = [(0, drizzle_orm_1.ne)(schema_1.expenses.status, "rejected")];
    if (params.status)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.expenses.status, params.status));
    if (params.memberId)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.expenses.memberId, Number(params.memberId)));
    if (params.categoryId)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.expenses.categoryId, Number(params.categoryId)));
    if (params.dateFrom)
        conditions.push((0, drizzle_orm_1.gte)(schema_1.expenses.expenseDate, dateSql(params.dateFrom)));
    if (params.dateTo)
        conditions.push((0, drizzle_orm_1.lte)(schema_1.expenses.expenseDate, dateSql(`${params.dateTo} 23:59:59`)));
    return conditions.length > 0 ? (0, drizzle_orm_1.and)(...conditions) : undefined;
};
const getSummary = async (params = {}) => {
    const where = reportWhere(params);
    const today = todayStart();
    const week = weekStart();
    const now = new Date();
    const monthStartStr = monthStart(now.getFullYear(), now.getMonth() + 1);
    const [totalAgg, monthAgg, weekAgg, todayAgg] = await Promise.all([
        db_1.db.select({ total: (0, drizzle_orm_1.sql) `COALESCE(SUM(amount), 0)`, count: (0, drizzle_orm_1.sql) `count(*)` }).from(schema_1.expenses).where(where),
        db_1.db
            .select({ total: (0, drizzle_orm_1.sql) `COALESCE(SUM(amount), 0)`, count: (0, drizzle_orm_1.sql) `count(*)` })
            .from(schema_1.expenses)
            .where((0, drizzle_orm_1.and)(where, (0, drizzle_orm_1.gte)(schema_1.expenses.expenseDate, dateSql(monthStartStr)))),
        db_1.db
            .select({ total: (0, drizzle_orm_1.sql) `COALESCE(SUM(amount), 0)`, count: (0, drizzle_orm_1.sql) `count(*)` })
            .from(schema_1.expenses)
            .where((0, drizzle_orm_1.and)(where, (0, drizzle_orm_1.gte)(schema_1.expenses.expenseDate, dateSql(week)))),
        db_1.db
            .select({ total: (0, drizzle_orm_1.sql) `COALESCE(SUM(amount), 0)`, count: (0, drizzle_orm_1.sql) `count(*)` })
            .from(schema_1.expenses)
            .where((0, drizzle_orm_1.and)(where, (0, drizzle_orm_1.gte)(schema_1.expenses.expenseDate, dateSql(today)))),
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
exports.getSummary = getSummary;
const getExpenseByMember = async (params) => {
    const where = reportWhere(params);
    const rows = await db_1.db
        .select({
        memberId: schema_1.expenses.memberId,
        memberName: schema_1.expenses.memberName,
        total: (0, drizzle_orm_1.sql) `COALESCE(SUM(${schema_1.expenses.amount}), 0)`,
        count: (0, drizzle_orm_1.sql) `count(*)`,
    })
        .from(schema_1.expenses)
        .where(where)
        .groupBy(schema_1.expenses.memberId, schema_1.expenses.memberName)
        .orderBy((0, drizzle_orm_1.sql) `SUM(${schema_1.expenses.amount}) DESC`);
    return rows.map((r) => ({
        memberId: r.memberId,
        memberName: r.memberName || "Unassigned",
        total: toNumber(r.total),
        count: Number(r.count),
    }));
};
exports.getExpenseByMember = getExpenseByMember;
const getExpenseByCategory = async (params) => {
    const where = reportWhere(params);
    const rows = await db_1.db
        .select({
        categoryId: schema_1.expenses.categoryId,
        categoryName: schema_1.expenseCategories.name,
        total: (0, drizzle_orm_1.sql) `COALESCE(SUM(${schema_1.expenses.amount}), 0)`,
        count: (0, drizzle_orm_1.sql) `count(*)`,
    })
        .from(schema_1.expenses)
        .leftJoin(schema_1.expenseCategories, (0, drizzle_orm_1.eq)(schema_1.expenses.categoryId, schema_1.expenseCategories.id))
        .where(where)
        .groupBy(schema_1.expenses.categoryId, schema_1.expenseCategories.name)
        .orderBy((0, drizzle_orm_1.sql) `SUM(${schema_1.expenses.amount}) DESC`);
    return rows.map((r) => ({
        categoryId: r.categoryId,
        categoryName: r.categoryName || "Uncategorized",
        total: toNumber(r.total),
        count: Number(r.count),
    }));
};
exports.getExpenseByCategory = getExpenseByCategory;
const getMonthlyReport = async (params) => {
    const now = new Date();
    const year = Number(params.year) || now.getFullYear();
    const month = Number(params.month) || now.getMonth() + 1;
    const from = monthStart(year, month);
    const to = nextMonthStart(year, month);
    const where = (0, drizzle_orm_1.and)(reportWhere({ memberId: params.memberId, categoryId: params.categoryId }), (0, drizzle_orm_1.gte)(schema_1.expenses.expenseDate, dateSql(from)), (0, drizzle_orm_1.lte)(schema_1.expenses.expenseDate, dateSql(to)));
    const agg = await db_1.db
        .select({
        total: (0, drizzle_orm_1.sql) `COALESCE(SUM(${schema_1.expenses.amount}), 0)`,
        count: (0, drizzle_orm_1.sql) `count(*)`,
        average: (0, drizzle_orm_1.sql) `COALESCE(AVG(${schema_1.expenses.amount}), 0)`,
        highest: (0, drizzle_orm_1.sql) `COALESCE(MAX(${schema_1.expenses.amount}), 0)`,
        lowest: (0, drizzle_orm_1.sql) `COALESCE(MIN(${schema_1.expenses.amount}), 0)`,
    })
        .from(schema_1.expenses)
        .where(where);
    const page = Math.max(1, params.page || DEFAULT_PAGE);
    const limit = Math.max(1, params.limit || DEFAULT_LIMIT);
    const [expenseRows, memberRows, categoryRows] = await Promise.all([
        db_1.db
            .select(expenseSelect)
            .from(schema_1.expenses)
            .leftJoin(schema_1.expenseCategories, (0, drizzle_orm_1.eq)(schema_1.expenses.categoryId, schema_1.expenseCategories.id))
            .leftJoin(schema_1.users, (0, drizzle_orm_1.eq)(schema_1.expenses.createdById, schema_1.users.id))
            .where(where)
            .orderBy((0, drizzle_orm_1.desc)(schema_1.expenses.expenseDate), (0, drizzle_orm_1.desc)(schema_1.expenses.id))
            .limit(limit)
            .offset((page - 1) * limit),
        (0, exports.getExpenseByMember)({ dateFrom: from.slice(0, 10), dateTo: to.slice(0, 10), memberId: params.memberId }),
        (0, exports.getExpenseByCategory)({ dateFrom: from.slice(0, 10), dateTo: to.slice(0, 10), memberId: params.memberId }),
    ]);
    const countResult = await db_1.db.select({ count: (0, drizzle_orm_1.sql) `count(*)` }).from(schema_1.expenses).where(where);
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
exports.getMonthlyReport = getMonthlyReport;
const getMonthlyTrend = async (params) => {
    const year = Number(params.year) || new Date().getFullYear();
    const monthExpr = (0, drizzle_orm_1.sql) `MONTH(expense_date)`;
    const rows = await db_1.db
        .select({
        month: monthExpr,
        total: (0, drizzle_orm_1.sql) `COALESCE(SUM(${schema_1.expenses.amount}), 0)`,
        count: (0, drizzle_orm_1.sql) `count(*)`,
    })
        .from(schema_1.expenses)
        .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.ne)(schema_1.expenses.status, "rejected"), (0, drizzle_orm_1.gte)(schema_1.expenses.expenseDate, dateSql(`${year}-01-01`)), (0, drizzle_orm_1.lte)(schema_1.expenses.expenseDate, dateSql(`${year}-12-31 23:59:59`))))
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
exports.getMonthlyTrend = getMonthlyTrend;
const getRangeReport = async (params) => {
    const where = reportWhere(params);
    const agg = await db_1.db
        .select({
        total: (0, drizzle_orm_1.sql) `COALESCE(SUM(${schema_1.expenses.amount}), 0)`,
        count: (0, drizzle_orm_1.sql) `count(*)`,
    })
        .from(schema_1.expenses)
        .where(where);
    const [expenseRows, memberRows, categoryRows] = await Promise.all([
        db_1.db
            .select(expenseSelect)
            .from(schema_1.expenses)
            .leftJoin(schema_1.expenseCategories, (0, drizzle_orm_1.eq)(schema_1.expenses.categoryId, schema_1.expenseCategories.id))
            .leftJoin(schema_1.users, (0, drizzle_orm_1.eq)(schema_1.expenses.createdById, schema_1.users.id))
            .where(where)
            .orderBy((0, drizzle_orm_1.desc)(schema_1.expenses.expenseDate), (0, drizzle_orm_1.desc)(schema_1.expenses.id)),
        (0, exports.getExpenseByMember)(params),
        (0, exports.getExpenseByCategory)(params),
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
exports.getRangeReport = getRangeReport;
const getProfitOverview = async (params) => {
    const now = new Date();
    const year = Number(params.year) || now.getFullYear();
    const month = Number(params.month) || now.getMonth() + 1;
    const from = monthStart(year, month);
    const to = nextMonthStart(year, month);
    const fromDate = new Date(from.replace(" ", "T"));
    const toDate = new Date(to.replace(" ", "T"));
    const [revenueAgg, costAgg, expenseAgg] = await Promise.all([
        db_1.db
            .select({ revenue: (0, drizzle_orm_1.sql) `COALESCE(SUM(${schema_1.orders.totalPrice}), 0)` })
            .from(schema_1.orders)
            .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.orders.status, "delivered"), (0, drizzle_orm_1.gte)(schema_1.orders.createdAt, fromDate), (0, drizzle_orm_1.lte)(schema_1.orders.createdAt, toDate))),
        db_1.db
            .select({ cost: (0, drizzle_orm_1.sql) `COALESCE(SUM(${schema_1.orderItems.quantity} * COALESCE(${schema_1.products.costPrice}, 0)), 0)` })
            .from(schema_1.orderItems)
            .innerJoin(schema_1.orders, (0, drizzle_orm_1.eq)(schema_1.orderItems.orderId, schema_1.orders.id))
            .innerJoin(schema_1.products, (0, drizzle_orm_1.eq)(schema_1.orderItems.productId, schema_1.products.id))
            .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.eq)(schema_1.orders.status, "delivered"), (0, drizzle_orm_1.gte)(schema_1.orders.createdAt, fromDate), (0, drizzle_orm_1.lte)(schema_1.orders.createdAt, toDate))),
        db_1.db
            .select({ total: (0, drizzle_orm_1.sql) `COALESCE(SUM(${schema_1.expenses.amount}), 0)` })
            .from(schema_1.expenses)
            .where((0, drizzle_orm_1.and)((0, drizzle_orm_1.ne)(schema_1.expenses.status, "rejected"), (0, drizzle_orm_1.gte)(schema_1.expenses.expenseDate, dateSql(from)), (0, drizzle_orm_1.lte)(schema_1.expenses.expenseDate, dateSql(to)))),
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
exports.getProfitOverview = getProfitOverview;
const exportExpensesCsv = async (query) => {
    const conditions = [(0, drizzle_orm_1.ne)(schema_1.expenses.status, "rejected")];
    if (query.status)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.expenses.status, query.status));
    if (query.memberId)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.expenses.memberId, Number(query.memberId)));
    if (query.categoryId)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.expenses.categoryId, Number(query.categoryId)));
    if (query.paymentMethod)
        conditions.push((0, drizzle_orm_1.eq)(schema_1.expenses.paymentMethod, query.paymentMethod));
    if (query.dateFrom)
        conditions.push((0, drizzle_orm_1.gte)(schema_1.expenses.expenseDate, dateSql(query.dateFrom)));
    if (query.dateTo)
        conditions.push((0, drizzle_orm_1.lte)(schema_1.expenses.expenseDate, dateSql(`${query.dateTo} 23:59:59`)));
    if (query.amountMin)
        conditions.push((0, drizzle_orm_1.gte)(schema_1.expenses.amount, String(query.amountMin)));
    if (query.amountMax)
        conditions.push((0, drizzle_orm_1.lte)(schema_1.expenses.amount, String(query.amountMax)));
    if (query.search) {
        const q = `%${query.search.toLowerCase()}%`;
        conditions.push((0, drizzle_orm_1.sql) `(LOWER(${schema_1.expenses.title}) LIKE ${q} OR LOWER(${schema_1.expenses.description}) LIKE ${q} OR LOWER(${schema_1.expenses.memberName}) LIKE ${q} OR LOWER(${schema_1.expenses.referenceNumber}) LIKE ${q} OR LOWER(${schema_1.expenses.vendor}) LIKE ${q})`);
    }
    const where = conditions.length > 0 ? (0, drizzle_orm_1.and)(...conditions) : undefined;
    const rows = await db_1.db
        .select(expenseSelect)
        .from(schema_1.expenses)
        .leftJoin(schema_1.expenseCategories, (0, drizzle_orm_1.eq)(schema_1.expenses.categoryId, schema_1.expenseCategories.id))
        .leftJoin(schema_1.users, (0, drizzle_orm_1.eq)(schema_1.expenses.createdById, schema_1.users.id))
        .where(where)
        .orderBy((0, drizzle_orm_1.desc)(schema_1.expenses.expenseDate), (0, drizzle_orm_1.desc)(schema_1.expenses.id));
    const esc = (v) => {
        const s = v === null || v === undefined ? "" : String(v);
        return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
    };
    const formatCsvDate = (v) => {
        if (v === null || v === undefined)
            return "";
        if (v instanceof Date) {
            const d = v;
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
        }
        return String(v).slice(0, 10);
    };
    const header = ["Date", "Expense ID", "Member", "Category", "Title", "Description", "Amount", "Payment Method", "Reference", "Status"];
    const lines = rows.map((r) => [
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
        .join(","));
    const csv = [header.map(esc).join(","), ...lines].join("\n");
    return { csv: `\uFEFF${csv}`, count: rows.length };
};
exports.exportExpensesCsv = exportExpensesCsv;
//# sourceMappingURL=expense.service.js.map