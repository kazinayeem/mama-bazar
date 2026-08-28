import type {
  AuthUser,
  Category,
  CheckoutNotice,
  PaymentMethodInfo,
  PolicyPage,
  Product,
  ProductReview,
  PublicOrderTrackingResult,
  ShippingMethod,
  StoreInfo,
  UserAddress,
  UserOrderWithItems,
} from '../../types'
import type { Banner, Brand, Collection, TrackingConfig } from '../../types/admin'
import type { HomepageData } from '../../types/homepage'
import { baseApi } from './api'

type ApiEnvelope<T> = {
  success: boolean
  data?: T
  pagination?: {
    page: number
    limit: number
    total: number
    totalPages: number
  }
  message?: string
}

export type ProductsQueryParams = {
  page?: number
  limit?: number
  category?: string
  search?: string
  sort?: string
  minPrice?: number
  maxPrice?: number
  brand?: string
  label?: string
  inStock?: boolean
  minRating?: number
  sale?: boolean
}

export type ProductsQueryResult = {
  data: Product[]
  total: number
  page: number
  limit: number
  totalPages: number
}

const toQueryString = (params?: ProductsQueryParams | void) => {
  const query = new URLSearchParams()
  if (!params) return ''
  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return
    query.set(key, String(value))
  })
  const serialized = query.toString()
  return serialized ? `?${serialized}` : ''
}

// How long a cache entry is kept after its last subscriber unsubscribes.
// Stable reference data (categories, brands, settings, …) is kept long so that
// navigating away and back reuses the cache instead of re-fetching.
const LONG_LIVED = 900

