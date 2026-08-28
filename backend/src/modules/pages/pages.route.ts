import { Router } from "express";
import { getBySlug, getAll, create, update, remove, submitContact, getContactMessages, updateContactStatus } from "./pages.controller";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import { pageSlugSchema, createPageSchema, updatePageSchema, pageIdSchema, contactMessageSchema } from "./pages.schema";

const router = Router();

// Public
router.get("/p/:slug", validate(pageSlugSchema), asyncHandler(getBySlug));
router.post("/contact", validate(contactMessageSchema), asyncHandler(submitContact));

// Admin
router.get("/", authMiddleware, requirePermission("policies.view"), asyncHandler(getAll));
router.post("/", authMiddleware, requirePermission("policies.manage"), validate(createPageSchema), asyncHandler(create));
router.put("/:id", authMiddleware, requirePermission("policies.manage"), validate(updatePageSchema), asyncHandler(update));
router.delete("/:id", authMiddleware, requirePermission("policies.manage"), validate(pageIdSchema), asyncHandler(remove));
router.get("/contact", authMiddleware, requirePermission("policies.view"), asyncHandler(getContactMessages));
router.patch("/contact/:id", authMiddleware, requirePermission("policies.manage"), asyncHandler(updateContactStatus));

export default router;