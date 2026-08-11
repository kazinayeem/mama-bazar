import { Request, Response } from "express";
import * as expenseService from "./expense.service";

export const list = async (req: Request, res: Response) => {
  const result = await expenseService.listExpenses({
    page: Number(req.query.page),
    limit: Number(req.query.limit),
    search: req.query.search as string | undefined,
    status: req.query.status as string | undefined,
    memberId: req.query.memberId as string | undefined,
    categoryId: req.query.categoryId as string | undefined,
    paymentMethod: req.query.paymentMethod as string | undefined,
    dateFrom: req.query.dateFrom as string | undefined,
    dateTo: req.query.dateTo as string | undefined,
    amountMin: req.query.amountMin as string | undefined,
    amountMax: req.query.amountMax as string | undefined,
  });
  res.json({ success: true, ...result });
};

export const getById = async (req: Request, res: Response) => {
  const data = await expenseService.getExpense(Number(req.params.id));
  if (!data) return res.status(404).json({ success: false, message: "Expense not found" });
  res.json({ success: true, data });
};

export const create = async (req: Request, res: Response) => {
  const data = await expenseService.createExpense(req.body, {
    id: (req as any).user?.id,
    name: (req as any).user?.name,
  });
  res.status(201).json({ success: true, data });
};

export const update = async (req: Request, res: Response) => {
  const data = await expenseService.updateExpense(Number(req.params.id), req.body, {
    id: (req as any).user?.id,
    name: (req as any).user?.name,
  });
  res.json({ success: true, data });
};

export const remove = async (req: Request, res: Response) => {
  const result = await expenseService.deleteExpense(Number(req.params.id));
  res.json(result);
};

// ==================== CATEGORIES ====================

export const categories = async (_req: Request, res: Response) => {
  const data = await expenseService.listExpenseCategories();
  res.json({ success: true, data });
};

export const createCategory = async (req: Request, res: Response) => {
  const data = await expenseService.createExpenseCategory(req.body);
  res.status(201).json({ success: true, data });
};

export const updateCategory = async (req: Request, res: Response) => {
  const data = await expenseService.updateExpenseCategory(Number(req.params.id), req.body);
  res.json({ success: true, data });
};

export const removeCategory = async (req: Request, res: Response) => {
  const result = await expenseService.deleteExpenseCategory(Number(req.params.id));
  res.json(result);
};

// ==================== MEMBERS ====================

export const members = async (_req: Request, res: Response) => {
  const data = await expenseService.listMembers();
  res.json({ success: true, data });
};

// ==================== REPORTS ====================

export const summary = async (req: Request, res: Response) => {
  const data = await expenseService.getSummary({
    dateFrom: req.query.dateFrom as string | undefined,
    dateTo: req.query.dateTo as string | undefined,
  });
  res.json({ success: true, data });
};

export const byMember = async (req: Request, res: Response) => {
  const data = await expenseService.getExpenseByMember({
    memberId: req.query.memberId as string | undefined,
    dateFrom: req.query.dateFrom as string | undefined,
    dateTo: req.query.dateTo as string | undefined,
  });
  res.json({ success: true, data });
};

export const byCategory = async (req: Request, res: Response) => {
  const data = await expenseService.getExpenseByCategory({
    memberId: req.query.memberId as string | undefined,
    dateFrom: req.query.dateFrom as string | undefined,
    dateTo: req.query.dateTo as string | undefined,
  });
  res.json({ success: true, data });
};

export const monthlyReport = async (req: Request, res: Response) => {
  const data = await expenseService.getMonthlyReport({
    year: req.query.year as string | undefined,
    month: req.query.month as string | undefined,
    memberId: req.query.memberId as string | undefined,
    categoryId: req.query.categoryId as string | undefined,
    page: Number(req.query.page),
    limit: Number(req.query.limit),
  });
  res.json({ success: true, data });
};

export const monthlyTrend = async (req: Request, res: Response) => {
  const data = await expenseService.getMonthlyTrend({
    year: req.query.year as string | undefined,
  });
  res.json({ success: true, ...data });
};

export const rangeReport = async (req: Request, res: Response) => {
  const data = await expenseService.getRangeReport({
    dateFrom: req.query.dateFrom as string | undefined,
    dateTo: req.query.dateTo as string | undefined,
    memberId: req.query.memberId as string | undefined,
    categoryId: req.query.categoryId as string | undefined,
    status: req.query.status as string | undefined,
  });
  res.json({ success: true, data });
};

export const profitOverview = async (req: Request, res: Response) => {
  const data = await expenseService.getProfitOverview({
    year: req.query.year as string | undefined,
    month: req.query.month as string | undefined,
  });
  res.json({ success: true, data });
};

export const exportCsv = async (req: Request, res: Response) => {
  const data = await expenseService.exportExpensesCsv({
    page: Number(req.query.page),
    limit: Number(req.query.limit),
    search: req.query.search as string | undefined,
    status: req.query.status as string | undefined,
    memberId: req.query.memberId as string | undefined,
    categoryId: req.query.categoryId as string | undefined,
    paymentMethod: req.query.paymentMethod as string | undefined,
    dateFrom: req.query.dateFrom as string | undefined,
    dateTo: req.query.dateTo as string | undefined,
    amountMin: req.query.amountMin as string | undefined,
    amountMax: req.query.amountMax as string | undefined,
  });
  res.json({ success: true, data });
};