export const commerceApi = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getProducts: builder.query<ProductsQueryResult, ProductsQueryParams | void>({
      query: (params) => `/api/products${toQueryString(params)}`,
      transformResponse: (response: ApiEnvelope<Product[]>) => {
        const list = response.data || []
        return {
          data: list,
          total: response.pagination?.total || list.length,
          page: response.pagination?.page || 1,
          limit: response.pagination?.limit || 12,
          totalPages: response.pagination?.totalPages || 1,
        }
      },
      providesTags: (result) =>
        result
          ? [
              ...result.data.map((product) => ({ type: 'Product' as const, id: product.id })),
              { type: 'Products' as const, id: 'LIST' },
            ]
          : [{ type: 'Products' as const, id: 'LIST' }],
    }),

    getProductBySlug: builder.query<Product | null, string>({
      query: (slug) => `/api/products/slug/${slug}`,
      transformResponse: (response: ApiEnvelope<Product>) => response.data || null,
      providesTags: (_result, _error, slug) => [{ type: 'Product', id: slug }],
    }),

    getRelatedProducts: builder.query<Product[], number>({
      query: (productId) => `/api/products/${productId}/related`,
      transformResponse: (response: ApiEnvelope<Product[]>) => response.data || [],
      providesTags: (_result, _error, productId) => [{ type: 'Products', id: `RELATED_${productId}` }],
    }),

    getCategories: builder.query<Category[], void>({
      query: () => '/api/categories',
      transformResponse: (response: ApiEnvelope<Category[]>) => response.data || [],
      providesTags: [{ type: 'Categories', id: 'LIST' }],
      keepUnusedDataFor: LONG_LIVED,
    }),

    getHeroSlides: builder.query<string[], void>({
      query: () => '/api/settings/hero-slides',
      transformResponse: (response: ApiEnvelope<string[]>) => response.data || [],
      providesTags: [{ type: 'HeroSlides', id: 'LIST' }],
      keepUnusedDataFor: LONG_LIVED,
    }),

    getHomepage: builder.query<HomepageData, void>({
      query: () => '/api/homepage',
      transformResponse: (response: ApiEnvelope<HomepageData>) => response.data as HomepageData,
      providesTags: [{ type: 'Homepage', id: 'LIST' }],
      keepUnusedDataFor: LONG_LIVED,
    }),

    getTrackingConfig: builder.query<TrackingConfig, void>({
      query: () => '/api/tracking/config',
      transformResponse: (response: ApiEnvelope<TrackingConfig>) => response.data as TrackingConfig,
    }),

    getStoreInfo: builder.query<StoreInfo, void>({
      query: () => '/api/settings/store-info',
      transformResponse: (response: ApiEnvelope<StoreInfo>) => response.data as StoreInfo,
      providesTags: [{ type: 'StoreInfo', id: 'DETAIL' }],
    }),

    subscribeNewsletter: builder.mutation<{ email: string; alreadySubscribed: boolean }, { email: string; source?: string }>({
      query: (body) => ({
        url: '/api/homepage/newsletter/subscribe',
        method: 'POST',
        body,
      }),
      transformResponse: (response: ApiEnvelope<{ email: string; alreadySubscribed: boolean }>) =>
        response.data as { email: string; alreadySubscribed: boolean },
      invalidatesTags: [{ type: 'Newsletter', id: 'LIST' }],
    }),

    getBrands: builder.query<Brand[], void>({
      query: () => '/api/brands',
      transformResponse: (response: ApiEnvelope<Brand[]>) => response.data || [],
      providesTags: [{ type: 'Brands', id: 'LIST' }],
      keepUnusedDataFor: LONG_LIVED,
    }),

    getCollections: builder.query<Collection[], void>({
      query: () => '/api/collections',
      transformResponse: (response: ApiEnvelope<Collection[]>) => response.data || [],
      providesTags: [{ type: 'Collections', id: 'LIST' }],
      keepUnusedDataFor: LONG_LIVED,
    }),

    getBanners: builder.query<Banner[], void>({
      query: () => '/api/banners',
      transformResponse: (response: ApiEnvelope<Banner[]>) => response.data || [],
      providesTags: [{ type: 'Banners' as const, id: 'LIST' }],
      keepUnusedDataFor: LONG_LIVED,
    }),

    getReviews: builder.query<ProductReview[], { productId?: number; limit?: number }>({
      query: ({ productId, limit }) =>
        `/api/reviews${toQueryString({ productId, limit } as ProductsQueryParams)}`,
      transformResponse: (response: ApiEnvelope<ProductReview[]>) => response.data || [],
      providesTags: (_result, _error, args) => [
        { type: 'Reviews' as const, id: args?.productId ?? 'LIST' },
        { type: 'Reviews' as const, id: 'LIST' },
      ],
    }),

    addReview: builder.mutation<ProductReview, { productId: number; rating: number; title?: string; comment: string }>({
      query: (body) => ({
        url: '/api/reviews',
        method: 'POST',
        body,
      }),
      transformResponse: (response: ApiEnvelope<ProductReview>) => response.data as ProductReview,
      invalidatesTags: (_result, _error, args) => [
        { type: 'Reviews' as const, id: args.productId },
        { type: 'Reviews' as const, id: 'LIST' },
        { type: 'Product' as const, id: args.productId },
      ],
    }),

    // ---- Settings / store configuration (shared with admin) ----
    getSettings: builder.query<Array<{ id: number; key: string; value: string }>, void>({
      query: () => '/api/settings',
      transformResponse: (response: ApiEnvelope<Array<{ id: number; key: string; value: string }>>) =>
        response.data || [],
      providesTags: [{ type: 'Settings', id: 'LIST' }],
      keepUnusedDataFor: LONG_LIVED,
    }),

    getContactSetting: builder.query<Record<string, string>, void>({
      query: () => '/api/settings/contact_info',
      transformResponse: (response: ApiEnvelope<Record<string, string> | string>) => {
        const raw = response.data
        if (typeof raw === 'string') {
          try {
            return JSON.parse(raw) as Record<string, string>
          } catch {
            return {}
          }
        }
        return raw || {}
      },
      providesTags: [{ type: 'Settings', id: 'CONTACT' }],
      keepUnusedDataFor: LONG_LIVED,
    }),

    // ---- Checkout reference data ----
    getShippingMethods: builder.query<ShippingMethod[], void>({
      query: () => '/api/shipping-methods/public',
      transformResponse: (response: ApiEnvelope<ShippingMethod[]>) => response.data || [],
      providesTags: [{ type: 'Shipping', id: 'PUBLIC' }],
      keepUnusedDataFor: LONG_LIVED,
    }),

    getPaymentMethods: builder.query<PaymentMethodInfo[], void>({
      query: () => '/api/payment-methods/public',
      transformResponse: (response: ApiEnvelope<PaymentMethodInfo[]>) => response.data || [],
      providesTags: [{ type: 'PaymentMethods', id: 'PUBLIC' }],
      keepUnusedDataFor: LONG_LIVED,
    }),

    getCheckoutNotices: builder.query<CheckoutNotice[], void>({
      query: () => '/api/checkout-notices/public',
      transformResponse: (response: ApiEnvelope<CheckoutNotice[]>) => response.data || [],
      providesTags: [{ type: 'CheckoutNotices', id: 'PUBLIC' }],
      keepUnusedDataFor: LONG_LIVED,
    }),

    getPolicyPage: builder.query<PolicyPage | null, string>({
      query: (slug) => `/api/pages/p/${slug}`,
      transformResponse: (response: ApiEnvelope<PolicyPage>) => response.data || null,
      providesTags: (_result, _error, slug) => [{ type: 'Policies', id: slug }],
    }),

    // ---- Authenticated user data ----
    getCurrentUser: builder.query<AuthUser | null, void>({
      query: () => '/api/users/profile',
      transformResponse: (response: ApiEnvelope<AuthUser>) => response.data || null,
      providesTags: [{ type: 'Profile', id: 'CURRENT' }],
    }),

    getMyOrders: builder.query<UserOrderWithItems[], void>({
      query: () => '/api/users/orders',
      transformResponse: (response: ApiEnvelope<UserOrderWithItems[]>) => response.data || [],
      providesTags: [{ type: 'Orders', id: 'MINE' }],
    }),

    getMyAddresses: builder.query<UserAddress[], void>({
      query: () => '/api/users/addresses',
      transformResponse: (response: ApiEnvelope<UserAddress[]>) => response.data || [],
      providesTags: [{ type: 'Addresses', id: 'MINE' }],
    }),

    // ---- User mutations ----
    estimateShipping: builder.mutation<ShippingMethod[], number>({
      query: (subtotal) => ({
        url: '/api/shipping-methods/estimate',
        method: 'POST',
        body: { subtotal },
      }),
      transformResponse: (response: ApiEnvelope<ShippingMethod[]>) => response.data || [],
    }),

    validateCoupon: builder.mutation<
      { discount: number; discountType: string; discountValue: string },
      { code: string; subtotal: number }
    >({
      query: ({ code, subtotal }) => ({
        url: '/api/coupons/validate',
        method: 'POST',
        body: { code, subtotal },
      }),
      transformResponse: (response: ApiEnvelope<{ discount: number; discountType: string; discountValue: string }>) => {
        if (!response.data) throw new Error(response.message || 'Invalid coupon')
        return response.data
      },
    }),

    trackOrder: builder.mutation<PublicOrderTrackingResult, { orderId?: string; phone?: string }>({
      query: (params) => ({
        url: '/api/order/track',
        method: 'POST',
        body: params,
      }),
      transformResponse: (response: ApiEnvelope<PublicOrderTrackingResult>) => response.data as PublicOrderTrackingResult,
    }),

    uploadPaymentProof: builder.mutation<{ url: string; provider?: string }, File>({
      query: (file) => {
        const formData = new FormData()
        formData.append('file', file)
        return {
          url: '/api/uploads/payment-proof',
          method: 'POST',
          body: formData,
        }
      },
      transformResponse: (response: ApiEnvelope<{ url: string; provider?: string }>) =>
        response.data || { url: '' },
    }),

    submitContactMessage: builder.mutation<{ id?: number }, { name: string; phone: string; email?: string; message: string }>({
      query: (body) => ({
        url: '/api/pages/contact',
        method: 'POST',
        body,
      }),
      transformResponse: (response: ApiEnvelope<{ id?: number }>) => response.data || {},
      invalidatesTags: [{ type: 'ContactMessages', id: 'LIST' }],
    }),

    requestPasswordReset: builder.mutation<{ message?: string }, string>({
      query: (phone) => ({
        url: '/api/users/password-reset-request',
        method: 'POST',
        body: { phone },
      }),
      transformResponse: (response: ApiEnvelope<{ message?: string }>) => response.data || {},
    }),

    updateMyProfile: builder.mutation<
      AuthUser,
      { name?: string; phone?: string; shippingArea?: string; shippingAddress?: string }
    >({
      query: (body) => ({
        url: '/api/users/profile',
        method: 'PUT',
        body,
      }),
      transformResponse: (response: ApiEnvelope<AuthUser>) => response.data as AuthUser,
      invalidatesTags: [{ type: 'Profile', id: 'CURRENT' }],
    }),

    createMyAddress: builder.mutation<
      UserAddress[],
      { recipientName: string; phone: string; shippingArea: string; address: string; isDefault?: boolean }
    >({
      query: (body) => ({
        url: '/api/users/addresses',
        method: 'POST',
        body,
      }),
      transformResponse: (response: ApiEnvelope<UserAddress[]>) => response.data || [],
      invalidatesTags: [{ type: 'Addresses', id: 'MINE' }],
    }),

    updateMyAddress: builder.mutation<
      UserAddress[],
      {
        id: number
        payload: {
          recipientName?: string
          phone?: string
          shippingArea?: string
          address?: string
          isDefault?: boolean
        }
      }
    >({
      query: ({ id, payload }) => ({
        url: `/api/users/addresses/${id}`,
        method: 'PUT',
        body: payload,
      }),
      transformResponse: (response: ApiEnvelope<UserAddress[]>) => response.data || [],
      invalidatesTags: [{ type: 'Addresses', id: 'MINE' }],
    }),

    deleteMyAddress: builder.mutation<UserAddress[], number>({
      query: (id) => ({
        url: `/api/users/addresses/${id}`,
        method: 'DELETE',
      }),
      transformResponse: (response: ApiEnvelope<UserAddress[]>) => response.data || [],
      invalidatesTags: [{ type: 'Addresses', id: 'MINE' }],
    }),

    changeMyPassword: builder.mutation<
      { success: boolean; message: string },
      { oldPassword: string; newPassword: string }
    >({
      query: (body) => ({
        url: '/api/users/change-password',
        method: 'POST',
        body,
      }),
      transformResponse: (response: ApiEnvelope<{ success: boolean; message: string }>) => ({
        success: response.success,
        message: response.message || 'Password changed successfully',
      }),
    }),
  }),
})

