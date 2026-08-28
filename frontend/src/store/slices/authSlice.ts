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
  error: string | null
}

const initialState: AuthState = {
  token: authStorage.getToken(),
  user: authStorage.getUser<AuthUser>(),
  loading: false,
  ordersLoading: false,
  userOrders: [],
  error: null,
}

export const loginUser = createAsyncThunk('auth/loginUser', async (payload: AuthCredentials, { rejectWithValue }) => {
  try {
    return await api.login(payload)
  } catch (err: any) {
    return rejectWithValue(err?.message || 'Invalid phone/email or password.')
  }
})

export const loginAsDev = createAsyncThunk('auth/loginAsDev', async (role: DevLoginRole, { rejectWithValue }) => {
  try {
    return await api.devLogin(role)
  } catch (err: any) {
    return rejectWithValue(err?.message || 'Development login is unavailable')
  }
})

export const registerUser = createAsyncThunk('auth/registerUser', async (payload: AuthRegisterInput, { rejectWithValue }) => {
  try {
    await api.register(payload)
    return await api.login({ phone: payload.phone, password: payload.password })
  } catch (err: any) {
    return rejectWithValue(err?.message || 'Registration failed. Please check your details.')
  }
})

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    setAuthSession: (state, action: { payload: AuthResponse }) => {
      state.token = action.payload.token
      state.user = action.payload.user
      state.error = null
      authStorage.setToken(action.payload.token)
      authStorage.setUser(action.payload.user)
    },
    setAuthUser: (state, action: { payload: AuthUser | null }) => {
      state.user = action.payload
      if (action.payload) authStorage.setUser(action.payload)
      else authStorage.clearUser()
    },
    setAuthOrders: (state, action: { payload: UserOrderWithItems[] }) => {
      state.userOrders = action.payload
    },
    setAuthOrdersLoading: (state, action: { payload: boolean }) => {
      state.ordersLoading = action.payload
    },
    logout: (state) => {
      state.token = null
      state.user = null
      state.userOrders = []
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
        authStorage.setToken(action.payload.token)
        authStorage.setUser(action.payload.user)
      })
      .addCase(loginUser.rejected, (state, action) => {
        state.loading = false
        state.error = (action.payload as string) || action.error.message || 'Login failed'
      })
      .addCase(loginAsDev.pending, (state) => {
        state.loading = true
        state.error = null
      })
      .addCase(loginAsDev.fulfilled, (state, action) => {
        state.loading = false
        state.token = action.payload.token
        state.user = action.payload.user
        authStorage.setToken(action.payload.token)
        authStorage.setUser(action.payload.user)
      })
      .addCase(loginAsDev.rejected, (state, action) => {
        state.loading = false
        state.error = (action.payload as string) || action.error.message || 'Development login is unavailable'
      })
      .addCase(registerUser.pending, (state) => {
        state.loading = true
        state.error = null
      })
      .addCase(registerUser.fulfilled, (state, action) => {
        state.loading = false
        state.token = action.payload.token
        state.user = action.payload.user
        authStorage.setToken(action.payload.token)
        authStorage.setUser(action.payload.user)
      })
      .addCase(registerUser.rejected, (state, action) => {
        state.loading = false
        state.error = (action.payload as string) || action.error.message || 'Registration failed'
      })
  },
})

export const { setAuthSession, setAuthUser, setAuthOrders, setAuthOrdersLoading, logout, clearAuthError } = authSlice.actions
export default authSlice.reducer
