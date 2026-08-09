import { createSlice, type PayloadAction } from '@reduxjs/toolkit'

export interface CartProduct {
  id: number
  title: string
  slug: string
  brand?: string
  price: number | string
  discount?: number | string | null
  images: string[]
  stock?: number
}

export interface CartItem {
  key: string
  product: CartProduct
  size?: string
  color?: string
  image?: string
  quantity: number
}

type AddToCartPayload =
  | CartProduct
  | {
      product: CartProduct
      size?: string
      color?: string
      image?: string
    }

interface CartState {
  items: CartItem[]
}

const initialState: CartState = {
  items: [],
}

const buildCartKey = (productId: number, size?: string, color?: string) => `${productId}::${size || ''}::${color || ''}`

const cartSlice = createSlice({
  name: 'cart',
  initialState,
  reducers: {
    addToCart: (state, action: PayloadAction<AddToCartPayload>) => {
      const payload = 'product' in action.payload ? action.payload : { product: action.payload }
      const key = buildCartKey(payload.product.id, payload.size, payload.color)
      const existing = state.items.find((item) => item.key === key)
      if (existing) {
        existing.quantity += 1
        return
      }
      state.items.push({
        key,
        product: payload.product,
        size: payload.size,
        color: payload.color,
        image: payload.image,
        quantity: 1,
      })
    },
    updateQuantity: (state, action: PayloadAction<{ key: string; quantity: number }>) => {
      const item = state.items.find((entry) => entry.key === action.payload.key)
      if (!item) return
      item.quantity = Math.max(1, action.payload.quantity)
    },
    removeFromCart: (state, action: PayloadAction<string>) => {
      state.items = state.items.filter((item) => item.key !== action.payload)
    },
    clearCart: (state) => {
      state.items = []
    },
  },
})

export const { addToCart, updateQuantity, removeFromCart, clearCart } = cartSlice.actions
export default cartSlice.reducer
