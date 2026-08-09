import { createSlice, type PayloadAction } from '@reduxjs/toolkit'

export type ThemeMode = 'light' | 'dark'

interface UiState {
  theme: ThemeMode
  cartOpen: boolean
  wishlist: number[]
  compare: number[]
}

const initialState: UiState = {
  theme: 'light',
  cartOpen: false,
  wishlist: [],
  compare: [],
}

const uiSlice = createSlice({
  name: 'ui',
  initialState,
  reducers: {
    setTheme: (state, action: PayloadAction<ThemeMode>) => {
      state.theme = action.payload
    },
    toggleTheme: (state) => {
      state.theme = state.theme === 'light' ? 'dark' : 'light'
    },
    openCart: (state) => {
      state.cartOpen = true
    },
    closeCart: (state) => {
      state.cartOpen = false
    },
    toggleWishlist: (state, action: PayloadAction<number>) => {
      const id = action.payload
      if (state.wishlist.includes(id)) {
        state.wishlist = state.wishlist.filter((entry) => entry !== id)
      } else {
        state.wishlist.push(id)
      }
    },
    toggleCompare: (state, action: PayloadAction<number>) => {
      const id = action.payload
      if (state.compare.includes(id)) {
        state.compare = state.compare.filter((entry) => entry !== id)
      } else if (state.compare.length < 4) {
        state.compare.push(id)
      }
    },
  },
})

export const { setTheme, toggleTheme, openCart, closeCart, toggleWishlist, toggleCompare } = uiSlice.actions
export default uiSlice.reducer
