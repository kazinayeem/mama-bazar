import { Router } from "express";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import {
  expenseListSchema,
  expenseIdSchema,
  expenseCreateSchema,
  expenseUpdateSchema,
  expenseSummarySchema,
  expenseMemberSchema,
  expenseByCategorySchema,
  expenseMonthlyReportSchema,
  expenseTrendSchema,
  expenseRangeReportSchema,
  expenseProfitSchema,
  expenseExportSchema,
  expenseCategoryCreateSchema,
  expenseCategoryUpdateSchema,
  expenseCategoryIdSchema,
} from "./expense.schema";
import {
  list,
  getById,
  create,
  update,
  remove,
  categories,
  createCategory,
  updateCategory,
  removeCategory,
  members,
  summary,
  byMember,
  byCategory,
  monthlyReport,
  monthlyTrend,
  rangeReport,
  profitOverview,
  exportCsv,
} from "./expense.controller";

const router = Router();

// Expense categories (static routes must precede /:id)
router.get("/categories", authMiddleware, requirePermission("expenses.view"), asyncHandler(categories));
router.post("/categories", authMiddleware, requirePermission("expenses.create"), validate(expenseCategoryCreateSchema), asyncHandler(createCategory));
router.put("/categories/:id", authMiddleware, requirePermission("expenses.update"), validate(expenseCategoryUpdateSchema), asyncHandler(updateCategory));
router.delete("/categories/:id", authMiddleware, requirePermission("expenses.delete"), validate(expenseCategoryIdSchema), asyncHandler(removeCategory));

// Team members for the expense form
router.get("/members", authMiddleware, requirePermission("expenses.view"), asyncHandler(members));

// Reports (financial data — restricted to report viewers)
router.get("/summary", authMiddleware, requirePermission("reports.view"), validate(expenseSummarySchema), asyncHandler(summary));
router.get("/by-member", authMiddleware, requirePermission("reports.view"), validate(expenseMemberSchema), asyncHandler(byMember));
router.get("/by-category", authMiddleware, requirePermission("reports.view"), validate(expenseByCategorySchema), asyncHandler(byCategory));
router.get("/monthly", authMiddleware, requirePermission("reports.view"), validate(expenseMonthlyReportSchema), asyncHandler(monthlyReport));
router.get("/trends", authMiddleware, requirePermission("reports.view"), validate(expenseTrendSchema), asyncHandler(monthlyTrend));
router.get("/report", authMiddleware, requirePermission("reports.view"), validate(expenseRangeReportSchema), asyncHandler(rangeReport));
router.get("/profit", authMiddleware, requirePermission("reports.view"), validate(expenseProfitSchema), asyncHandler(profitOverview));
router.get("/export/csv", authMiddleware, requirePermission("reports.export"), validate(expenseExportSchema), asyncHandler(exportCsv));

// Expense CRUD
router.get("/", authMiddleware, requirePermission("expenses.view"), validate(expenseListSchema), asyncHandler(list));
router.post("/", authMiddleware, requirePermission("expenses.create"), validate(expenseCreateSchema), asyncHandler(create));
router.put("/:id", authMiddleware, requirePermission("expenses.update"), validate(expenseUpdateSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, requirePermission("expenses.delete"), validate(expenseIdSchema), asyncHandler(remove));
router.get("/:id", authMiddleware, requirePermission("expenses.view"), validate(expenseIdSchema), asyncHandler(getById));

export default router;
