"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.suppliersRouter = exports.vendorsRouter = exports.collectionsRouter = exports.sizesRouter = exports.colorsRouter = void 0;
const express_1 = require("express");
const catalog_controller_1 = require("./catalog.controller");
const uploadMemory_1 = require("../../middleware/uploadMemory");
const auth_1 = require("../../middleware/auth");
const asyncHandler_1 = require("../../middleware/asyncHandler");
const validate_1 = require("../../middleware/validate");
const catalog_schema_1 = require("./catalog.schema");
const makeCrudRoutes = (controller, schemas, withImage) => {
    const router = (0, express_1.Router)();
    const upload = withImage ? uploadMemory_1.uploadMemory.single("image") : undefined;
    const uploadMw = upload ? [upload] : [];
    router.get("/", (0, asyncHandler_1.asyncHandler)(controller.list));
    // Admin (before /:id to avoid param capture)
    router.get("/admin", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(schemas.list), (0, asyncHandler_1.asyncHandler)(controller.listAdmin));
    router.get("/:id/usage", auth_1.authMiddleware, (0, validate_1.validate)(catalog_schema_1.idParamSchema), (0, asyncHandler_1.asyncHandler)(controller.getUsage));
    router.post("/:id/move", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(schemas.move), (0, asyncHandler_1.asyncHandler)(controller.moveProducts));
    router.get("/:id", (0, validate_1.validate)(catalog_schema_1.idParamSchema), (0, asyncHandler_1.asyncHandler)(controller.getById));
    router.post("/", auth_1.authMiddleware, auth_1.adminOnly, ...uploadMw, (0, validate_1.validate)(schemas.create), (0, asyncHandler_1.asyncHandler)(controller.create));
    router.put("/:id", auth_1.authMiddleware, auth_1.adminOnly, ...uploadMw, (0, validate_1.validate)(schemas.update), (0, asyncHandler_1.asyncHandler)(controller.update));
    router.delete("/:id", auth_1.authMiddleware, auth_1.adminOnly, (0, validate_1.validate)(catalog_schema_1.idParamSchema), (0, asyncHandler_1.asyncHandler)(controller.remove));
    return router;
};
exports.colorsRouter = makeCrudRoutes(catalog_controller_1.colorController, { create: catalog_schema_1.colorCreateSchema, update: catalog_schema_1.colorUpdateSchema, list: catalog_schema_1.colorListSchema, move: catalog_schema_1.colorMoveSchema }, false);
exports.sizesRouter = makeCrudRoutes(catalog_controller_1.sizeController, { create: catalog_schema_1.sizeCreateSchema, update: catalog_schema_1.sizeUpdateSchema, list: catalog_schema_1.sizeListSchema, move: catalog_schema_1.sizeMoveSchema }, false);
exports.collectionsRouter = makeCrudRoutes(catalog_controller_1.collectionController, { create: catalog_schema_1.collectionCreateSchema, update: catalog_schema_1.collectionUpdateSchema, list: catalog_schema_1.collectionListSchema, move: catalog_schema_1.collectionMoveSchema }, true);
exports.vendorsRouter = makeCrudRoutes(catalog_controller_1.vendorController, { create: catalog_schema_1.vendorCreateSchema, update: catalog_schema_1.vendorUpdateSchema, list: catalog_schema_1.vendorListSchema, move: catalog_schema_1.vendorMoveSchema }, true);
exports.suppliersRouter = makeCrudRoutes(catalog_controller_1.supplierController, { create: catalog_schema_1.supplierCreateSchema, update: catalog_schema_1.supplierUpdateSchema, list: catalog_schema_1.supplierListSchema, move: catalog_schema_1.supplierMoveSchema }, true);
//# sourceMappingURL=catalog.route.js.map