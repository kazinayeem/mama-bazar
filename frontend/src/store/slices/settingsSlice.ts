import { createAsyncThunk, createSlice } from '@reduxjs/toolkit'
import { api } from '../../lib/api'

interface SettingsState {
  heroSlides: string[]
  loading: boolean
}

const initialState: SettingsState = {
  heroSlides: [],
  loading: false,
}

export const fetchHeroSlides = createAsyncThunk('settings/fetchHeroSlides', async () => {
  return api.getHeroSlides()
})

const settingsSlice = createSlice({
  name: 'settings',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchHeroSlides.pending, (state) => {
        state.loading = true
      })
      .addCase(fetchHeroSlides.fulfilled, (state, action) => {
        state.loading = false
        state.heroSlides = action.payload
      })
      .addCase(fetchHeroSlides.rejected, (state) => {
        state.loading = false
      })
  },
})

export default settingsSlice.reducer
