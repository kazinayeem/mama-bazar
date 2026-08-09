import { Request, Response } from "express";
import * as expenseService from "./expense.service";

export const list = async (req: Request, res: Response) => {
  const result = await expenseService.listExpenses({
    page: Number(req.query.page),
    limit: Number(req.query.limit),
    search: req.query.search as string | undefined,
    status: req.query.status as string | undefined,
  });
  res.json({ success: true, ...result });
};

export const categories = async (_req: Request, res: Response) => {
  const data = await expenseService.listExpenseCategories();
  res.json({ success: true, data });
};

export const getById = async (req: Request, res: Response) => {
  const data = await expenseService.getExpense(Number(req.params.id));
  if (!data) return res.status(404).json({ success: false, message: "Expense not found" });
  res.json({ success: true, data });
};

export const create = async (req: Request, res: Response) => {
  const data = await expenseService.createExpense(req.body);
  res.status(201).json({ success: true, data });
};

export const update = async (req: Request, res: Response) => {
  const data = await expenseService.updateExpense(Number(req.params.id), req.body);
  res.json({ success: true, data });
};

export const remove = async (req: Request, res: Response) => {
  await expenseService.deleteExpense(Number(req.params.id));
  res.json({ success: true, message: "Expense deleted" });
};
