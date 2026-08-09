import { db } from "../../config/db";
import { expenses, expenseCategories } from "../../config/schema";
import { eq, and, like, desc, sql } from "drizzle-orm";
import { AppError } from "../../utils/AppError";

const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 20;

export interface ExpenseQuery {
  page?: number;
  limit?: number;
  search?: string;
  status?: string;
}

export const listExpenses = async (query: ExpenseQuery) => {
  const page = Math.max(1, query.page || DEFAULT_PAGE);
  const limit = Math.max(1, query.limit || DEFAULT_LIMIT);
  const offset = (page - 1) * limit;

  const conditions: any[] = [];
  if (query.status) conditions.push(eq(expenses.status, query.status));
  if (query.search) conditions.push(like(expenses.title, `%${query.search}%`));
  const where = conditions.length > 0 ? and(...conditions) : undefined;

  const data = await db
    .select({
      id: expenses.id,
      title: expenses.title,
      description: expenses.description,
      categoryId: expenses.categoryId,
      categoryName: expenseCategories.name,
      amount: expenses.amount,
      paymentMethod: expenses.paymentMethod,
      vendor: expenses.vendor,
      expenseDate: expenses.expenseDate,
      referenceNumber: expenses.referenceNumber,
      attachmentUrl: expenses.attachmentUrl,
      notes: expenses.notes,
      status: expenses.status,
      createdAt: expenses.createdAt,
    })
    .from(expenses)
    .leftJoin(expenseCategories, eq(expenses.categoryId, expenseCategories.id))
    .where(where)
    .orderBy(desc(expenses.expenseDate))
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

export const listExpenseCategories = async () => {
  return db
    .select({ id: expenseCategories.id, name: expenseCategories.name, status: expenseCategories.status })
    .from(expenseCategories)
    .orderBy(expenseCategories.sortOrder);
};

export const getExpense = async (id: number) => {
  const rows = await db
    .select({
      id: expenses.id,
      title: expenses.title,
      description: expenses.description,
      categoryId: expenses.categoryId,
      categoryName: expenseCategories.name,
      amount: expenses.amount,
      paymentMethod: expenses.paymentMethod,
      vendor: expenses.vendor,
      expenseDate: expenses.expenseDate,
      referenceNumber: expenses.referenceNumber,
      attachmentUrl: expenses.attachmentUrl,
      notes: expenses.notes,
      status: expenses.status,
      createdAt: expenses.createdAt,
      updatedAt: expenses.updatedAt,
    })
    .from(expenses)
    .leftJoin(expenseCategories, eq(expenses.categoryId, expenseCategories.id))
    .where(eq(expenses.id, id))
    .limit(1);
  return rows[0] || null;
};

export const createExpense = async (input: {
  title: string;
  description?: string | null;
  categoryId?: number | null;
  amount: string | number;
  paymentMethod?: string;
  vendor?: string | null;
  expenseDate: string;
  referenceNumber?: string | null;
  attachmentUrl?: string | null;
  notes?: string | null;
  status?: string;
}) => {
  const amount = String(input.amount ?? 0);
  const [inserted] = await db.insert(expenses).values({
    title: input.title,
    description: input.description || null,
    categoryId: input.categoryId || null,
    amount,
    paymentMethod: input.paymentMethod || "cash",
    vendor: input.vendor || null,
    expenseDate: input.expenseDate,
    referenceNumber: input.referenceNumber || null,
    attachmentUrl: input.attachmentUrl || null,
    notes: input.notes || null,
    status: (input.status as "pending" | "approved" | "rejected") || "approved",
  });
  return inserted;
};

export const updateExpense = async (id: number, input: Record<string, unknown>) => {
  const existing = await getExpense(id);
  if (!existing) throw new AppError(404, "Expense not found");
  await db
    .update(expenses)
    .set({
      title: input.title !== undefined ? String(input.title) : existing.title,
      description: input.description !== undefined ? (input.description as string | null) : existing.description,
      categoryId: input.categoryId !== undefined ? (Number(input.categoryId) || null) : existing.categoryId,
      amount: input.amount !== undefined ? String(input.amount) : existing.amount,
      paymentMethod: input.paymentMethod !== undefined ? String(input.paymentMethod) : existing.paymentMethod,
      vendor: input.vendor !== undefined ? (input.vendor as string | null) : existing.vendor,
      expenseDate: input.expenseDate !== undefined ? String(input.expenseDate) : existing.expenseDate,
      referenceNumber: input.referenceNumber !== undefined ? (input.referenceNumber as string | null) : existing.referenceNumber,
      attachmentUrl: input.attachmentUrl !== undefined ? (input.attachmentUrl as string | null) : existing.attachmentUrl,
      notes: input.notes !== undefined ? (input.notes as string | null) : existing.notes,
      status: input.status !== undefined ? String(input.status) : existing.status,
    })
    .where(eq(expenses.id, id));
  return getExpense(id);
};

export const deleteExpense = async (id: number) => {
  const existing = await getExpense(id);
  if (!existing) throw new AppError(404, "Expense not found");
  await db.delete(expenses).where(eq(expenses.id, id));
  return { success: true };
};
