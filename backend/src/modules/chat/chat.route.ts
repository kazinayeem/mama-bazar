import { Router } from "express";
import { handleChat } from "./chat.controller";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { chatMessageSchema } from "./chat.schema";

const router = Router();

// Public route: POST /api/chat
router.post("/", validate(chatMessageSchema), asyncHandler(handleChat));

export default router;
