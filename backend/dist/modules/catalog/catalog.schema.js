"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.idParamSchema = exports.supplierMoveSchema = exports.vendorMoveSchema = exports.supplierListSchema = exports.vendorListSchema = exports.supplierUpdateSchema = exports.supplierCreateSchema = exports.vendorUpdateSchema = exports.vendorCreateSchema = exports.collectionMoveSchema = exports.collectionListSchema = exports.collectionUpdateSchema = exports.collectionCreateSchema = exports.sizeMoveSchema = exports.sizeListSchema = exports.sizeUpdateSchema = exports.sizeCreateSchema = exports.colorMoveSchema = exports.colorListSchema = exports.colorUpdateSchema = exports.colorCreateSchema = void 0;
const zod_1 = require("zod");
const idParam = zod_1.z.object({ params: zod_1.z.object({ id: zod_1.z.string() }) });
const statusField = zod_1.z.enum(["active", "inactive", "archived"]).optional();
const baseFields = {
    name: zod_1.z.string().min(1, "Name is required"),
    status: statusField,
    sortOrder: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
};
const listQuery = zod_1.z.object({
    query: zod_1.z.object({
        page: zod_1.z.string().optional(),
        limit: zod_1.z.string().optional(),
        search: zod_1.z.string().optional(),
        status: zod_1.z.string().optional(),
        sort: zod_1.z.string().optional(),
    }),
});
const moveBody = zod_1.z.object({
    body: zod_1.z.object({
        targetId: zod_1.z.union([zod_1.z.string(), zod_1.z.number(), zod_1.z.null()]),
    }),
});
// ==================== COLORS ====================
exports.colorCreateSchema = zod_1.z.object({
    body: zod_1.z.object({
        ...baseFields,
        displayName: zod_1.z.string().optional(),
        hex: zod_1.z.string().regex(/^#[0-9a-fA-F]{6}$/, "Hex must be like #RRGGBB"),
    }),
});
exports.colorUpdateSchema = zod_1.z.object({
    ...idParam.shape,
    body: zod_1.z.object({
        name: zod_1.z.string().min(1).optional(),
        displayName: zod_1.z.string().optional(),
        hex: zod_1.z.string().regex(/^#[0-9a-fA-F]{6}$/, "Hex must be like #RRGGBB").optional(),
        status: statusField,
        sortOrder: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    }),
});
exports.colorListSchema = listQuery;
exports.colorMoveSchema = moveBody;
// ==================== SIZES ====================
exports.sizeCreateSchema = zod_1.z.object({
    body: zod_1.z.object({
        ...baseFields,
        type: zod_1.z.enum(["clothing", "shoes", "general", "custom"]).optional(),
    }),
});
exports.sizeUpdateSchema = zod_1.z.object({
    ...idParam.shape,
    body: zod_1.z.object({
        name: zod_1.z.string().min(1).optional(),
        type: zod_1.z.enum(["clothing", "shoes", "general", "custom"]).optional(),
        status: statusField,
        sortOrder: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    }),
});
exports.sizeListSchema = listQuery;
exports.sizeMoveSchema = moveBody;
// ==================== COLLECTIONS ====================
const collectionFields = {
    name: zod_1.z.string().min(1, "Name is required"),
    slug: zod_1.z.string().optional(),
    description: zod_1.z.string().optional(),
    image: zod_1.z.string().optional(),
    banner: zod_1.z.string().optional(),
    featured: zod_1.z.union([zod_1.z.boolean(), zod_1.z.string()]).optional(),
    homepageVisibility: zod_1.z.union([zod_1.z.boolean(), zod_1.z.string()]).optional(),
    sortOrder: zod_1.z.union([zod_1.z.string(), zod_1.z.number()]).optional(),
    startDate: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    endDate: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    status: statusField,
};
exports.collectionCreateSchema = zod_1.z.object({
    body: zod_1.z.object(collectionFields),
});
exports.collectionUpdateSchema = zod_1.z.object({
    ...idParam.shape,
    body: zod_1.z.object({
        ...collectionFields,
        name: zod_1.z.string().min(1).optional(),
    }),
});
exports.collectionListSchema = listQuery;
exports.collectionMoveSchema = moveBody;
// ==================== VENDORS / SUPPLIERS ====================
const vendorSupplierFields = {
    name: zod_1.z.string().min(1, "Name is required"),
    slug: zod_1.z.string().optional(),
    description: zod_1.z.string().optional(),
    contact: zod_1.z.string().optional(),
    phone: zod_1.z.string().optional(),
    email: zod_1.z.string().email("Invalid email").optional(),
    address: zod_1.z.string().optional(),
    notes: zod_1.z.string().optional(),
    status: statusField,
};
exports.vendorCreateSchema = zod_1.z.object({
    body: zod_1.z.object({
        ...vendorSupplierFields,
        logo: zod_1.z.string().optional(),
    }),
});
exports.vendorUpdateSchema = zod_1.z.object({
    ...idParam.shape,
    body: zod_1.z.object({
        ...vendorSupplierFields,
        name: zod_1.z.string().min(1).optional(),
        logo: zod_1.z.string().optional(),
    }),
});
exports.supplierCreateSchema = zod_1.z.object({
    body: zod_1.z.object({
        ...vendorSupplierFields,
        logo: zod_1.z.string().optional(),
    }),
});
exports.supplierUpdateSchema = zod_1.z.object({
    ...idParam.shape,
    body: zod_1.z.object({
        ...vendorSupplierFields,
        name: zod_1.z.string().min(1).optional(),
        logo: zod_1.z.string().optional(),
    }),
});
exports.vendorListSchema = listQuery;
exports.supplierListSchema = listQuery;
exports.vendorMoveSchema = moveBody;
exports.supplierMoveSchema = moveBody;
exports.idParamSchema = zod_1.z.object({ params: zod_1.z.object({ id: zod_1.z.string() }) });
//# sourceMappingURL=catalog.schema.js.map