export const {
  useGetProductsQuery,
  useGetProductBySlugQuery,
  useGetRelatedProductsQuery,
  useGetCategoriesQuery,
  useGetHeroSlidesQuery,
  useGetHomepageQuery,
  useSubscribeNewsletterMutation,
  useGetBrandsQuery,
  useGetCollectionsQuery,
  useGetBannersQuery,
  useGetReviewsQuery,
  useAddReviewMutation,
  useGetTrackingConfigQuery,
  useGetStoreInfoQuery,
  useGetSettingsQuery,
  useGetContactSettingQuery,
  useGetShippingMethodsQuery,
  useGetPaymentMethodsQuery,
  useGetCheckoutNoticesQuery,
  useGetPolicyPageQuery,
  useGetCurrentUserQuery,
  useGetMyOrdersQuery,
  useGetMyAddressesQuery,
  useEstimateShippingMutation,
  useValidateCouponMutation,
  useTrackOrderMutation,
  useUploadPaymentProofMutation,
  useSubmitContactMessageMutation,
  useRequestPasswordResetMutation,
  useUpdateMyProfileMutation,
  useCreateMyAddressMutation,
  useUpdateMyAddressMutation,
  useDeleteMyAddressMutation,
  useChangeMyPasswordMutation,
} = commerceApi