import { createAsyncThunk, createSlice } from '@reduxjs/toolkit'
import { api } from '../../lib/api'
import type { Product } from '../../types'

interface ProductsState {
  items: Product[]
  featured: Product[]
  selected: Product | null
  related: Product[]
  page: number
  limit: number
  total: number
  totalPages: number
  loading: boolean
  error: string | null
}

const initialState: ProductsState = {
  items: [],
  featured: [],
  selected: null,
  related: [],
  page: 1,
  limit: 12,
  total: 0,
  totalPages: 1,
  loading: false,
  error: null,
}

export const fetchProducts = createAsyncThunk(
  'products/fetchProducts',
  async (params?: { page?: number; limit?: number; category?: string; search?: string; sort?: string }) => {
    return api.getProducts(params)
  },
)

export const fetchProductBySlug = createAsyncThunk('products/fetchProductBySlug', async (slug: string) => {
  return api.getProductBySlug(slug)
})

export const fetchRelatedProducts = createAsyncThunk('products/fetchRelatedProducts', async (productId: number) => {
  return api.getRelatedProducts(productId)
})

const productsSlice = createSlice({
  name: 'products',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchProducts.pending, (state) => {
        state.loading = true
        state.error = null
      })
      .addCase(fetchProducts.fulfilled, (state, action) => {
        state.loading = false
        state.items = action.payload.data
        state.featured = action.payload.data.slice(0, 8)
        state.page = action.payload.page
        state.limit = action.payload.limit
        state.total = action.payload.total
        state.totalPages = action.payload.totalPages
      })
      .addCase(fetchProducts.rejected, (state, action) => {
        state.loading = false
        state.error = action.error.message || 'Failed to load products'
      })
      .addCase(fetchProductBySlug.pending, (state) => {
        state.loading = true
        state.error = null
      })
      .addCase(fetchProductBySlug.fulfilled, (state, action) => {
        state.loading = false
        state.selected = action.payload
      })
      .addCase(fetchProductBySlug.rejected, (state, action) => {
        state.loading = false
        state.error = action.error.message || 'Failed to load product'
      })
      .addCase(fetchRelatedProducts.fulfilled, (state, action) => {
        state.related = action.payload
      })
  },
})

export default productsSlice.reducer
