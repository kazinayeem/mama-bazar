import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react'
import { authStorage } from '../../lib/authStorage'
import { API_BASE_URL } from '../../lib/apiConfig'
import type { Category, Product, ProductReview } from '../../types'
import type { Brand, Collection, TrackingConfig } from '../../types/admin'
import type { HomepageData } from '../../types/homepage'

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

export const commerceApi = createApi({
  reducerPath: 'commerceApi',
  baseQuery: fetchBaseQuery({
    baseUrl: API_BASE_URL,
    prepareHeaders: (headers) => {
      const token = authStorage.getToken()
      if (token) headers.set('Authorization', `Bearer ${token}`)
      return headers
    },
  }),
  tagTypes: ['Products', 'Product', 'Categories', 'HeroSlides', 'Reviews', 'Homepage', 'Newsletter'],
  keepUnusedDataFor: 120,
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
    }),

    getHeroSlides: builder.query<string[], void>({
      query: () => '/api/settings/hero-slides',
      transformResponse: (response: ApiEnvelope<string[]>) => response.data || [],
      providesTags: [{ type: 'HeroSlides', id: 'LIST' }],
    }),

    getHomepage: builder.query<HomepageData, void>({
      query: () => '/api/homepage',
      transformResponse: (response: ApiEnvelope<HomepageData>) => response.data as HomepageData,
      providesTags: [{ type: 'Homepage', id: 'LIST' }],
    }),

    getTrackingConfig: builder.query<TrackingConfig, void>({
      query: () => '/api/tracking/config',
      transformResponse: (response: ApiEnvelope<TrackingConfig>) => response.data as TrackingConfig,
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
    }),

    getCollections: builder.query<Collection[], void>({
      query: () => '/api/collections',
      transformResponse: (response: ApiEnvelope<Collection[]>) => response.data || [],
    }),

    getReviews: builder.query<ProductReview[], { productId?: number; limit?: number }>({
      query: ({ productId, limit }) =>
        `/api/reviews${toQueryString({ productId, limit } as ProductsQueryParams)}`,
      transformResponse: (response: ApiEnvelope<ProductReview[]>) => response.data || [],
      providesTags: (_result, _error, args) => [{ type: 'Reviews' as const, id: args?.productId ?? 'LIST' }],
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
        { type: 'Product' as const, id: 'LIST' },
      ],
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
  useGetReviewsQuery,
  useAddReviewMutation,
  useGetTrackingConfigQuery,
} = commerceApi
