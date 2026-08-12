"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const rental_schema_1 = require("./rental.schema");
const rental_controller_1 = require("./rental.controller");
const router = (0, express_1.Router)();
router.get("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("rentals.view"), (0, validate_1.validate)(rental_schema_1.rentalListSchema), (0, asyncHandler_1.asyncHandler)(rental_controller_1.list));
router.get("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("rentals.view"), (0, validate_1.validate)(rental_schema_1.rentalIdSchema), (0, asyncHandler_1.asyncHandler)(rental_controller_1.getById));
router.post("/", auth_1.authMiddleware, (0, auth_1.requirePermission)("rentals.create"), (0, validate_1.validate)(rental_schema_1.rentalCreateSchema), (0, asyncHandler_1.asyncHandler)(rental_controller_1.create));
router.put("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("rentals.update"), (0, validate_1.validate)(rental_schema_1.rentalUpdateSchema), (0, asyncHandler_1.asyncHandler)(rental_controller_1.update));
router.delete("/:id", auth_1.authMiddleware, (0, auth_1.requirePermission)("rentals.delete"), (0, validate_1.validate)(rental_schema_1.rentalIdSchema), (0, asyncHandler_1.asyncHandler)(rental_controller_1.remove));
exports.default = router;
//# sourceMappingURL=rental.route.js.map