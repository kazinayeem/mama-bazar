import { Router } from "express";
import { uploadMemory } from "../../middleware/uploadMemory";
import { asyncHandler } from "../../middleware/asyncHandler";
import { uploadPaymentProof } from "./upload.controller";

const router = Router();

// Public — customers upload payment screenshots before placing an order
router.post("/payment-proof", uploadMemory.single("file"), asyncHandler(uploadPaymentProof));

export default router;
