import { Router } from "express";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { bookingListSchema, bookingIdSchema, bookingCreateSchema, bookingUpdateSchema } from "./booking.schema";
import { list, getById, create, update, remove } from "./booking.controller";

const router = Router();

router.get("/", authMiddleware, requirePermission("bookings.view"), validate(bookingListSchema), asyncHandler(list));
router.get("/:id", authMiddleware, requirePermission("bookings.view"), validate(bookingIdSchema), asyncHandler(getById));
router.post("/", authMiddleware, requirePermission("bookings.create"), validate(bookingCreateSchema), asyncHandler(create));
router.put("/:id", authMiddleware, requirePermission("bookings.update"), validate(bookingUpdateSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, requirePermission("bookings.delete"), validate(bookingIdSchema), asyncHandler(remove));

export default router;
