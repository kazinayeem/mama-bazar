"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const uploadMemory_1 = require("../../middleware/uploadMemory");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const upload_controller_1 = require("./upload.controller");
const router = (0, express_1.Router)();
// Public — customers upload payment screenshots before placing an order
router.post("/payment-proof", uploadMemory_1.uploadMemory.single("file"), (0, asyncHandler_1.asyncHandler)(upload_controller_1.uploadPaymentProof));
exports.default = router;
//# sourceMappingURL=upload.route.js.map