import { Router } from "express";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { rentalListSchema, rentalIdSchema, rentalCreateSchema, rentalUpdateSchema } from "./rental.schema";
import { list, getById, create, update, remove } from "./rental.controller";

const router = Router();

router.get("/", authMiddleware, requirePermission("rentals.view"), validate(rentalListSchema), asyncHandler(list));
router.get("/:id", authMiddleware, requirePermission("rentals.view"), validate(rentalIdSchema), asyncHandler(getById));
router.post("/", authMiddleware, requirePermission("rentals.create"), validate(rentalCreateSchema), asyncHandler(create));
router.put("/:id", authMiddleware, requirePermission("rentals.update"), validate(rentalUpdateSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, requirePermission("rentals.delete"), validate(rentalIdSchema), asyncHandler(remove));

export default router;
