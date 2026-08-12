"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const expense_schema_1 = require("./expense.schema");
const expense_controller_1 = require("./expense.controller");
const router = (0, express_1.Router)();
// Expense categories (static routes must precede /:id)
router.get("/categories", auth_1.authMiddleware, (0, auth_1.requirePermission)("expenses.view"), (0, asyncHandler_1.asyncHandler)(expense_controller_1.categories));
router.post("/categories", auth_1.authMiddleware, (0, auth_1.requirePermission)("expenses.create"), (0, validate_1.validate)(expense_schema_1.expenseCategoryCreateSchema), (0, asyncHandler_1.asyncHandler)(expense_controller_1.createCategory));
router.put("/categories/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("expenses.update"), (0, validate_1.validate)(expense_schema_1.expenseCategoryUpdateSchema), (0, asyncHandler_1.asyncHandler)(expense_controller_1.updateCategory));
router.delete("/categories/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("expenses.delete"), (0, validate_1.validate)(expense_schema_1.expenseCategoryIdSchema), (0, asyncHandler_1.asyncHandler)(expense_controller_1.removeCategory));
// Team members for the expense form
router.get("/members", auth_1.authMiddleware, (0, auth_1.requirePermission)("expenses.view"), (0, asyncHandler_1.asyncHandler)(expense_controller_1.members));
// Reports (financial data — restricted to report viewers)
router.get("/summary", auth_1.authMiddleware, (0, auth_1.requirePermission)("reports.view"), (0, validate_1.validate)(expense_schema_1.expenseSummarySchema), (0, asyncHandler_1.asyncHandler)(expense_controller_1.summary));
router.get("/by-member", auth_1.authMiddleware, (0, auth_1.requirePermission)("reports.view"), (0, validate_1.validate)(expense_schema_1.expenseMemberSchema), (0, asyncHandler_1.asyncHandler)(expense_controller_1.byMember));
router.get("/by-category", auth_1.authMiddleware, (0, auth_1.requirePermission)("reports.view"), (0, validate_1.validate)(expense_schema_1.expenseByCategorySchema), (0, asyncHandler_1.asyncHandler)(expense_controller_1.byCategory));
router.get("/monthly", auth_1.authMiddleware, (0, auth_1.requirePermission)("reports.view"), (0, validate_1.validate)(expense_schema_1.expenseMonthlyReportSchema), (0, asyncHandler_1.asyncHandler)(expense_controller_1.monthlyReport));
router.get("/trends", auth_1.authMiddleware, (0, auth_1.requirePermission)("reports.view"), (0, validate_1.validate)(expense_schema_1.expenseTrendSchema), (0, asyncHandler_1.asyncHandler)(expense_controller_1.monthlyTrend));
router.get("/report", auth_1.authMiddleware, (0, auth_1.requirePermission)("reports.view"), (0, validate_1.validate)(expense_schema_1.expenseRangeReportSchema), (0, asyncHandler_1.asyncHandler)(expense_controller_1.rangeReport));
router.get("/profit", auth_1.authMiddleware, (0, auth_1.requirePermission)("reports.view"), (0, validate_1.validate)(expense_schema_1.expenseProfitSchema), (0, asyncHandler_1.asyncHandler)(expense_controller_1.profitOverview));
router.get("/export/csv", auth_1.authMiddleware, (0, auth_1.requirePermission)("reports.export"), (0, validate_1.validate)(expense_schema_1.expenseExportSchema), (0, asyncHandler_1.asyncHandler)(expense_controller_1.exportCsv));
// Expense CRUD
router.get("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("expenses.view"), (0, validate_1.validate)(expense_schema_1.expenseListSchema), (0, asyncHandler_1.asyncHandler)(expense_controller_1.list));
router.post("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("expenses.create"), (0, validate_1.validate)(expense_schema_1.expenseCreateSchema), (0, asyncHandler_1.asyncHandler)(expense_controller_1.create));
router.put("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("expenses.update"), (0, validate_1.validate)(expense_schema_1.expenseUpdateSchema), (0, asyncHandler_1.asyncHandler)(expense_controller_1.update));
router.delete("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("expenses.delete"), (0, validate_1.validate)(expense_schema_1.expenseIdSchema), (0, asyncHandler_1.asyncHandler)(expense_controller_1.remove));
router.get("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("expenses.view"), (0, validate_1.validate)(expense_schema_1.expenseIdSchema), (0, asyncHandler_1.asyncHandler)(expense_controller_1.getById));
exports.default = router;
//# sourceMappingURL=expense.route.js.map