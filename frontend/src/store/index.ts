import { configureStore, createListenerMiddleware } from '@reduxjs/toolkit'
import authReducer, { logout, setAuthOrders, setAuthOrdersLoading, setAuthUser } from './slices/authSlice'
import cartReducer, { addToCart } from './slices/cartSlice'
import ordersReducer from './slices/ordersSlice'
import uiReducer from './slices/uiSlice'
import { baseApi } from './services/api'
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

// Keep the auth slice in sync with the RTK Query profile/orders caches so that
// components reading `state.auth.user` / `state.auth.userOrders` keep working
// while the data itself is owned by the centralized API cache.
listenerMiddleware.startListening({
  matcher: commerceApi.endpoints.getCurrentUser.matchFulfilled,
  effect: (action, listenerApi) => {
    listenerApi.dispatch(setAuthUser(action.payload))
  },
})

listenerMiddleware.startListening({
  matcher: commerceApi.endpoints.getCurrentUser.matchRejected,
  effect: (action, listenerApi) => {
    if (action.error?.status === 401 || action.error?.status === 403) {
      listenerApi.dispatch(logout())
    }
  },
})

listenerMiddleware.startListening({
  matcher: commerceApi.endpoints.getMyOrders.matchPending,
  effect: (_action, listenerApi) => {
    listenerApi.dispatch(setAuthOrdersLoading(true))
  },
})

listenerMiddleware.startListening({
  matcher: commerceApi.endpoints.getMyOrders.matchFulfilled,
  effect: (action, listenerApi) => {
    listenerApi.dispatch(setAuthOrders(action.payload))
    listenerApi.dispatch(setAuthOrdersLoading(false))
  },
})

listenerMiddleware.startListening({
  matcher: commerceApi.endpoints.getMyOrders.matchRejected,
  effect: (_action, listenerApi) => {
    listenerApi.dispatch(setAuthOrdersLoading(false))
  },
})

// Wipe every cached query when the user logs out so no stale data from a
// previous session leaks into the next one.
listenerMiddleware.startListening({
  actionCreator: logout,
  effect: (_action, listenerApi) => {
    listenerApi.dispatch(baseApi.util.resetApiState())
  },
})

export const store = configureStore({
  reducer: {
    [baseApi.reducerPath]: baseApi.reducer,
    auth: authReducer,
    cart: cartReducer,
    orders: ordersReducer,
    ui: uiReducer,
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().prepend(listenerMiddleware.middleware).concat(baseApi.middleware),
})

// Ensure both endpoint groups are registered on the base API.
void commerceApi
void adminProductsApi

export type RootState = ReturnType<typeof store.getState>
export type AppDispatch = typeof store.dispatch