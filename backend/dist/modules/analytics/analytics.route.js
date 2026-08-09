"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const analytics_controller_1 = require("./analytics.controller");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const analytics_schema_1 = require("./analytics.schema");
const router = (0, express_1.Router)();
router.post("/purchase", (0, validate_1.validate)(analytics_schema_1.trackPurchaseSchema), (0, asyncHandler_1.asyncHandler)(analytics_controller_1.trackPurchase));
exports.default = router;
//# sourceMappingURL=analytics.route.js.map