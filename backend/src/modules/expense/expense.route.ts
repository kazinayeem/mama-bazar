import { Router } from "express";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { expenseListSchema, expenseIdSchema, expenseCreateSchema, expenseUpdateSchema } from "./expense.schema";
import { list, categories, getById, create, update, remove } from "./expense.controller";

const router = Router();

router.get("/", authMiddleware, requirePermission("expenses.view"), validate(expenseListSchema), asyncHandler(list));
router.get("/categories", authMiddleware, requirePermission("expenses.view"), asyncHandler(categories));
router.get("/:id", authMiddleware, requirePermission("expenses.view"), validate(expenseIdSchema), asyncHandler(getById));
router.post("/", authMiddleware, requirePermission("expenses.create"), validate(expenseCreateSchema), asyncHandler(create));
router.put("/:id", authMiddleware, requirePermission("expenses.update"), validate(expenseUpdateSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, requirePermission("expenses.delete"), validate(expenseIdSchema), asyncHandler(remove));

export default router;
