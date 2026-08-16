import { configureStore, createListenerMiddleware, isAnyOf } from '@reduxjs/toolkit'
import authReducer, { logout, setAuthOrders, setAuthOrdersLoading, setAuthUser } from './slices/authSlice'
import cartReducer, { addToCart } from './slices/cartSlice'
import ordersReducer from './slices/ordersSlice'
import uiReducer from './slices/uiSlice'
import { baseApi } from './services/api'
// Side-effect imports: register all endpoints on baseApi before the store boots.
import './services/commerceApi'
import './services/adminProductsApi'
import { commerceApi } from './services/commerceApi'
import { trackAddToCart } from '../lib/pixel'
import type { AuthUser, UserOrderWithItems } from '../types'

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
    listenerApi.dispatch(setAuthUser(action.payload as AuthUser | null))
  },
})

listenerMiddleware.startListening({
  matcher: commerceApi.endpoints.getCurrentUser.matchRejected,
  effect: (action, listenerApi) => {
    const status = (action.payload as { status?: number } | undefined)?.status
    if (status === 401 || status === 403) {
      listenerApi.dispatch(logout())
    }
  },
})

listenerMiddleware.startListening({
  matcher: isAnyOf(
    commerceApi.endpoints.getMyOrders.matchPending,
    commerceApi.endpoints.getMyOrders.matchFulfilled,
    commerceApi.endpoints.getMyOrders.matchRejected,
  ),
  effect: (action, listenerApi) => {
    if (commerceApi.endpoints.getMyOrders.matchPending(action)) {
      listenerApi.dispatch(setAuthOrdersLoading(true))
      return
    }
    if (commerceApi.endpoints.getMyOrders.matchFulfilled(action)) {
      listenerApi.dispatch(setAuthOrders(action.payload as UserOrderWithItems[]))
      listenerApi.dispatch(setAuthOrdersLoading(false))
      return
    }
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

export type RootState = ReturnType<typeof store.getState>
export type AppDispatch = typeof store.dispatch