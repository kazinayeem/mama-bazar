import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react'
import { authStorage } from '../../lib/authStorage'
import { API_BASE_URL } from '../../lib/apiConfig'
import type {
  AdminListResult,
  AdminProduct,
  AdminProductFilters,
  AdminProductListResult,
  Brand,
  Collection,
  Color,
  MediaAsset,
  ProductBulkAction,
  ProductInput,
  Size,
  Supplier,
  Vendor,
} from '../../types/admin'
import type { Category, ShippingMethod } from '../../types'



type Envelope<T> = {
  success: boolean
  data?: T
  pagination?: { page: number; limit: number; total: number; totalPages: number }
  message?: string
}

const toQueryString = (params?: Record<string, string | number | boolean | undefined>) => {
  const query = new URLSearchParams()
  if (!params) return ''
  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return
    query.set(key, String(value))
  })
  const serialized = query.toString()
  return serialized ? `?${serialized}` : ''
}

const parseError = (err: unknown): string => {
  if (err && typeof err === 'object' && 'data' in err) {
    const data = (err as { data?: { message?: string } }).data
    if (data?.message) return data.message
  }
  if (err instanceof Error) return err.message
  return 'Request failed'
}

export const adminProductsApi = createApi({
  reducerPath: 'adminProductsApi',
  baseQuery: fetchBaseQuery({
    baseUrl: API_BASE_URL,
    prepareHeaders: (headers) => {
      const token = authStorage.getToken()
      if (token) headers.set('Authorization', `Bearer ${token}`)
      return headers
    },
  }),
  tagTypes: ['Products', 'Product', 'Categories', 'Brands', 'Collections', 'Colors', 'Sizes', 'Vendors', 'Suppliers', 'Media', 'Shipping'],
  endpoints: (builder) => ({
    // ==================== Products ====================
    getAdminProducts: builder.query<AdminProductListResult, AdminProductFilters | void>({
      query: (filters) => `/api/products${toQueryString(filters as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<AdminProduct[]>) => ({
        data: response.data || [],
        total: response.pagination?.total || 0,
        page: response.pagination?.page || 1,
        limit: response.pagination?.limit || 20,
        totalPages: response.pagination?.totalPages || 1,
      }),
      providesTags: (result) =>
        result
          ? [
              ...result.data.map((p) => ({ type: 'Product' as const, id: p.id })),
              { type: 'Products' as const, id: 'LIST' },
            ]
          : [{ type: 'Products' as const, id: 'LIST' }],
    }),

    getAdminProductById: builder.query<AdminProduct, number>({
      query: (id) => `/api/products/${id}`,
      transformResponse: (response: Envelope<AdminProduct>) => {
        if (!response.data) throw new Error(response.message || 'Product not found')
        return response.data
      },
      providesTags: (_result, _error, id) => [{ type: 'Product', id }],
    }),

    createProduct: builder.mutation<AdminProduct, ProductInput>({
      query: (payload) => ({ url: '/api/products', method: 'POST', body: payload }),
      transformResponse: (response: Envelope<AdminProduct>) => response.data!,
      invalidatesTags: ['Products', 'Product'],
    }),

    updateProduct: builder.mutation<AdminProduct, { id: number; payload: Partial<ProductInput> }>({
      query: ({ id, payload }) => ({ url: `/api/products/${id}`, method: 'PUT', body: payload }),
      transformResponse: (response: Envelope<AdminProduct>) => response.data!,
      invalidatesTags: (_result, _error, { id }) => [
        { type: 'Product', id },
        { type: 'Products', id: 'LIST' },
      ],
    }),

    saveProductDraft: builder.mutation<{ success: boolean }, { id: number; draft: Record<string, unknown> }>({
      query: ({ id, draft }) => ({ url: `/api/products/${id}/draft`, method: 'POST', body: { draft } }),
      invalidatesTags: (_result, _error, { id }) => [{ type: 'Product', id }],
    }),

    deleteProduct: builder.mutation<{ success: boolean }, number>({
      query: (id) => ({ url: `/api/products/${id}`, method: 'DELETE' }),
      invalidatesTags: ['Products', 'Product'],
    }),

    bulkProductAction: builder.mutation<{ affected: number }, { action: ProductBulkAction; ids: number[] }>({
      query: ({ action, ids }) => ({ url: '/api/products/bulk', method: 'POST', body: { action, ids } }),
      transformResponse: (response: Envelope<{ affected?: number }>) => ({ affected: response.data?.affected ?? 0 }),
      invalidatesTags: ['Products', 'Product'],
    }),

    duplicateProduct: builder.mutation<AdminProduct, number>({
      query: (id) => ({ url: `/api/products/${id}/duplicate`, method: 'POST' }),
      transformResponse: (response: Envelope<AdminProduct>) => response.data!,
      invalidatesTags: ['Products', 'Product'],
    }),

    exportProductsCsv: builder.mutation<string, Partial<AdminProductFilters>>({
      query: (filters) => `/api/products/export/csv${toQueryString(filters as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<{ csv?: string }>) => response.data?.csv || '',
    }),

    importProductsCsv: builder.mutation<{ imported: number }, string>({
      query: (csv) => ({ url: '/api/products/import/csv', method: 'POST', body: { csv } }),
      transformResponse: (response: Envelope<{ imported?: number }>) => ({ imported: response.data?.imported ?? 0 }),
      invalidatesTags: ['Products'],
    }),

    // ==================== Reference data ====================
    getAdminCategories: builder.query<Category[], void>({
      query: () => '/api/categories',
      transformResponse: (response: Envelope<Category[]>) => response.data || [],
      providesTags: [{ type: 'Categories', id: 'LIST' }],
    }),

    getAdminBrands: builder.query<Brand[], void>({
      query: () => '/api/brands',
      transformResponse: (response: Envelope<Brand[]>) => response.data || [],
      providesTags: [{ type: 'Brands', id: 'LIST' }],
    }),

    getAdminCollections: builder.query<Collection[], void>({
      query: () => '/api/collections',
      transformResponse: (response: Envelope<Collection[]>) => response.data || [],
      providesTags: [{ type: 'Collections', id: 'LIST' }],
    }),

    getAdminColors: builder.query<Color[], void>({
      query: () => '/api/colors',
      transformResponse: (response: Envelope<Color[]>) => response.data || [],
      providesTags: [{ type: 'Colors', id: 'LIST' }],
    }),

    getAdminSizes: builder.query<Size[], void>({
      query: () => '/api/sizes',
      transformResponse: (response: Envelope<Size[]>) => response.data || [],
      providesTags: [{ type: 'Sizes', id: 'LIST' }],
    }),

    getAdminVendors: builder.query<Vendor[], void>({
      query: () => '/api/vendors',
      transformResponse: (response: Envelope<Vendor[]>) => response.data || [],
      providesTags: [{ type: 'Vendors', id: 'LIST' }],
    }),

    getAdminSuppliers: builder.query<Supplier[], void>({
      query: () => '/api/suppliers',
      transformResponse: (response: Envelope<Supplier[]>) => response.data || [],
      providesTags: [{ type: 'Suppliers', id: 'LIST' }],
    }),

    // ==================== Master data mutations ====================
    getAdminCategoriesAdmin: builder.query<AdminListResult<Category>, Record<string, string | number | boolean | undefined> | void>({
      query: (params) => `/api/categories/admin${toQueryString(params as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<Category[]>) => ({
        data: response.data || [],
        total: response.pagination?.total || 0,
        page: response.pagination?.page || 1,
        limit: response.pagination?.limit || 20,
        totalPages: response.pagination?.totalPages || 1,
      }),
      providesTags: [{ type: 'Categories', id: 'LIST' }],
    }),

    createCategory: builder.mutation<Category, Record<string, unknown>>({
      query: (payload) => ({ url: '/api/categories', method: 'POST', body: payload }),
      transformResponse: (response: Envelope<Category>) => response.data!,
      invalidatesTags: [{ type: 'Categories', id: 'LIST' }],
    }),

    updateCategory: builder.mutation<Category, { id: number; payload: Record<string, unknown> }>({
      query: ({ id, payload }) => ({ url: `/api/categories/${id}`, method: 'PUT', body: payload }),
      transformResponse: (response: Envelope<Category>) => response.data!,
      invalidatesTags: [{ type: 'Categories', id: 'LIST' }],
    }),

    deleteCategory: builder.mutation<{ success: boolean }, number>({
      query: (id) => ({ url: `/api/categories/${id}`, method: 'DELETE' }),
      invalidatesTags: [{ type: 'Categories', id: 'LIST' }],
    }),

    moveCategoryProducts: builder.mutation<{ moved: number }, { id: number; targetId: number | null }>({
      query: ({ id, targetId }) => ({ url: `/api/categories/${id}/move`, method: 'POST', body: { targetId } }),
      invalidatesTags: [{ type: 'Categories', id: 'LIST' }],
    }),

    getAdminBrandsAdmin: builder.query<AdminListResult<Brand>, Record<string, string | number | boolean | undefined> | void>({
      query: (params) => `/api/brands/admin${toQueryString(params as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<Brand[]>) => ({
        data: response.data || [],
        total: response.pagination?.total || 0,
        page: response.pagination?.page || 1,
        limit: response.pagination?.limit || 20,
        totalPages: response.pagination?.totalPages || 1,
      }),
      providesTags: [{ type: 'Brands', id: 'LIST' }],
    }),

    createBrand: builder.mutation<Brand, Record<string, unknown>>({
      query: (payload) => ({ url: '/api/brands', method: 'POST', body: payload }),
      transformResponse: (response: Envelope<Brand>) => response.data!,
      invalidatesTags: [{ type: 'Brands', id: 'LIST' }],
    }),

    updateBrand: builder.mutation<Brand, { id: number; payload: Record<string, unknown> }>({
      query: ({ id, payload }) => ({ url: `/api/brands/${id}`, method: 'PUT', body: payload }),
      transformResponse: (response: Envelope<Brand>) => response.data!,
      invalidatesTags: [{ type: 'Brands', id: 'LIST' }],
    }),

    deleteBrand: builder.mutation<{ success: boolean }, number>({
      query: (id) => ({ url: `/api/brands/${id}`, method: 'DELETE' }),
      invalidatesTags: [{ type: 'Brands', id: 'LIST' }],
    }),

    moveBrandProducts: builder.mutation<{ moved: number }, { id: number; targetId: number | null }>({
      query: ({ id, targetId }) => ({ url: `/api/brands/${id}/move`, method: 'POST', body: { targetId } }),
      invalidatesTags: [{ type: 'Brands', id: 'LIST' }],
    }),

    getAdminCollectionsAdmin: builder.query<AdminListResult<Collection>, Record<string, string | number | boolean | undefined> | void>({
      query: (params) => `/api/collections/admin${toQueryString(params as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<Collection[]>) => ({
        data: response.data || [],
        total: response.pagination?.total || 0,
        page: response.pagination?.page || 1,
        limit: response.pagination?.limit || 20,
        totalPages: response.pagination?.totalPages || 1,
      }),
      providesTags: [{ type: 'Collections', id: 'LIST' }],
    }),

    createCollection: builder.mutation<Collection, Record<string, unknown>>({
      query: (payload) => ({ url: '/api/collections', method: 'POST', body: payload }),
      transformResponse: (response: Envelope<Collection>) => response.data!,
      invalidatesTags: [{ type: 'Collections', id: 'LIST' }],
    }),

    updateCollection: builder.mutation<Collection, { id: number; payload: Record<string, unknown> }>({
      query: ({ id, payload }) => ({ url: `/api/collections/${id}`, method: 'PUT', body: payload }),
      transformResponse: (response: Envelope<Collection>) => response.data!,
      invalidatesTags: [{ type: 'Collections', id: 'LIST' }],
    }),

    deleteCollection: builder.mutation<{ success: boolean }, number>({
      query: (id) => ({ url: `/api/collections/${id}`, method: 'DELETE' }),
      invalidatesTags: [{ type: 'Collections', id: 'LIST' }],
    }),

    moveCollectionProducts: builder.mutation<{ moved: number }, { id: number; targetId: number | null }>({
      query: ({ id, targetId }) => ({ url: `/api/collections/${id}/move`, method: 'POST', body: { targetId } }),
      invalidatesTags: [{ type: 'Collections', id: 'LIST' }],
    }),

    getAdminColorsAdmin: builder.query<AdminListResult<Color>, Record<string, string | number | boolean | undefined> | void>({
      query: (params) => `/api/colors/admin${toQueryString(params as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<Color[]>) => ({
        data: response.data || [],
        total: response.pagination?.total || 0,
        page: response.pagination?.page || 1,
        limit: response.pagination?.limit || 20,
        totalPages: response.pagination?.totalPages || 1,
      }),
      providesTags: [{ type: 'Colors', id: 'LIST' }],
    }),

    createColor: builder.mutation<Color, Record<string, unknown>>({
      query: (payload) => ({ url: '/api/colors', method: 'POST', body: payload }),
      transformResponse: (response: Envelope<Color>) => response.data!,
      invalidatesTags: [{ type: 'Colors', id: 'LIST' }],
    }),

    updateColor: builder.mutation<Color, { id: number; payload: Record<string, unknown> }>({
      query: ({ id, payload }) => ({ url: `/api/colors/${id}`, method: 'PUT', body: payload }),
      transformResponse: (response: Envelope<Color>) => response.data!,
      invalidatesTags: [{ type: 'Colors', id: 'LIST' }],
    }),

    deleteColor: builder.mutation<{ success: boolean }, number>({
      query: (id) => ({ url: `/api/colors/${id}`, method: 'DELETE' }),
      invalidatesTags: [{ type: 'Colors', id: 'LIST' }],
    }),

    moveColorProducts: builder.mutation<{ moved: number }, { id: number; targetId: number | null }>({
      query: ({ id, targetId }) => ({ url: `/api/colors/${id}/move`, method: 'POST', body: { targetId } }),
      invalidatesTags: [{ type: 'Colors', id: 'LIST' }],
    }),

    getAdminSizesAdmin: builder.query<AdminListResult<Size>, Record<string, string | number | boolean | undefined> | void>({
      query: (params) => `/api/sizes/admin${toQueryString(params as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<Size[]>) => ({
        data: response.data || [],
        total: response.pagination?.total || 0,
        page: response.pagination?.page || 1,
        limit: response.pagination?.limit || 20,
        totalPages: response.pagination?.totalPages || 1,
      }),
      providesTags: [{ type: 'Sizes', id: 'LIST' }],
    }),

    createSize: builder.mutation<Size, Record<string, unknown>>({
      query: (payload) => ({ url: '/api/sizes', method: 'POST', body: payload }),
      transformResponse: (response: Envelope<Size>) => response.data!,
      invalidatesTags: [{ type: 'Sizes', id: 'LIST' }],
    }),

    updateSize: builder.mutation<Size, { id: number; payload: Record<string, unknown> }>({
      query: ({ id, payload }) => ({ url: `/api/sizes/${id}`, method: 'PUT', body: payload }),
      transformResponse: (response: Envelope<Size>) => response.data!,
      invalidatesTags: [{ type: 'Sizes', id: 'LIST' }],
    }),

    deleteSize: builder.mutation<{ success: boolean }, number>({
      query: (id) => ({ url: `/api/sizes/${id}`, method: 'DELETE' }),
      invalidatesTags: [{ type: 'Sizes', id: 'LIST' }],
    }),

    moveSizeProducts: builder.mutation<{ moved: number }, { id: number; targetId: number | null }>({
      query: ({ id, targetId }) => ({ url: `/api/sizes/${id}/move`, method: 'POST', body: { targetId } }),
      invalidatesTags: [{ type: 'Sizes', id: 'LIST' }],
    }),

    getAdminVendorsAdmin: builder.query<AdminListResult<Vendor>, Record<string, string | number | boolean | undefined> | void>({
      query: (params) => `/api/vendors/admin${toQueryString(params as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<Vendor[]>) => ({
        data: response.data || [],
        total: response.pagination?.total || 0,
        page: response.pagination?.page || 1,
        limit: response.pagination?.limit || 20,
        totalPages: response.pagination?.totalPages || 1,
      }),
      providesTags: [{ type: 'Vendors', id: 'LIST' }],
    }),

    createVendor: builder.mutation<Vendor, Record<string, unknown>>({
      query: (payload) => ({ url: '/api/vendors', method: 'POST', body: payload }),
      transformResponse: (response: Envelope<Vendor>) => response.data!,
      invalidatesTags: [{ type: 'Vendors', id: 'LIST' }],
    }),

    updateVendor: builder.mutation<Vendor, { id: number; payload: Record<string, unknown> }>({
      query: ({ id, payload }) => ({ url: `/api/vendors/${id}`, method: 'PUT', body: payload }),
      transformResponse: (response: Envelope<Vendor>) => response.data!,
      invalidatesTags: [{ type: 'Vendors', id: 'LIST' }],
    }),

    deleteVendor: builder.mutation<{ success: boolean }, number>({
      query: (id) => ({ url: `/api/vendors/${id}`, method: 'DELETE' }),
      invalidatesTags: [{ type: 'Vendors', id: 'LIST' }],
    }),

    moveVendorProducts: builder.mutation<{ moved: number }, { id: number; targetId: number | null }>({
      query: ({ id, targetId }) => ({ url: `/api/vendors/${id}/move`, method: 'POST', body: { targetId } }),
      invalidatesTags: [{ type: 'Vendors', id: 'LIST' }],
    }),

    getAdminSuppliersAdmin: builder.query<AdminListResult<Supplier>, Record<string, string | number | boolean | undefined> | void>({
      query: (params) => `/api/suppliers/admin${toQueryString(params as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<Supplier[]>) => ({
        data: response.data || [],
        total: response.pagination?.total || 0,
        page: response.pagination?.page || 1,
        limit: response.pagination?.limit || 20,
        totalPages: response.pagination?.totalPages || 1,
      }),
      providesTags: [{ type: 'Suppliers', id: 'LIST' }],
    }),

    createSupplier: builder.mutation<Supplier, Record<string, unknown>>({
      query: (payload) => ({ url: '/api/suppliers', method: 'POST', body: payload }),
      transformResponse: (response: Envelope<Supplier>) => response.data!,
      invalidatesTags: [{ type: 'Suppliers', id: 'LIST' }],
    }),

    updateSupplier: builder.mutation<Supplier, { id: number; payload: Record<string, unknown> }>({
      query: ({ id, payload }) => ({ url: `/api/suppliers/${id}`, method: 'PUT', body: payload }),
      transformResponse: (response: Envelope<Supplier>) => response.data!,
      invalidatesTags: [{ type: 'Suppliers', id: 'LIST' }],
    }),

    deleteSupplier: builder.mutation<{ success: boolean }, number>({
      query: (id) => ({ url: `/api/suppliers/${id}`, method: 'DELETE' }),
      invalidatesTags: [{ type: 'Suppliers', id: 'LIST' }],
    }),

    moveSupplierProducts: builder.mutation<{ moved: number }, { id: number; targetId: number | null }>({
      query: ({ id, targetId }) => ({ url: `/api/suppliers/${id}/move`, method: 'POST', body: { targetId } }),
      invalidatesTags: [{ type: 'Suppliers', id: 'LIST' }],
    }),

    getAdminShippingMethods: builder.query<ShippingMethod[], void>({
      query: () => '/api/shipping-methods',
      transformResponse: (response: Envelope<ShippingMethod[]>) => response.data || [],
      providesTags: [{ type: 'Shipping', id: 'LIST' }],
    }),

    // ==================== Media ====================
    getAdminMedia: builder.query<{ data: MediaAsset[]; totalPages: number }, { page?: number; limit?: number; folder?: string; search?: string } | void>({
      query: (params) => `/api/media${toQueryString(params as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<MediaAsset[]>) => ({
        data: response.data || [],
        totalPages: response.pagination?.totalPages || 1,
      }),
      providesTags: [{ type: 'Media', id: 'LIST' }],
    }),

    getAdminMediaFolders: builder.query<Array<{ name: string; count: number }>, void>({
      query: () => '/api/media/folders',
      transformResponse: (response: Envelope<Array<{ name: string; count: number }>>) => response.data || [],
    }),

    uploadMedia: builder.mutation<MediaAsset[], { files: File[]; folder?: string }>({
      query: ({ files, folder = 'products' }) => {
        const formData = new FormData()
        formData.append('folder', folder)
        if (files.length === 1) {
          formData.append('file', files[0])
          return { url: '/api/media/upload', method: 'POST', body: formData }
        }
        files.forEach((f) => formData.append('files', f))
        return { url: '/api/media/upload/multiple', method: 'POST', body: formData }
      },
      transformResponse: (response: Envelope<MediaAsset | MediaAsset[]>) => {
        const data = response.data
        return Array.isArray(data) ? data : data ? [data] : []
      },
      invalidatesTags: [{ type: 'Media', id: 'LIST' }],
    }),

    deleteMedia: builder.mutation<{ success: boolean }, number>({
      query: (id) => ({ url: `/api/media/${id}`, method: 'DELETE' }),
      invalidatesTags: [{ type: 'Media', id: 'LIST' }],
    }),
  }),
})

export const {
  useGetAdminProductsQuery,
  useGetAdminProductByIdQuery,
  useCreateProductMutation,
  useUpdateProductMutation,
  useSaveProductDraftMutation,
  useDeleteProductMutation,
  useBulkProductActionMutation,
  useDuplicateProductMutation,
  useExportProductsCsvMutation,
  useImportProductsCsvMutation,
  useGetAdminCategoriesQuery,
  useGetAdminBrandsQuery,
  useGetAdminCollectionsQuery,
  useGetAdminColorsQuery,
  useGetAdminSizesQuery,
  useGetAdminVendorsQuery,
  useGetAdminSuppliersQuery,
  useGetAdminCategoriesAdminQuery,
  useGetAdminBrandsAdminQuery,
  useGetAdminCollectionsAdminQuery,
  useGetAdminColorsAdminQuery,
  useGetAdminSizesAdminQuery,
  useGetAdminVendorsAdminQuery,
  useGetAdminSuppliersAdminQuery,
  useLazyGetAdminCategoriesAdminQuery,
  useLazyGetAdminBrandsAdminQuery,
  useLazyGetAdminCollectionsAdminQuery,
  useLazyGetAdminColorsAdminQuery,
  useLazyGetAdminSizesAdminQuery,
  useLazyGetAdminVendorsAdminQuery,
  useLazyGetAdminSuppliersAdminQuery,
  useCreateCategoryMutation,
  useUpdateCategoryMutation,
  useDeleteCategoryMutation,
  useMoveCategoryProductsMutation,
  useCreateBrandMutation,
  useUpdateBrandMutation,
  useDeleteBrandMutation,
  useMoveBrandProductsMutation,
  useCreateCollectionMutation,
  useUpdateCollectionMutation,
  useDeleteCollectionMutation,
  useMoveCollectionProductsMutation,
  useCreateColorMutation,
  useUpdateColorMutation,
  useDeleteColorMutation,
  useMoveColorProductsMutation,
  useCreateSizeMutation,
  useUpdateSizeMutation,
  useDeleteSizeMutation,
  useMoveSizeProductsMutation,
  useCreateVendorMutation,
  useUpdateVendorMutation,
  useDeleteVendorMutation,
  useMoveVendorProductsMutation,
  useCreateSupplierMutation,
  useUpdateSupplierMutation,
  useDeleteSupplierMutation,
  useMoveSupplierProductsMutation,
  useGetAdminShippingMethodsQuery,
  useGetAdminMediaQuery,
  useGetAdminMediaFoldersQuery,
  useUploadMediaMutation,
  useDeleteMediaMutation,
} = adminProductsApi

export { parseError, toQueryString }
