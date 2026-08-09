import { configureStore } from '@reduxjs/toolkit'
import authReducer from './slices/authSlice'
import cartReducer from './slices/cartSlice'
import categoriesReducer from './slices/categoriesSlice'
import dashboardReducer from './slices/dashboardSlice'
import ordersReducer from './slices/ordersSlice'
import productsReducer from './slices/productsSlice'
import settingsReducer from './slices/settingsSlice'
import uiReducer from './slices/uiSlice'
import { commerceApi } from './services/commerceApi'
import { adminProductsApi } from './services/adminProductsApi'

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
    getDefaultMiddleware().concat(commerceApi.middleware, adminProductsApi.middleware),
})

export type RootState = ReturnType<typeof store.getState>
export type AppDispatch = typeof store.dispatch
