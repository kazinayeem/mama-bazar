import { createAsyncThunk, createSlice } from '@reduxjs/toolkit'
import { api } from '../../lib/api'
import type { CheckoutInput, Order } from '../../types'

interface OrdersState {
  recent: Order[]
  page: number
  limit: number
  total: number
  totalPages: number
  creating: boolean
  loading: boolean
  checkoutMessage: string | null
}

const initialState: OrdersState = {
  recent: [],
  page: 1,
  limit: 5,
  total: 0,
  totalPages: 1,
  creating: false,
  loading: false,
  checkoutMessage: null,
}

export const placeOrder = createAsyncThunk('orders/placeOrder', async (payload: CheckoutInput) => {
  return api.createOrder(payload)
})

export const fetchRecentOrders = createAsyncThunk(
  'orders/fetchRecentOrders',
  async (params?: { page?: number; limit?: number; status?: string }) => {
    return api.getRecentOrders(params)
  },
)

const ordersSlice = createSlice({
  name: 'orders',
  initialState,
  reducers: {
    clearCheckoutMessage: (state) => {
      state.checkoutMessage = null
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(placeOrder.pending, (state) => {
        state.creating = true
        state.checkoutMessage = null
      })
      .addCase(placeOrder.fulfilled, (state, action) => {
        state.creating = false
        state.checkoutMessage = action.payload.message
      })
      .addCase(placeOrder.rejected, (state) => {
        state.creating = false
        state.checkoutMessage = 'Failed to place order'
      })
      .addCase(fetchRecentOrders.pending, (state) => {
        state.loading = true
      })
      .addCase(fetchRecentOrders.fulfilled, (state, action) => {
        state.loading = false
        state.recent = action.payload.data
        state.page = action.payload.page
        state.limit = action.payload.limit
        state.total = action.payload.total
        state.totalPages = action.payload.totalPages
      })
      .addCase(fetchRecentOrders.rejected, (state) => {
        state.loading = false
      })
  },
})

export const { clearCheckoutMessage } = ordersSlice.actions
export default ordersSlice.reducer
