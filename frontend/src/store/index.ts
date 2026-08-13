import { configureStore, createListenerMiddleware } from '@reduxjs/toolkit'
import authReducer from './slices/authSlice'
import cartReducer, { addToCart } from './slices/cartSlice'
import categoriesReducer from './slices/categoriesSlice'
import dashboardReducer from './slices/dashboardSlice'
import ordersReducer from './slices/ordersSlice'
import productsReducer from './slices/productsSlice'
import settingsReducer from './slices/settingsSlice'
import uiReducer from './slices/uiSlice'
import { commerceApi } from './services/commerceApi'
import { adminProductsApi } from './services/adminProductsApi'
import { trackAddToCart } from '../lib/pixel'

const listenerMiddleware = createListenerMiddleware()

listenerMiddleware.startListening({
  actionCreator: addToCart,
  effect: (action) => {
    const payload = 'product' in action.payload ? action.payload : { product: action.payload }
    trackAddToCart({
      title: payload.product.title,
      id: payload.product.id,
      value: Number(payload.product.price),
      quantity: 1,
    })
  },
})

export const store = configureStore({
  reducer: {
    [commerceApi.reducerPath]: commerceApi.reducer,
    [adminProductsApi.reducerPath]: adminProductsApi.reducer,
    auth: authReducer,
    products: productsReducer,
    categories: categoriesReducer,
    cart: cartReducer,
    orders: ordersReducer,
    dashboard: dashboardReducer,
    settings: settingsReducer,
    ui: uiReducer,
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().prepend(listenerMiddleware.middleware).concat(commerceApi.middleware, adminProductsApi.middleware),
})

export type RootState = ReturnType<typeof store.getState>
export type AppDispatch = typeof store.dispatch
