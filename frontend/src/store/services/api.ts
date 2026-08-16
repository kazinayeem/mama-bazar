import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react'
import { authStorage } from '../../lib/authStorage'
import { API_BASE_URL } from '../../lib/apiConfig'

/**
 * Central RTK Query API layer.
 *
 * Every server-data endpoint in the app lives here. `commerceApi` and
 * `adminProductsApi` extend this single API via `injectEndpoints`, so all
 * caches share the same store slice, the same tag namespace and therefore
 * invalidation: an admin mutation invalidates the matching user-facing cache
 * (and vice versa) without any extra wiring.
 */

export const baseApi = createApi({
  reducerPath: 'api',
  baseQuery: fetchBaseQuery({
    baseUrl: API_BASE_URL,
    prepareHeaders: (headers) => {
      const token = authStorage.getToken()
      if (token) headers.set('Authorization', `Bearer ${token}`)
      return headers
    },
  }),
  tagTypes: [
    'Products',
    'Product',
    'Categories',
    'Brands',
    'Collections',
    'Colors',
    'Sizes',
    'Vendors',
    'Suppliers',
    'Media',
    'Shipping',
    'PaymentMethods',
    'CheckoutNotices',
    'Expenses',
    'Expense',
    'ExpenseCategories',
    'ExpenseSummary',
    'ExpenseReports',
    'Orders',
    'Users',
    'Coupons',
    'Banners',
    'Settings',
    'Homepage',
    'HeroSlides',
    'Reviews',
    'Newsletter',
    'StoreInfo',
    'HomepageConfig',
    'Policies',
    'ContactMessages',
    'Tracking',
    'Profile',
    'Addresses',
    'Dashboard',
    'StoreInfo',
  ],
  // Stable reference data (categories/brands/settings…) lives longer; short-lived
  // metrics override this per endpoint. Cached data is reused across navigation
  // instead of being re-fetched on every mount.
  keepUnusedDataFor: 300,
  endpoints: () => ({}),
})