"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || (function () {
    var ownKeys = function(o) {
        ownKeys = Object.getOwnPropertyNames || function (o) {
            var ar = [];
            for (var k in o) if (Object.prototype.hasOwnProperty.call(o, k)) ar[ar.length] = k;
            return ar;
        };
        return ownKeys(o);
    };
    return function (mod) {
        if (mod && mod.__esModule) return mod;
        var result = {};
        if (mod != null) for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
        __setModuleDefault(result, mod);
        return result;
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
exports.exportCsv = exports.profitOverview = exports.rangeReport = exports.monthlyTrend = exports.monthlyReport = exports.byCategory = exports.byMember = exports.summary = exports.members = exports.removeCategory = exports.updateCategory = exports.createCategory = exports.categories = exports.remove = exports.update = exports.create = exports.getById = exports.list = void 0;
const expenseService = __importStar(require("./expense.service"));
const list = async (req, res) => {
    const result = await expenseService.listExpenses({
        page: Number(req.query.page),
        limit: Number(req.query.limit),
        search: req.query.search,
        status: req.query.status,
        memberId: req.query.memberId,
        categoryId: req.query.categoryId,
        paymentMethod: req.query.paymentMethod,
        dateFrom: req.query.dateFrom,
        dateTo: req.query.dateTo,
        amountMin: req.query.amountMin,
        amountMax: req.query.amountMax,
    });
    res.json({ success: true, ...result });
};
exports.list = list;
const getById = async (req, res) => {
    const data = await expenseService.getExpense(Number(req.params.id));
    if (!data)
        return res.status(404).json({ success: false, message: "Expense not found" });
    res.json({ success: true, data });
};
exports.getById = getById;
const create = async (req, res) => {
    const data = await expenseService.createExpense(req.body, {
        id: req.user?.id,
        name: req.user?.name,
    });
    res.status(201).json({ success: true, data });
};
exports.create = create;
const update = async (req, res) => {
    const data = await expenseService.updateExpense(Number(req.params.id), req.body, {
        id: req.user?.id,
        name: req.user?.name,
    });
    res.json({ success: true, data });
};
exports.update = update;
const remove = async (req, res) => {
    const result = await expenseService.deleteExpense(Number(req.params.id));
    res.json(result);
};
exports.remove = remove;
// ==================== CATEGORIES ====================
const categories = async (_req, res) => {
    const data = await expenseService.listExpenseCategories();
    res.json({ success: true, data });
};
exports.categories = categories;
const createCategory = async (req, res) => {
    const data = await expenseService.createExpenseCategory(req.body);
    res.status(201).json({ success: true, data });
};
exports.createCategory = createCategory;
const updateCategory = async (req, res) => {
    const data = await expenseService.updateExpenseCategory(Number(req.params.id), req.body);
    res.json({ success: true, data });
};
exports.updateCategory = updateCategory;
const removeCategory = async (req, res) => {
    const result = await expenseService.deleteExpenseCategory(Number(req.params.id));
    res.json(result);
};
exports.removeCategory = removeCategory;
// ==================== MEMBERS ====================
const members = async (_req, res) => {
    const data = await expenseService.listMembers();
    res.json({ success: true, data });
};
exports.members = members;
// ==================== REPORTS ====================
const summary = async (req, res) => {
    const data = await expenseService.getSummary({
        dateFrom: req.query.dateFrom,
        dateTo: req.query.dateTo,
    });
    res.json({ success: true, data });
};
exports.summary = summary;
const byMember = async (req, res) => {
    const data = await expenseService.getExpenseByMember({
        memberId: req.query.memberId,
        dateFrom: req.query.dateFrom,
        dateTo: req.query.dateTo,
    });
    res.json({ success: true, data });
};
exports.byMember = byMember;
const byCategory = async (req, res) => {
    const data = await expenseService.getExpenseByCategory({
        memberId: req.query.memberId,
        dateFrom: req.query.dateFrom,
        dateTo: req.query.dateTo,
    });
    res.json({ success: true, data });
};
exports.byCategory = byCategory;
const monthlyReport = async (req, res) => {
    const data = await expenseService.getMonthlyReport({
        year: req.query.year,
        month: req.query.month,
        memberId: req.query.memberId,
        categoryId: req.query.categoryId,
        page: Number(req.query.page),
        limit: Number(req.query.limit),
    });
    res.json({ success: true, data });
};
exports.monthlyReport = monthlyReport;
const monthlyTrend = async (req, res) => {
    const data = await expenseService.getMonthlyTrend({
        year: req.query.year,
    });
    res.json({ success: true, ...data });
};
exports.monthlyTrend = monthlyTrend;
const rangeReport = async (req, res) => {
    const data = await expenseService.getRangeReport({
        dateFrom: req.query.dateFrom,
        dateTo: req.query.dateTo,
        memberId: req.query.memberId,
        categoryId: req.query.categoryId,
        status: req.query.status,
    });
    res.json({ success: true, data });
};
exports.rangeReport = rangeReport;
const profitOverview = async (req, res) => {
    const data = await expenseService.getProfitOverview({
        year: req.query.year,
        month: req.query.month,
    });
    res.json({ success: true, data });
};
exports.profitOverview = profitOverview;
const exportCsv = async (req, res) => {
    const data = await expenseService.exportExpensesCsv({
        page: Number(req.query.page),
        limit: Number(req.query.limit),
        search: req.query.search,
        status: req.query.status,
        memberId: req.query.memberId,
        categoryId: req.query.categoryId,
        paymentMethod: req.query.paymentMethod,
        dateFrom: req.query.dateFrom,
        dateTo: req.query.dateTo,
        amountMin: req.query.amountMin,
        amountMax: req.query.amountMax,
    });
    res.json({ success: true, data });
};
exports.exportCsv = exportCsv;
//# sourceMappingURL=expense.controller.js.map