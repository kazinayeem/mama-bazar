import type { Category, ShippingMethod, AdminCoupon, PolicyPage, ContactMessage } from '../../types'
import type {
  AdminCustomer,
  AdminOrder,
  AdminCheckoutNotice,
  AdminListResult,
  AdminPaymentMethod,
  AdminProduct,
  AdminProductFilters,
  AdminProductListResult,
  Banner,
  Brand,
  Collection,
  Color,
  DashboardData,
  MarketingIntegration,
  MediaAsset,
  ProductBulkAction,
  ProductInput,
  Size,
  Supplier,
  Vendor,
} from '../../types/admin'
import type {
  Expense,
  ExpenseCategory,
  ExpenseFilters,
  ExpenseInput,
  ExpenseListResult,
  ExpenseMonthlyReport,
  ExpenseRangeReport,
  ExpenseSummary,
  ExpenseTrendRow,
  ExpenseCategoryRow,
  ExpenseMemberRow,
  ProfitOverview,
  TeamMember,
} from '../../types/admin'
import type { HomepageConfig, NewsletterSubscriber } from '../../types/homepage'
import { baseApi } from './api'

export {
  useGetCategoriesQuery,
  useGetBrandsQuery,
  useGetCollectionsQuery,
  useGetSettingsQuery,
} from './commerceApi'

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

const asList = <T>(response: Envelope<T[]>, page = 1, limit = 20): AdminListResult<T> => ({
  data: response.data || [],
  total: response.pagination?.total || 0,
  page: response.pagination?.page || page,
  limit: response.pagination?.limit || limit,
  totalPages: response.pagination?.totalPages || 1,
})

// Admin-managed checkout configuration must invalidate BOTH the admin list and
// the public/user-facing cache so changes propagate instantly to the storefront.
const CHECKOUT_CONFIG_TAGS = [
  { type: 'Shipping' as const, id: 'LIST' },
  { type: 'Shipping' as const, id: 'PUBLIC' },
]
const PAYMENT_CONFIG_TAGS = [
  { type: 'PaymentMethods' as const, id: 'LIST' },
  { type: 'PaymentMethods' as const, id: 'PUBLIC' },
]
const NOTICE_CONFIG_TAGS = [
  { type: 'CheckoutNotices' as const, id: 'LIST' },
  { type: 'CheckoutNotices' as const, id: 'PUBLIC' },
]
const SETTINGS_TAGS = [
  { type: 'Settings' as const, id: 'LIST' },
  { type: 'Settings' as const, id: 'CONTACT' },
  { type: 'StoreInfo' as const, id: 'DETAIL' },
]

