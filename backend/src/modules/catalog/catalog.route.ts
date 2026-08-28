import { Router, RequestHandler, Request, Response, NextFunction } from "express";
import {
  colorController,
  sizeController,
  collectionController,
  vendorController,
  supplierController,
} from "./catalog.controller";
import { uploadMemory } from "../../middleware/uploadMemory";
import { authMiddleware, requirePermission } from "../../middleware/auth";
import { asyncHandler } from "../../middleware/asyncHandler";
import { validate } from "../../middleware/validate";
import {
  colorCreateSchema,
  colorUpdateSchema,
  colorListSchema,
  colorMoveSchema,
  sizeCreateSchema,
  sizeUpdateSchema,
  sizeListSchema,
  sizeMoveSchema,
  collectionCreateSchema,
  collectionUpdateSchema,
  collectionListSchema,
  collectionMoveSchema,
  vendorCreateSchema,
  vendorUpdateSchema,
  vendorListSchema,
  vendorMoveSchema,
  supplierCreateSchema,
  supplierUpdateSchema,
  supplierListSchema,
  supplierMoveSchema,
  idParamSchema,
} from "./catalog.schema";

type CatalogFn = (req: Request, res: Response, next: NextFunction) => Promise<any>;

interface CatalogController {
  list: CatalogFn;
  listAdmin: CatalogFn;
  getById: CatalogFn;
  getUsage: CatalogFn;
  create: CatalogFn;
  update: CatalogFn;
  remove: CatalogFn;
  moveProducts: CatalogFn;
}

interface CatalogSchemas {
  create: unknown;
  update: unknown;
  list: unknown;
  move: unknown;
}

const makeCrudRoutes = (
  moduleName: string,
  controller: CatalogController,
  schemas: CatalogSchemas,
  withImage: boolean
): Router => {
  const router = Router();
  const upload = withImage ? (uploadMemory.single("image") as RequestHandler) : undefined;
  const uploadMw = upload ? [upload] : [];

  router.get("/", asyncHandler(controller.list));

  // Admin (before /:id to avoid param capture)
  router.get("/admin", authMiddleware, requirePermission(`${moduleName}.view`), validate(schemas.list as never), asyncHandler(controller.listAdmin));
  router.get("/:id/usage", authMiddleware, requirePermission(`${moduleName}.view`), validate(idParamSchema as never), asyncHandler(controller.getUsage));
  router.post("/:id/move", authMiddleware, requirePermission(`${moduleName}.update`), validate(schemas.move as never), asyncHandler(controller.moveProducts));

  router.get("/:id", validate(idParamSchema as never), asyncHandler(controller.getById));
  router.post("/", authMiddleware, requirePermission(`${moduleName}.create`), ...uploadMw, validate(schemas.create as never), asyncHandler(controller.create));
  router.put("/:id", authMiddleware, requirePermission(`${moduleName}.update`), ...uploadMw, validate(schemas.update as never), asyncHandler(controller.update));
  router.delete("/:id", authMiddleware, requirePermission(`${moduleName}.delete`), validate(idParamSchema as never), asyncHandler(controller.remove));

  return router;
};

export const colorsRouter = makeCrudRoutes(
  "colors",
  colorController,
  { create: colorCreateSchema, update: colorUpdateSchema, list: colorListSchema, move: colorMoveSchema },
  false
);
export const sizesRouter = makeCrudRoutes(
  "sizes",
  sizeController,
  { create: sizeCreateSchema, update: sizeUpdateSchema, list: sizeListSchema, move: sizeMoveSchema },
  false
);
export const collectionsRouter = makeCrudRoutes(
  "collections",
  collectionController,
  { create: collectionCreateSchema, update: collectionUpdateSchema, list: collectionListSchema, move: collectionMoveSchema },
  true
);
export const vendorsRouter = makeCrudRoutes(
  "vendors",
  vendorController,
  { create: vendorCreateSchema, update: vendorUpdateSchema, list: vendorListSchema, move: vendorMoveSchema },
  true
);
export const suppliersRouter = makeCrudRoutes(
  "suppliers",
  supplierController,
  { create: supplierCreateSchema, update: supplierUpdateSchema, list: supplierListSchema, move: supplierMoveSchema },
  true
);
