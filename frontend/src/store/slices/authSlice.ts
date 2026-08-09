import { createAsyncThunk, createSlice } from '@reduxjs/toolkit'
import { api } from '../../lib/api'
import { authStorage } from '../../lib/authStorage'
import type { AuthCredentials, AuthRegisterInput, AuthResponse, AuthUser, DevLoginRole, UserOrderWithItems } from '../../types'

interface AuthState {
  token: string | null
  user: AuthUser | null
  loading: boolean
  ordersLoading: boolean
  userOrders: UserOrderWithItems[]
  profileFetchedAt: number | null
  ordersFetchedAt: number | null
  error: string | null
}

type FetchOptions = {
  force?: boolean
}

const DASHBOARD_CACHE_TTL_MS = 2 * 60 * 1000

const initialState: AuthState = {
  token: authStorage.getToken(),
  user: authStorage.getUser<AuthUser>(),
  loading: false,
  ordersLoading: false,
  userOrders: [],
  profileFetchedAt: null,
  ordersFetchedAt: null,
  error: null,
}

export const loginUser = createAsyncThunk('auth/loginUser', async (payload: AuthCredentials) => {
  return api.login(payload)
})

export const loginAsDev = createAsyncThunk('auth/loginAsDev', async (role: DevLoginRole) => {
  return api.devLogin(role)
})

export const registerUser = createAsyncThunk('auth/registerUser', async (payload: AuthRegisterInput) => {
  await api.register(payload)
  return api.login({ phone: payload.phone, password: payload.password })
})

export const fetchMyProfile = createAsyncThunk<AuthUser, FetchOptions | undefined, { state: { auth: AuthState } }>(
  'auth/fetchMyProfile',
  async () => {
    return api.getMyProfile()
  },
  {
    condition: (options, { getState }) => {
      if (options?.force) return true
      const state = getState().auth
      if (!state.user || !state.profileFetchedAt) return true
      return Date.now() - state.profileFetchedAt > DASHBOARD_CACHE_TTL_MS
    },
  },
)

export const fetchMyOrders = createAsyncThunk<UserOrderWithItems[], FetchOptions | undefined, { state: { auth: AuthState } }>(
  'auth/fetchMyOrders',
  async () => {
    return api.getMyOrders()
  },
  {
    condition: (options, { getState }) => {
      if (options?.force) return true
      const state = getState().auth
      if (!state.ordersFetchedAt) return true
      return Date.now() - state.ordersFetchedAt > DASHBOARD_CACHE_TTL_MS
    },
  },
)

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    setAuthSession: (state, action: { payload: AuthResponse }) => {
      state.token = action.payload.token
      state.user = action.payload.user
      state.error = null
      state.profileFetchedAt = Date.now()
      authStorage.setToken(action.payload.token)
      authStorage.setUser(action.payload.user)
    },
    logout: (state) => {
      state.token = null
      state.user = null
      state.userOrders = []
      state.profileFetchedAt = null
      state.ordersFetchedAt = null
      state.error = null
      authStorage.clearToken()
      authStorage.clearUser()
    },
    clearAuthError: (state) => {
      state.error = null
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(loginUser.pending, (state) => {
        state.loading = true
        state.error = null
      })
      .addCase(loginUser.fulfilled, (state, action) => {
        state.loading = false
        state.token = action.payload.token
        state.user = action.payload.user
        state.profileFetchedAt = Date.now()
        authStorage.setToken(action.payload.token)
        authStorage.setUser(action.payload.user)
      })
      .addCase(loginUser.rejected, (state, action) => {
        state.loading = false
        state.error = action.error.message || 'Login failed'
      })
      .addCase(loginAsDev.pending, (state) => {
        state.loading = true
        state.error = null
      })
      .addCase(loginAsDev.fulfilled, (state, action) => {
        state.loading = false
        state.token = action.payload.token
        state.user = action.payload.user
        state.profileFetchedAt = Date.now()
        authStorage.setToken(action.payload.token)
        authStorage.setUser(action.payload.user)
      })
      .addCase(loginAsDev.rejected, (state, action) => {
        state.loading = false
        state.error = action.error.message || 'Development login is unavailable'
      })
      .addCase(registerUser.pending, (state) => {
        state.loading = true
        state.error = null
      })
      .addCase(registerUser.fulfilled, (state, action) => {
        state.loading = false
        state.token = action.payload.token
        state.user = action.payload.user
        state.profileFetchedAt = Date.now()
        authStorage.setToken(action.payload.token)
        authStorage.setUser(action.payload.user)
      })
      .addCase(registerUser.rejected, (state, action) => {
        state.loading = false
        state.error = action.error.message || 'Registration failed'
      })
      .addCase(fetchMyProfile.fulfilled, (state, action) => {
        state.user = action.payload
        state.profileFetchedAt = Date.now()
        authStorage.setUser(action.payload)
      })
      .addCase(fetchMyProfile.rejected, (state) => {
        state.token = null
        state.user = null
        state.profileFetchedAt = null
        authStorage.clearToken()
        authStorage.clearUser()
      })
      .addCase(fetchMyOrders.pending, (state) => {
        state.ordersLoading = true
      })
      .addCase(fetchMyOrders.fulfilled, (state, action) => {
        state.ordersLoading = false
        state.userOrders = action.payload
        state.ordersFetchedAt = Date.now()
      })
      .addCase(fetchMyOrders.rejected, (state) => {
        state.ordersLoading = false
      })
  },
})

export const { setAuthSession, logout, clearAuthError } = authSlice.actions
export default authSlice.reducer
