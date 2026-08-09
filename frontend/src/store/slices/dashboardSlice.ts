import { createAsyncThunk, createSlice } from '@reduxjs/toolkit'
import { api } from '../../lib/api'

interface DashboardState {
  revenue: number
  activeOrders: number
  conversionRate: number
  marketShare: number
  loading: boolean
}

const initialState: DashboardState = {
  revenue: 0,
  activeOrders: 0,
  conversionRate: 0,
  marketShare: 0,
  loading: false,
}

export const fetchDashboardStats = createAsyncThunk('dashboard/fetchDashboardStats', async () => {
  return api.getAdminStats()
})

const dashboardSlice = createSlice({
  name: 'dashboard',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchDashboardStats.pending, (state) => {
        state.loading = true
      })
      .addCase(fetchDashboardStats.fulfilled, (state, action) => {
        state.loading = false
        state.revenue = action.payload.revenue
        state.activeOrders = action.payload.activeOrders
        state.conversionRate = action.payload.conversionRate
        state.marketShare = action.payload.marketShare
      })
      .addCase(fetchDashboardStats.rejected, (state) => {
        state.loading = false
      })
  },
})

export default dashboardSlice.reducer
