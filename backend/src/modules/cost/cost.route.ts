import { Router } from "express";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { costListSchema, costIdSchema, costCreateSchema, costUpdateSchema } from "./cost.schema";
import { list, getById, create, update, remove } from "./cost.controller";

const router = Router();

router.get("/", authMiddleware, requirePermission("costs.view"), validate(costListSchema), asyncHandler(list));
router.get("/:id", authMiddleware, requirePermission("costs.view"), validate(costIdSchema), asyncHandler(getById));
router.post("/", authMiddleware, requirePermission("costs.create"), validate(costCreateSchema), asyncHandler(create));
router.put("/:id", authMiddleware, requirePermission("costs.update"), validate(costUpdateSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, requirePermission("costs.delete"), validate(costIdSchema), asyncHandler(remove));

export default router;
