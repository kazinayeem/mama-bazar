import { createAsyncThunk, createSlice } from '@reduxjs/toolkit'
import { api } from '../../lib/api'
import type { Category } from '../../types'

interface CategoriesState {
  items: Category[]
  loading: boolean
}

const initialState: CategoriesState = {
  items: [],
  loading: false,
}

export const fetchCategories = createAsyncThunk('categories/fetchCategories', async () => {
  return api.getCategories()
})

const categoriesSlice = createSlice({
  name: 'categories',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchCategories.pending, (state) => {
        state.loading = true
      })
      .addCase(fetchCategories.fulfilled, (state, action) => {
        state.loading = false
        state.items = action.payload
      })
      .addCase(fetchCategories.rejected, (state) => {
        state.loading = false
      })
  },
})

export default categoriesSlice.reducer
