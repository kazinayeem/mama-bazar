import { Router } from "express";
import { getBySlug, getAll, create, update, remove, submitContact, getContactMessages, updateContactStatus } from "./pages.controller";
import { authMiddleware, adminOnly } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { pageSlugSchema, createPageSchema, updatePageSchema, pageIdSchema, contactMessageSchema } from "./pages.schema";

const router = Router();

// Public
router.get("/p/:slug", validate(pageSlugSchema), asyncHandler(getBySlug));
router.post("/contact", validate(contactMessageSchema), asyncHandler(submitContact));

// Admin
router.get("/", authMiddleware, adminOnly, asyncHandler(getAll));
router.post("/", authMiddleware, adminOnly, validate(createPageSchema), asyncHandler(create));
router.put("/:id", authMiddleware, adminOnly, validate(updatePageSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, adminOnly, validate(pageIdSchema), asyncHandler(remove));
router.get("/contact", authMiddleware, adminOnly, asyncHandler(getContactMessages));
router.patch("/contact/:id", authMiddleware, adminOnly, asyncHandler(updateContactStatus));

export default router;