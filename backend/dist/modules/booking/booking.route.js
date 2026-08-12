"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const booking_schema_1 = require("./booking.schema");
const booking_controller_1 = require("./booking.controller");
const router = (0, express_1.Router)();
router.get("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("bookings.view"), (0, validate_1.validate)(booking_schema_1.bookingListSchema), (0, asyncHandler_1.asyncHandler)(booking_controller_1.list));
router.get("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("bookings.view"), (0, validate_1.validate)(booking_schema_1.bookingIdSchema), (0, asyncHandler_1.asyncHandler)(booking_controller_1.getById));
router.post("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("bookings.create"), (0, validate_1.validate)(booking_schema_1.bookingCreateSchema), (0, asyncHandler_1.asyncHandler)(booking_controller_1.create));
router.put("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("bookings.update"), (0, validate_1.validate)(booking_schema_1.bookingUpdateSchema), (0, asyncHandler_1.asyncHandler)(booking_controller_1.update));
router.delete("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("bookings.delete"), (0, validate_1.validate)(booking_schema_1.bookingIdSchema), (0, asyncHandler_1.asyncHandler)(booking_controller_1.remove));
exports.default = router;
//# sourceMappingURL=booking.route.js.map