export const adminProductsApi = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    // ==================== Dashboard ====================
    getAdminDashboard: builder.query<DashboardData, string | void>({
      query: (range) => `/api/admin/dashboard${toQueryString({ range } as Record<string, string | undefined>)}`,
      transformResponse: (response: Envelope<DashboardData>) => response.data!,
      providesTags: (_result, _error, range) => [{ type: 'Dashboard', id: range || 'DEFAULT' }],
      keepUnusedDataFor: 120,
    }),

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

    // ==================== Reference data (admin-only lists) ====================
    getAdminColors: builder.query<Color[], void>({
      query: () => '/api/colors',
      transformResponse: (response: Envelope<Color[]>) => response.data || [],
      providesTags: [{ type: 'Colors', id: 'LIST' }],
      keepUnusedDataFor: 900,
    }),

    getAdminSizes: builder.query<Size[], void>({
      query: () => '/api/sizes',
      transformResponse: (response: Envelope<Size[]>) => response.data || [],
      providesTags: [{ type: 'Sizes', id: 'LIST' }],
      keepUnusedDataFor: 900,
    }),

    getAdminVendors: builder.query<Vendor[], void>({
      query: () => '/api/vendors',
      transformResponse: (response: Envelope<Vendor[]>) => response.data || [],
      providesTags: [{ type: 'Vendors', id: 'LIST' }],
      keepUnusedDataFor: 900,
    }),

    getAdminSuppliers: builder.query<Supplier[], void>({
      query: () => '/api/suppliers',
      transformResponse: (response: Envelope<Supplier[]>) => response.data || [],
      providesTags: [{ type: 'Suppliers', id: 'LIST' }],
      keepUnusedDataFor: 900,
    }),

    // ==================== Master data mutations ====================
    getAdminCategoriesAdmin: builder.query<AdminListResult<Category>, Record<string, string | number | boolean | undefined> | void>({
      query: (params) => `/api/categories/admin${toQueryString(params as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<Category[]>) => asList<Category>(response),
      providesTags: [{ type: 'Categories', id: 'LIST' }],
      keepUnusedDataFor: 900,
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
      transformResponse: (response: Envelope<Brand[]>) => asList<Brand>(response),
      providesTags: [{ type: 'Brands', id: 'LIST' }],
      keepUnusedDataFor: 900,
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
      transformResponse: (response: Envelope<Collection[]>) => asList<Collection>(response),
      providesTags: [{ type: 'Collections', id: 'LIST' }],
      keepUnusedDataFor: 900,
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
      transformResponse: (response: Envelope<Color[]>) => asList<Color>(response),
      providesTags: [{ type: 'Colors', id: 'LIST' }],
      keepUnusedDataFor: 900,
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
      transformResponse: (response: Envelope<Size[]>) => asList<Size>(response),
      providesTags: [{ type: 'Sizes', id: 'LIST' }],
      keepUnusedDataFor: 900,
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
      transformResponse: (response: Envelope<Vendor[]>) => asList<Vendor>(response),
      providesTags: [{ type: 'Vendors', id: 'LIST' }],
      keepUnusedDataFor: 900,
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
      transformResponse: (response: Envelope<Supplier[]>) => asList<Supplier>(response),
      providesTags: [{ type: 'Suppliers', id: 'LIST' }],
      keepUnusedDataFor: 900,
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

    // ==================== Shipping methods ====================
    getAdminShippingMethods: builder.query<ShippingMethod[], void>({
      query: () => '/api/shipping-methods',
      transformResponse: (response: Envelope<ShippingMethod[]>) => response.data || [],
      providesTags: [{ type: 'Shipping', id: 'LIST' }],
      keepUnusedDataFor: 900,
    }),

    createShippingMethod: builder.mutation<
      ShippingMethod,
      {
        name: string
        charge: number
        estimatedDelivery?: string
        description?: string
        priority?: number
        freeShippingMinAmount?: number
        codAvailable?: boolean
        status?: 'active' | 'inactive'
      }
    >({
      query: (payload) => ({ url: '/api/shipping-methods', method: 'POST', body: payload }),
      transformResponse: (response: Envelope<ShippingMethod>) => response.data!,
      invalidatesTags: CHECKOUT_CONFIG_TAGS,
    }),

    updateShippingMethod: builder.mutation<
      ShippingMethod,
      {
        id: number
        payload: Partial<{
          name: string
          charge: number
          estimatedDelivery: string
          description: string
          priority: number
          freeShippingMinAmount: number | null
          codAvailable: boolean
          status: 'active' | 'inactive'
        }>
      }
    >({
      query: ({ id, payload }) => ({ url: `/api/shipping-methods/${id}`, method: 'PUT', body: payload }),
      transformResponse: (response: Envelope<ShippingMethod>) => response.data!,
      invalidatesTags: CHECKOUT_CONFIG_TAGS,
    }),

    deleteShippingMethod: builder.mutation<{ success: boolean }, number>({
      query: (id) => ({ url: `/api/shipping-methods/${id}`, method: 'DELETE' }),
      invalidatesTags: CHECKOUT_CONFIG_TAGS,
    }),

    // ==================== Payment methods ====================
    getAdminPaymentMethods: builder.query<AdminPaymentMethod[], void>({
      query: () => '/api/payment-methods',
      transformResponse: (response: Envelope<AdminPaymentMethod[]>) => response.data || [],
      providesTags: [{ type: 'PaymentMethods', id: 'LIST' }],
      keepUnusedDataFor: 900,
    }),

    createPaymentMethod: builder.mutation<
      AdminPaymentMethod,
      {
        code: string
        name: string
        type: 'cod' | 'mobile_banking' | 'bank' | 'online'
        enabled?: boolean
        sortOrder?: number
        maintenanceMode?: boolean
        config?: Record<string, unknown>
      }
    >({
      query: (payload) => ({ url: '/api/payment-methods', method: 'POST', body: payload }),
      transformResponse: (response: Envelope<AdminPaymentMethod>) => response.data!,
      invalidatesTags: PAYMENT_CONFIG_TAGS,
    }),

    updatePaymentMethod: builder.mutation<
      AdminPaymentMethod,
      {
        id: number
        payload: Partial<{
          code: string
          name: string
          type: 'cod' | 'mobile_banking' | 'bank' | 'online'
          enabled: boolean
          sortOrder: number
          maintenanceMode: boolean
          config: Record<string, unknown>
        }>
      }
    >({
      query: ({ id, payload }) => ({ url: `/api/payment-methods/${id}`, method: 'PUT', body: payload }),
      transformResponse: (response: Envelope<AdminPaymentMethod>) => response.data!,
      invalidatesTags: PAYMENT_CONFIG_TAGS,
    }),

    setPaymentMethodsStatus: builder.mutation<{ success: boolean }, { ids: number[]; enabled: boolean }>({
      query: (payload) => ({ url: '/api/payment-methods', method: 'PUT', body: payload }),
      invalidatesTags: PAYMENT_CONFIG_TAGS,
    }),

    deletePaymentMethod: builder.mutation<{ success: boolean }, number>({
      query: (id) => ({ url: `/api/payment-methods/${id}`, method: 'DELETE' }),
      invalidatesTags: PAYMENT_CONFIG_TAGS,
    }),

    // ==================== Checkout notices ====================
    getAdminCheckoutNotices: builder.query<AdminCheckoutNotice[], void>({
      query: () => '/api/checkout-notices',
      transformResponse: (response: Envelope<AdminCheckoutNotice[]>) => response.data || [],
      providesTags: [{ type: 'CheckoutNotices', id: 'LIST' }],
      keepUnusedDataFor: 900,
    }),

    createCheckoutNotice: builder.mutation<
      AdminCheckoutNotice,
      {
        text: string
        priority?: number
        backgroundColor?: string
        textColor?: string
        icon?: string
        status?: 'active' | 'inactive'
      }
    >({
      query: (payload) => ({ url: '/api/checkout-notices', method: 'POST', body: payload }),
      transformResponse: (response: Envelope<AdminCheckoutNotice>) => response.data!,
      invalidatesTags: NOTICE_CONFIG_TAGS,
    }),

    updateCheckoutNotice: builder.mutation<
      AdminCheckoutNotice,
      {
        id: number
        payload: Partial<{
          text: string
          priority: number
          backgroundColor: string
          textColor: string
          icon: string
          status: 'active' | 'inactive'
        }>
      }
    >({
      query: ({ id, payload }) => ({ url: `/api/checkout-notices/${id}`, method: 'PUT', body: payload }),
      transformResponse: (response: Envelope<AdminCheckoutNotice>) => response.data!,
      invalidatesTags: NOTICE_CONFIG_TAGS,
    }),

    deleteCheckoutNotice: builder.mutation<{ success: boolean }, number>({
      query: (id) => ({ url: `/api/checkout-notices/${id}`, method: 'DELETE' }),
      invalidatesTags: NOTICE_CONFIG_TAGS,
    }),

    // ==================== Orders ====================
    getAdminOrders: builder.query<AdminListResult<AdminOrder>, { page?: number; limit?: number; status?: string; search?: string } | void>({
      query: (params) => `/api/order${toQueryString(params as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<AdminOrder[]>) => asList<AdminOrder>(response),
      providesTags: (result) =>
        result
          ? [
              ...result.data.map((o) => ({ type: 'Orders' as const, id: o.id })),
              { type: 'Orders' as const, id: 'LIST' },
            ]
          : [{ type: 'Orders' as const, id: 'LIST' }],
    }),

    getAdminOrderById: builder.query<AdminOrder, number>({
      query: (id) => `/api/order/${id}`,
      transformResponse: (response: Envelope<AdminOrder>) => {
        if (!response.data) throw new Error(response.message || 'Order not found')
        return response.data
      },
      providesTags: (_result, _error, id) => [{ type: 'Orders', id }],
    }),

    getOrderInvoice: builder.query<AdminOrder, number>({
      query: (id) => `/api/order/${id}/invoice`,
      transformResponse: (response: Envelope<AdminOrder>) => {
        if (!response.data) throw new Error(response.message || 'Order not found')
        return response.data
      },
      providesTags: (_result, _error, id) => [{ type: 'Orders', id }],
    }),

    updateOrderStatus: builder.mutation<AdminOrder, { id: number; payload: { status: string; note?: string; trackingNumber?: string } }>({
      query: ({ id, payload }) => ({ url: `/api/order/${id}/status`, method: 'PATCH', body: payload }),
      transformResponse: (response: Envelope<AdminOrder>) => response.data!,
      invalidatesTags: (_result, _error, { id }) => [
        { type: 'Orders', id },
        { type: 'Orders', id: 'LIST' },
        { type: 'Dashboard', id: 'DEFAULT' },
      ],
    }),

    verifyOrderPayment: builder.mutation<AdminOrder, { id: number; action: 'verified' | 'rejected'; note?: string }>({
      query: ({ id, action, note }) => ({ url: `/api/order/${id}/payment/verify`, method: 'PATCH', body: { action, note } }),
      transformResponse: (response: Envelope<AdminOrder>) => response.data!,
      invalidatesTags: (_result, _error, { id }) => [
        { type: 'Orders', id },
        { type: 'Orders', id: 'LIST' },
      ],
    }),

    addOrderAdminNote: builder.mutation<AdminOrder, { id: number; note: string }>({
      query: ({ id, note }) => ({ url: `/api/order/${id}/admin-note`, method: 'PATCH', body: { note } }),
      transformResponse: (response: Envelope<AdminOrder>) => response.data!,
      invalidatesTags: (_result, _error, { id }) => [{ type: 'Orders', id }],
    }),

    deleteOrder: builder.mutation<{ success: boolean }, number>({
      query: (id) => ({ url: `/api/order/${id}`, method: 'DELETE' }),
      invalidatesTags: ['Orders'],
    }),

    // ==================== Customers ====================
    getAdminCustomers: builder.query<AdminListResult<AdminCustomer>, void>({
      query: () => '/api/users',
      transformResponse: (response: Envelope<AdminCustomer[]>) => asList<AdminCustomer>(response),
      providesTags: [{ type: 'Users', id: 'LIST' }],
    }),

    createAdmin: builder.mutation<
      AdminCustomer,
      { name: string; email: string; phone: string; password: string; role: 'admin' | 'manager' }
    >({
      query: (payload) => ({
        url: '/api/users/admin',
        method: 'POST',
        body: { ...payload, confirmPassword: payload.password },
      }),
      transformResponse: (response: Envelope<AdminCustomer>) => response.data!,
      invalidatesTags: [{ type: 'Users', id: 'LIST' }],
    }),

    deleteCustomer: builder.mutation<{ success: boolean }, number>({
      query: (id) => ({ url: `/api/users/${id}`, method: 'DELETE' }),
      invalidatesTags: [{ type: 'Users', id: 'LIST' }],
    }),

    // ==================== Coupons ====================
    getAdminCoupons: builder.query<AdminCoupon[], void>({
      query: () => '/api/coupons',
      transformResponse: (response: Envelope<AdminCoupon[]>) => response.data || [],
      providesTags: [{ type: 'Coupons', id: 'LIST' }],
    }),

    createCoupon: builder.mutation<
      AdminCoupon,
      {
        code: string
        discountType: 'percentage' | 'fixed'
        discountValue: number
        minOrderAmount?: number
        expiryDate?: string
        status?: 'active' | 'inactive'
      }
    >({
      query: (payload) => ({ url: '/api/coupons', method: 'POST', body: payload }),
      transformResponse: (response: Envelope<AdminCoupon>) => response.data!,
      invalidatesTags: [{ type: 'Coupons', id: 'LIST' }],
    }),

    updateCoupon: builder.mutation<
      AdminCoupon,
      {
        id: number
        payload: Partial<{
          code: string
          discountType: 'percentage' | 'fixed'
          discountValue: number
          minOrderAmount?: number
          expiryDate?: string
          status?: 'active' | 'inactive'
        }>
      }
    >({
      query: ({ id, payload }) => ({ url: `/api/coupons/${id}`, method: 'PUT', body: payload }),
      transformResponse: (response: Envelope<AdminCoupon>) => response.data!,
      invalidatesTags: [{ type: 'Coupons', id: 'LIST' }],
    }),

    deleteCoupon: builder.mutation<{ success: boolean }, number>({
      query: (id) => ({ url: `/api/coupons/${id}`, method: 'DELETE' }),
      invalidatesTags: [{ type: 'Coupons', id: 'LIST' }],
    }),

    // ==================== Banners ====================
    getBanners: builder.query<Banner[], void>({
      query: () => '/api/banners',
      transformResponse: (response: Envelope<Banner[]>) => response.data || [],
      providesTags: [{ type: 'Banners', id: 'LIST' }],
      keepUnusedDataFor: 900,
    }),

    createBanner: builder.mutation<Banner, Partial<Banner>>({
      query: (payload) => ({ url: '/api/banners', method: 'POST', body: payload }),
      transformResponse: (response: Envelope<Banner>) => response.data!,
      invalidatesTags: [{ type: 'Banners', id: 'LIST' }],
    }),

    updateBanner: builder.mutation<Banner, { id: number; payload: Partial<Banner> }>({
      query: ({ id, payload }) => ({ url: `/api/banners/${id}`, method: 'PUT', body: payload }),
      transformResponse: (response: Envelope<Banner>) => response.data!,
      invalidatesTags: [{ type: 'Banners', id: 'LIST' }],
    }),

    deleteBanner: builder.mutation<{ success: boolean }, number>({
      query: (id) => ({ url: `/api/banners/${id}`, method: 'DELETE' }),
      invalidatesTags: [{ type: 'Banners', id: 'LIST' }],
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

    getMediaConfig: builder.query<{ configured: boolean; cloudName: string | null }, void>({
      query: () => '/api/media/config',
      transformResponse: (response: Envelope<{ configured: boolean; cloudName: string | null }>) =>
        response.data || { configured: false, cloudName: null },
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

    updateMediaAlt: builder.mutation<MediaAsset, { id: number; alt: string }>({
      query: ({ id, alt }) => ({ url: `/api/media/${id}/alt`, method: 'PUT', body: { alt } }),
      transformResponse: (response: Envelope<MediaAsset>) => response.data!,
      invalidatesTags: [{ type: 'Media', id: 'LIST' }],
    }),

    // ==================== Settings ====================
    setSetting: builder.mutation<{ id: number; key: string; value: string }, { key: string; value: unknown }>({
      query: (payload) => ({ url: '/api/settings', method: 'PUT', body: payload }),
      transformResponse: (response: Envelope<{ id: number; key: string; value: string }>) => response.data!,
      invalidatesTags: SETTINGS_TAGS,
    }),

    // ==================== Tracking integrations ====================
    getTrackingIntegrations: builder.query<MarketingIntegration[], void>({
      query: () => '/api/tracking',
      transformResponse: (response: Envelope<MarketingIntegration[]>) => response.data || [],
      providesTags: [{ type: 'Tracking', id: 'LIST' }],
    }),

    createTrackingIntegration: builder.mutation<
      MarketingIntegration,
      { name: string; type: MarketingIntegration['type']; pixelId?: string; status?: 'active' | 'inactive' }
    >({
      query: (payload) => ({ url: '/api/tracking', method: 'POST', body: payload }),
      transformResponse: (response: Envelope<MarketingIntegration>) => response.data!,
      invalidatesTags: [{ type: 'Tracking', id: 'LIST' }],
    }),

    updateTrackingIntegration: builder.mutation<
      MarketingIntegration,
      {
        id: number
        payload: { name?: string; type?: MarketingIntegration['type']; pixelId?: string; status?: 'active' | 'inactive' }
      }
    >({
      query: ({ id, payload }) => ({ url: `/api/tracking/${id}`, method: 'PUT', body: payload }),
      transformResponse: (response: Envelope<MarketingIntegration>) => response.data!,
      invalidatesTags: [{ type: 'Tracking', id: 'LIST' }],
    }),

    // ==================== Homepage config ====================
    getHomepageConfig: builder.query<HomepageConfig, void>({
      query: () => '/api/homepage/admin/config',
      transformResponse: (response: Envelope<HomepageConfig>) => response.data!,
      providesTags: [{ type: 'HomepageConfig', id: 'ADMIN' }],
    }),

    saveHomepageConfig: builder.mutation<HomepageConfig, Partial<HomepageConfig>>({
      query: (payload) => ({ url: '/api/homepage/admin/config', method: 'PUT', body: payload }),
      transformResponse: (response: Envelope<HomepageConfig>) => response.data!,
      invalidatesTags: [{ type: 'HomepageConfig', id: 'ADMIN' }, { type: 'Homepage', id: 'LIST' }],
    }),

    resetHomepageConfig: builder.mutation<HomepageConfig, void>({
      query: () => ({ url: '/api/homepage/admin/reset-defaults', method: 'POST' }),
      transformResponse: (response: Envelope<HomepageConfig>) => response.data!,
      invalidatesTags: [{ type: 'HomepageConfig', id: 'ADMIN' }, { type: 'Homepage', id: 'LIST' }],
    }),

    // ==================== Newsletter ====================
    getNewsletterSubscribers: builder.query<NewsletterSubscriber[], void>({
      query: () => '/api/homepage/admin/subscribers',
      transformResponse: (response: Envelope<NewsletterSubscriber[]>) => response.data || [],
      providesTags: [{ type: 'Newsletter', id: 'LIST' }],
    }),

    // ==================== Policies ====================
    getPolicyPages: builder.query<PolicyPage[], void>({
      query: () => '/api/pages',
      transformResponse: (response: Envelope<PolicyPage[]>) => response.data || [],
      providesTags: [{ type: 'Policies', id: 'LIST' }],
    }),

    createPolicyPage: builder.mutation<PolicyPage, { slug: string; title: string; content: string; status: 'published' | 'draft' }>({
      query: (payload) => ({ url: '/api/pages', method: 'POST', body: payload }),
      transformResponse: (response: Envelope<PolicyPage>) => response.data!,
      invalidatesTags: [{ type: 'Policies', id: 'LIST' }],
    }),

    updatePolicyPage: builder.mutation<PolicyPage, { id: number; payload: { title?: string; content?: string; status?: 'published' | 'draft' } }>({
      query: ({ id, payload }) => ({ url: `/api/pages/${id}`, method: 'PUT', body: payload }),
      transformResponse: (response: Envelope<PolicyPage>) => response.data!,
      invalidatesTags: [{ type: 'Policies', id: 'LIST' }],
    }),

    deletePolicyPage: builder.mutation<{ success: boolean }, number>({
      query: (id) => ({ url: `/api/pages/${id}`, method: 'DELETE' }),
      invalidatesTags: [{ type: 'Policies', id: 'LIST' }],
    }),

    // ==================== Contact messages ====================
    getContactMessages: builder.query<ContactMessage[], void>({
      query: () => '/api/pages/contact',
      transformResponse: (response: Envelope<ContactMessage[]>) => response.data || [],
      providesTags: [{ type: 'ContactMessages', id: 'LIST' }],
    }),

    setContactMessageStatus: builder.mutation<ContactMessage, { id: number; status: 'new' | 'read' | 'archived' }>({
      query: ({ id, status }) => ({ url: `/api/pages/contact/${id}`, method: 'PATCH', body: { status } }),
      transformResponse: (response: Envelope<ContactMessage>) => response.data!,
      invalidatesTags: [{ type: 'ContactMessages', id: 'LIST' }],
    }),

    // ==================== Expenses ====================
    getAdminExpenses: builder.query<ExpenseListResult, ExpenseFilters | void>({
      query: (filters) => `/api/expenses${toQueryString(filters as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<Expense[]>) => ({
        data: response.data || [],
        total: response.pagination?.total || 0,
        page: response.pagination?.page || 1,
        limit: response.pagination?.limit || 20,
        totalPages: response.pagination?.totalPages || 1,
      }),
      providesTags: (result) =>
        result
          ? [
              ...result.data.map((e) => ({ type: 'Expense' as const, id: e.id })),
              { type: 'Expenses' as const, id: 'LIST' },
            ]
          : [{ type: 'Expenses' as const, id: 'LIST' }],
    }),

    getAdminExpenseById: builder.query<Expense, number>({
      query: (id) => `/api/expenses/${id}`,
      transformResponse: (response: Envelope<Expense>) => {
        if (!response.data) throw new Error(response.message || 'Expense not found')
        return response.data
      },
      providesTags: (_result, _error, id) => [{ type: 'Expense', id }],
    }),

    createExpense: builder.mutation<Expense, ExpenseInput>({
      query: (payload) => ({ url: '/api/expenses', method: 'POST', body: payload }),
      transformResponse: (response: Envelope<Expense>) => response.data!,
      invalidatesTags: ['Expenses', 'Expense', 'ExpenseSummary', 'ExpenseReports'],
    }),

    updateExpense: builder.mutation<Expense, { id: number; payload: Partial<ExpenseInput> }>({
      query: ({ id, payload }) => ({ url: `/api/expenses/${id}`, method: 'PUT', body: payload }),
      transformResponse: (response: Envelope<Expense>) => response.data!,
      invalidatesTags: (_result, _error, { id }) => [
        { type: 'Expense', id },
        { type: 'Expenses', id: 'LIST' },
        { type: 'ExpenseSummary', id: 'LIST' },
        { type: 'ExpenseReports', id: 'LIST' },
      ],
    }),

    deleteExpense: builder.mutation<{ success: boolean }, number>({
      query: (id) => ({ url: `/api/expenses/${id}`, method: 'DELETE' }),
      invalidatesTags: ['Expenses', 'Expense', 'ExpenseSummary', 'ExpenseReports'],
    }),

    getAdminExpenseCategories: builder.query<ExpenseCategory[], void>({
      query: () => '/api/expenses/categories',
      transformResponse: (response: Envelope<ExpenseCategory[]>) => response.data || [],
      providesTags: [{ type: 'ExpenseCategories', id: 'LIST' }],
    }),

    createExpenseCategory: builder.mutation<ExpenseCategory, Record<string, unknown>>({
      query: (payload) => ({ url: '/api/expenses/categories', method: 'POST', body: payload }),
      transformResponse: (response: Envelope<ExpenseCategory>) => response.data!,
      invalidatesTags: ['ExpenseCategories'],
    }),

    updateExpenseCategory: builder.mutation<ExpenseCategory, { id: number; payload: Record<string, unknown> }>({
      query: ({ id, payload }) => ({ url: `/api/expenses/categories/${id}`, method: 'PUT', body: payload }),
      transformResponse: (response: Envelope<ExpenseCategory>) => response.data!,
      invalidatesTags: ['ExpenseCategories'],
    }),

    deleteExpenseCategory: builder.mutation<{ success: boolean; usageCount?: number; message?: string }, number>({
      query: (id) => ({ url: `/api/expenses/categories/${id}`, method: 'DELETE' }),
      transformResponse: (response: Envelope<{ usageCount?: number }>) => ({
        success: response.success,
        usageCount: response.data?.usageCount ?? 0,
        message: response.message,
      }),
      invalidatesTags: ['ExpenseCategories', 'Expenses'],
    }),

    getExpenseTeamMembers: builder.query<TeamMember[], void>({
      query: () => '/api/expenses/members',
      transformResponse: (response: Envelope<TeamMember[]>) => response.data || [],
    }),

    getExpenseSummary: builder.query<ExpenseSummary, Record<string, string | undefined> | void>({
      query: (params) => `/api/expenses/summary${toQueryString(params as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<ExpenseSummary>) => response.data!,
      providesTags: [{ type: 'ExpenseSummary', id: 'LIST' }],
    }),

    getExpenseByMember: builder.query<ExpenseMemberRow[], Record<string, string | number | boolean | undefined> | void>({
      query: (params) => `/api/expenses/by-member${toQueryString(params as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<ExpenseMemberRow[]>) => response.data || [],
      providesTags: [{ type: 'ExpenseReports', id: 'LIST' }],
    }),

    getExpenseByCategory: builder.query<ExpenseCategoryRow[], Record<string, string | number | boolean | undefined> | void>({
      query: (params) => `/api/expenses/by-category${toQueryString(params as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<ExpenseCategoryRow[]>) => response.data || [],
      providesTags: [{ type: 'ExpenseReports', id: 'LIST' }],
    }),

    getExpenseMonthlyReport: builder.query<ExpenseMonthlyReport, Record<string, string | number | undefined> | void>({
      query: (params) => `/api/expenses/monthly${toQueryString(params as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<ExpenseMonthlyReport>) => response.data!,
      providesTags: [{ type: 'ExpenseReports', id: 'LIST' }],
    }),

    getExpenseMonthlyTrend: builder.query<{ year: number; data: ExpenseTrendRow[] }, Record<string, string | undefined> | void>({
      query: (params) => `/api/expenses/trends${toQueryString(params as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<{ year: number; data: ExpenseTrendRow[] }>) => response.data!,
      providesTags: [{ type: 'ExpenseReports', id: 'LIST' }],
    }),

    getExpenseRangeReport: builder.query<ExpenseRangeReport, Record<string, string | undefined> | void>({
      query: (params) => `/api/expenses/report${toQueryString(params as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<ExpenseRangeReport>) => response.data!,
      providesTags: [{ type: 'ExpenseReports', id: 'LIST' }],
    }),

    getProfitOverview: builder.query<ProfitOverview, Record<string, string | number | undefined> | void>({
      query: (params) => `/api/expenses/profit${toQueryString(params as Record<string, string | number | boolean | undefined>)}`,
      transformResponse: (response: Envelope<ProfitOverview>) => response.data!,
      providesTags: [{ type: 'ExpenseReports', id: 'LIST' }],
    }),

    exportExpensesCsv: builder.mutation<{ csv: string; count: number }, ExpenseFilters | void>({
      query: (filters) => ({
        url: `/api/expenses/export/csv${toQueryString(filters as Record<string, string | number | boolean | undefined>)}`,
        method: 'GET',
      }),
      transformResponse: (response: Envelope<{ csv?: string; count?: number }>) => ({
        csv: response.data?.csv || '',
        count: response.data?.count ?? 0,
      }),
    }),
  }),
})

export const {
  useGetAdminDashboardQuery,
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
  useCreateShippingMethodMutation,
  useUpdateShippingMethodMutation,
  useDeleteShippingMethodMutation,
  useGetAdminPaymentMethodsQuery,
  useCreatePaymentMethodMutation,
  useUpdatePaymentMethodMutation,
  useSetPaymentMethodsStatusMutation,
  useDeletePaymentMethodMutation,
  useGetAdminCheckoutNoticesQuery,
  useCreateCheckoutNoticeMutation,
  useUpdateCheckoutNoticeMutation,
  useDeleteCheckoutNoticeMutation,
  useGetAdminOrdersQuery,
  useGetAdminOrderByIdQuery,
  useGetOrderInvoiceQuery,
  useUpdateOrderStatusMutation,
  useVerifyOrderPaymentMutation,
  useAddOrderAdminNoteMutation,
  useDeleteOrderMutation,
  useGetAdminCustomersQuery,
  useCreateAdminMutation,
  useDeleteCustomerMutation,
  useGetAdminCouponsQuery,
  useCreateCouponMutation,
  useUpdateCouponMutation,
  useDeleteCouponMutation,
  useGetBannersQuery,
  useCreateBannerMutation,
  useUpdateBannerMutation,
  useDeleteBannerMutation,
  useGetAdminMediaQuery,
  useGetAdminMediaFoldersQuery,
  useGetMediaConfigQuery,
  useUploadMediaMutation,
  useDeleteMediaMutation,
  useUpdateMediaAltMutation,
  useSetSettingMutation,
  useGetTrackingIntegrationsQuery,
  useCreateTrackingIntegrationMutation,
  useUpdateTrackingIntegrationMutation,
  useGetHomepageConfigQuery,
  useSaveHomepageConfigMutation,
  useResetHomepageConfigMutation,
  useGetNewsletterSubscribersQuery,
  useGetPolicyPagesQuery,
  useCreatePolicyPageMutation,
  useUpdatePolicyPageMutation,
  useDeletePolicyPageMutation,
  useGetContactMessagesQuery,
  useSetContactMessageStatusMutation,
  useGetAdminExpensesQuery,
  useLazyGetAdminExpensesQuery,
  useGetAdminExpenseByIdQuery,
  useCreateExpenseMutation,
  useUpdateExpenseMutation,
  useDeleteExpenseMutation,
  useGetAdminExpenseCategoriesQuery,
  useCreateExpenseCategoryMutation,
  useUpdateExpenseCategoryMutation,
  useDeleteExpenseCategoryMutation,
  useGetExpenseTeamMembersQuery,
  useGetExpenseSummaryQuery,
  useGetExpenseByMemberQuery,
  useGetExpenseByCategoryQuery,
  useGetExpenseMonthlyReportQuery,
  useGetExpenseMonthlyTrendQuery,
  useGetExpenseRangeReportQuery,
  useGetProfitOverviewQuery,
  useExportExpensesCsvMutation,
} = adminProductsApi

export { parseError, toQueryString }