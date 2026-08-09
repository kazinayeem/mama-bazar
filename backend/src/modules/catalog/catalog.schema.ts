import { z } from "zod";

const idParam = z.object({ params: z.object({ id: z.string() }) });

const statusField = z.enum(["active", "inactive", "archived"]).optional();

const baseFields = {
  name: z.string().min(1, "Name is required"),
  status: statusField,
  sortOrder: z.union([z.string(), z.number()]).optional(),
};

const listQuery = z.object({
  query: z.object({
    page: z.string().optional(),
    limit: z.string().optional(),
    search: z.string().optional(),
    status: z.string().optional(),
    sort: z.string().optional(),
  }),
});

const moveBody = z.object({
  body: z.object({
    targetId: z.union([z.string(), z.number(), z.null()]),
  }),
});

// ==================== COLORS ====================
export const colorCreateSchema = z.object({
  body: z.object({
    ...baseFields,
    displayName: z.string().optional(),
    hex: z.string().regex(/^#[0-9a-fA-F]{6}$/, "Hex must be like #RRGGBB"),
  }),
});

export const colorUpdateSchema = z.object({
  ...idParam.shape,
  body: z.object({
    name: z.string().min(1).optional(),
    displayName: z.string().optional(),
    hex: z.string().regex(/^#[0-9a-fA-F]{6}$/, "Hex must be like #RRGGBB").optional(),
    status: statusField,
    sortOrder: z.union([z.string(), z.number()]).optional(),
  }),
});

export const colorListSchema = listQuery;
export const colorMoveSchema = moveBody;

// ==================== SIZES ====================
export const sizeCreateSchema = z.object({
  body: z.object({
    ...baseFields,
    type: z.enum(["clothing", "shoes", "general", "custom"]).optional(),
  }),
});

export const sizeUpdateSchema = z.object({
  ...idParam.shape,
  body: z.object({
    name: z.string().min(1).optional(),
    type: z.enum(["clothing", "shoes", "general", "custom"]).optional(),
    status: statusField,
    sortOrder: z.union([z.string(), z.number()]).optional(),
  }),
});

export const sizeListSchema = listQuery;
export const sizeMoveSchema = moveBody;

// ==================== COLLECTIONS ====================
const collectionFields = {
  name: z.string().min(1, "Name is required"),
  slug: z.string().optional(),
  description: z.string().optional(),
  image: z.string().optional(),
  banner: z.string().optional(),
  featured: z.union([z.boolean(), z.string()]).optional(),
  homepageVisibility: z.union([z.boolean(), z.string()]).optional(),
  sortOrder: z.union([z.string(), z.number()]).optional(),
  startDate: z.union([z.string(), z.null()]).optional(),
  endDate: z.union([z.string(), z.null()]).optional(),
  status: statusField,
};

export const collectionCreateSchema = z.object({
  body: z.object(collectionFields),
});

export const collectionUpdateSchema = z.object({
  ...idParam.shape,
  body: z.object({
    ...collectionFields,
    name: z.string().min(1).optional(),
  }),
});

export const collectionListSchema = listQuery;
export const collectionMoveSchema = moveBody;

// ==================== VENDORS / SUPPLIERS ====================
const vendorSupplierFields = {
  name: z.string().min(1, "Name is required"),
  slug: z.string().optional(),
  description: z.string().optional(),
  contact: z.string().optional(),
  phone: z.string().optional(),
  email: z.string().email("Invalid email").optional(),
  address: z.string().optional(),
  notes: z.string().optional(),
  status: statusField,
};

export const vendorCreateSchema = z.object({
  body: z.object({
    ...vendorSupplierFields,
    logo: z.string().optional(),
  }),
});

export const vendorUpdateSchema = z.object({
  ...idParam.shape,
  body: z.object({
    ...vendorSupplierFields,
    name: z.string().min(1).optional(),
    logo: z.string().optional(),
  }),
});

export const supplierCreateSchema = z.object({
  body: z.object({
    ...vendorSupplierFields,
    logo: z.string().optional(),
  }),
});

export const supplierUpdateSchema = z.object({
  ...idParam.shape,
  body: z.object({
    ...vendorSupplierFields,
    name: z.string().min(1).optional(),
    logo: z.string().optional(),
  }),
});

export const vendorListSchema = listQuery;
export const supplierListSchema = listQuery;
export const vendorMoveSchema = moveBody;
export const supplierMoveSchema = moveBody;

export const idParamSchema = z.object({ params: z.object({ id: z.string() }) });
