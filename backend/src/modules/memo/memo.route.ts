import { Router } from "express";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { memoListSchema, memoIdSchema, memoCreateSchema, memoDeleteManySchema } from "./memo.schema";
import { list, getById, create, remove, removeMany } from "./memo.controller";

const router = Router();

router.get("/", authMiddleware, requirePermission("memos.view"), validate(memoListSchema), asyncHandler(list));
router.get("/:id", authMiddleware, requirePermission("memos.view"), validate(memoIdSchema), asyncHandler(getById));
router.post("/", authMiddleware, requirePermission("memos.upload"), validate(memoCreateSchema), asyncHandler(create));
router.delete("/:id", authMiddleware, requirePermission("memos.delete"), validate(memoIdSchema), asyncHandler(remove));
router.post("/bulk-delete", authMiddleware, requirePermission("memos.delete"), validate(memoDeleteManySchema), asyncHandler(removeMany));

export default router;
