import { Router } from "express";
import { trackPurchase } from "./analytics.controller";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { trackPurchaseSchema } from "./analytics.schema";

const router = Router();

router.post("/purchase", validate(trackPurchaseSchema), asyncHandler(trackPurchase));

export default router;
