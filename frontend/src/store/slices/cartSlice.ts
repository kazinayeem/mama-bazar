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
  variantId?: number
  size?: string
  color?: string
  image?: string
  quantity: number
}

type AddToCartPayload =
  | CartProduct
  | {
      product: CartProduct
      variantId?: number
      size?: string
      color?: string
      image?: string
    }

interface CartState {
  items: CartItem[]
}

const CART_STORAGE_KEY = 'mama_bazar_cart'

const loadCartFromStorage = (): CartItem[] => {
  try {
    const stored = localStorage.getItem(CART_STORAGE_KEY)
    if (stored) {
      const parsed = JSON.parse(stored)
      if (Array.isArray(parsed)) return parsed
    }
  } catch {
    // Ignore parse errors
  }
  return []
}

const saveCartToStorage = (items: CartItem[]) => {
  try {
    localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(items))
  } catch {
    // Ignore storage errors
  }
}

const initialState: CartState = {
  items: loadCartFromStorage(),
}

const buildCartKey = (productId: number, variantId?: number, size?: string, color?: string) => `${productId}::${variantId || ''}::${size || ''}::${color || ''}`

const cartSlice = createSlice({
  name: 'cart',
  initialState,
  reducers: {
    addToCart: (state, action: PayloadAction<AddToCartPayload>) => {
      const payload = 'product' in action.payload ? action.payload : { product: action.payload }
      const key = buildCartKey(payload.product.id, payload.variantId, payload.size, payload.color)
      const existing = state.items.find((item) => item.key === key)
      if (existing) {
        existing.quantity += 1
        saveCartToStorage(state.items)
        return
      }
      state.items.push({
        key,
        product: payload.product,
        variantId: payload.variantId,
        size: payload.size,
        color: payload.color,
        image: payload.image,
        quantity: 1,
      })
      saveCartToStorage(state.items)
    },
    updateQuantity: (state, action: PayloadAction<{ key: string; quantity: number }>) => {
      const item = state.items.find((entry) => entry.key === action.payload.key)
      if (!item) return
      item.quantity = Math.max(1, action.payload.quantity)
      saveCartToStorage(state.items)
    },
    removeFromCart: (state, action: PayloadAction<string>) => {
      state.items = state.items.filter((item) => item.key !== action.payload)
      saveCartToStorage(state.items)
    },
    clearCart: (state) => {
      state.items = []
      saveCartToStorage(state.items)
    },
  },
})

export const { addToCart, updateQuantity, removeFromCart, clearCart } = cartSlice.actions
export default cartSlice.reducer
