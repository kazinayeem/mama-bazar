import { authStorage } from './authStorage'
import { resolveUrl } from './apiConfig'
import type {
  AdminCoupon,
  ApiListResult,
  AuthCredentials,
  OrderCreateResponse,
  AuthRegisterInput,
  AuthResponse,
  AuthUser,
  Category,
  CheckoutInput,
  CheckoutNotice,
  DevLoginRole,
  Order,
  PolicyPage,
  OrderStatus,
  PaymentMethodInfo,
  Product,
  ShippingMethod,
  UserAddress,
  UserOrderItem,
  UserOrderWithItems,
} from '../types'

const NETWORK_ERROR_MESSAGE = 'Unable to connect to the server. Please try again.'

type ApiEnvelope<T> = {
  success: boolean
  data?: T
  pagination?: {
    page: number
    limit: number
    total: number
    totalPages: number
  }
  message?: string
}

const parseEnvelope = <T>(raw: string): ApiEnvelope<T> | null => {
  try {
    return JSON.parse(raw) as ApiEnvelope<T>
  } catch {
    return null
  }
}

const requestEnvelope = async <T>(path: string, init?: RequestInit, withAuth = false): Promise<ApiEnvelope<T>> => {
  const controller = new AbortController()
  const timeout = setTimeout(() => controller.abort(), 6000)
  const token = authStorage.getToken()

  try {
    const response = await fetch(resolveUrl(path), {
      ...init,
      headers: {
        'Content-Type': 'application/json',
        ...(withAuth && token ? { Authorization: `Bearer ${token}` } : {}),
        ...(init?.headers || {}),
      },
      signal: controller.signal,
    })

    const rawText = await response.text()
    const data = parseEnvelope<T>(rawText)

    if (!data) {
      throw new Error(NETWORK_ERROR_MESSAGE)
    }

    if (!response.ok || !data.success) {
      throw new Error(data.message || `Request failed (${response.status})`)
    }

    return data
  } catch (err) {
    if (err instanceof TypeError || (err as DOMException)?.name === 'AbortError') {
      throw new Error(NETWORK_ERROR_MESSAGE)
    }
    throw err
  } finally {
    clearTimeout(timeout)
  }
}

const fetchJson = async <T>(path: string, init?: RequestInit, withAuth = false): Promise<T> => {
  const envelope = await requestEnvelope<T>(path, init, withAuth)
  return envelope.data as T
}

const buildQuery = (params: Record<string, string | number | undefined>) => {
  const query = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === '') return
    query.set(key, String(value))
  })
  const serialized = query.toString()
  return serialized ? `?${serialized}` : ''
}

export const api = {
  async getProducts(params?: {
    page?: number
    limit?: number
    category?: string
    search?: string
    sort?: string
    minPrice?: number
    maxPrice?: number
  }): Promise<ApiListResult<Product>> {
    const query = buildQuery({
      page: params?.page,
      limit: params?.limit,
      category: params?.category,
      search: params?.search,
      sort: params?.sort,
      minPrice: params?.minPrice,
      maxPrice: params?.maxPrice,
    })

    const response = await requestEnvelope<Product[]>(`/api/products${query}`)
    const list = response.data || []
    const pagination = response.pagination
    return {
      data: list,
      total: pagination?.total || list.length,
      page: pagination?.page || params?.page || 1,
      limit: pagination?.limit || params?.limit || 12,
      totalPages: pagination?.totalPages || 1,
    }
  },

  async getProductBySlug(slug: string): Promise<Product | null> {
    return await fetchJson<Product>(`/api/products/slug/${slug}`)
  },

  async getRelatedProducts(productId: number): Promise<Product[]> {
    return await fetchJson<Product[]>(`/api/products/${productId}/related`)
  },

  async getCategories(): Promise<Category[]> {
    return await fetchJson<Category[]>('/api/categories')
  },

  async getHeroSlides(): Promise<string[]> {
    return await fetchJson<string[]>('/api/settings/hero-slides')
  },

  async createOrder(payload: CheckoutInput): Promise<OrderCreateResponse> {
    const response = await requestEnvelope<{ order?: Order; auth?: AuthResponse }>(
      '/api/order/create',
      {
        method: 'POST',
        body: JSON.stringify(payload),
      },
      true,
    )

    return {
      orderId: response.data?.order?.orderId,
      order: response.data?.order,
      auth: response.data?.auth,
      message: response.message || 'Order placed successfully',
    }
  },

  async getShippingMethods(): Promise<ShippingMethod[]> {
    return await fetchJson<ShippingMethod[]>('/api/shipping-methods/public')
  },

  async estimateShipping(subtotal: number): Promise<ShippingMethod[]> {
    return await fetchJson<ShippingMethod[]>('/api/shipping-methods/estimate', {
      method: 'POST',
      body: JSON.stringify({ subtotal }),
    })
  },

  async getPaymentMethods(): Promise<PaymentMethodInfo[]> {
    return await fetchJson<PaymentMethodInfo[]>('/api/payment-methods/public')
  },

  async getCheckoutNotices(): Promise<CheckoutNotice[]> {
    return await fetchJson<CheckoutNotice[]>('/api/checkout-notices/public')
  },

  async validateCoupon(code: string, subtotal: number): Promise<{ discount: number; discountType: string; discountValue: string }> {
    const response = await requestEnvelope<{ discount: number; discountType: string; discountValue: string }>(
      '/api/coupons/validate',
      {
        method: 'POST',
        body: JSON.stringify({ code, subtotal }),
      },
    )
    if (!response.data) throw new Error(response.message || 'Invalid coupon')
    return response.data
  },

  async uploadPaymentProof(file: File): Promise<{ url: string; provider?: string }> {
    const formData = new FormData()
    formData.append('file', file)
    const response = await fetch(resolveUrl('/api/uploads/payment-proof'), {
      method: 'POST',
      body: formData,
    })
    const data = (await response.json()) as ApiEnvelope<{ url: string; provider?: string }>
    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Upload failed')
    }
    return data.data || { url: '' }
  },

  async trackOrder(orderId: string, phone: string): Promise<Order & { items?: UserOrderItem[] }> {
    return await fetchJson('/api/order/track', {
      method: 'POST',
      body: JSON.stringify({ orderId, phone }),
    })
  },

  async getAdminStats(): Promise<{ revenue: number; activeOrders: number; conversionRate: number; marketShare: number }> {
    const stats = await fetchJson<{ totalRevenue?: number; pendingOrders?: number; totalOrders?: number; conversionRate?: number; marketShare?: number }>('/api/order/stats')
    return {
      revenue: Number(stats?.totalRevenue || 0),
      activeOrders: Number(stats?.pendingOrders || stats?.totalOrders || 0),
      conversionRate: Number(stats?.conversionRate || 0),
      marketShare: Number(stats?.marketShare || 0),
    }
  },

  async getRecentOrders(params?: { page?: number; limit?: number; status?: string }): Promise<ApiListResult<Order>> {
    const query = buildQuery({
      page: params?.page || 1,
      limit: params?.limit || 5,
      status: params?.status,
    })
    const response = await requestEnvelope<Order[]>(`/api/order${query}`, undefined, true)
    const list = response.data || []
    return {
      data: list,
      total: response.pagination?.total || list.length,
      page: response.pagination?.page || 1,
      limit: response.pagination?.limit || 5,
      totalPages: response.pagination?.totalPages || 1,
    }
  },

  async register(payload: AuthRegisterInput): Promise<{ id: number; name: string; phone: string }> {
    return fetchJson('/api/users/register', {
      method: 'POST',
      body: JSON.stringify(payload),
    })
  },

  async login(payload: AuthCredentials): Promise<AuthResponse> {
    return fetchJson('/api/users/login', {
      method: 'POST',
      body: JSON.stringify(payload),
    })
  },

  async devLogin(role: DevLoginRole): Promise<AuthResponse> {
    return fetchJson('/api/users/dev-login', {
      method: 'POST',
      body: JSON.stringify({ role }),
    })
  },

  async requestPasswordReset(phone: string): Promise<{ message?: string }> {
    return fetchJson('/api/users/password-reset-request', {
      method: 'POST',
      body: JSON.stringify({ phone }),
    })
  },

  async getPolicyPage(slug: string): Promise<PolicyPage> {
    return fetchJson(`/api/pages/p/${slug}`)
  },

  async getContactSetting(): Promise<Record<string, string>> {
    const res = await requestEnvelope<Record<string, string> | string>('/api/settings/contact_info')
    const raw = res.data
    if (typeof raw === 'string') {
      try {
        return JSON.parse(raw)
      } catch {
        return {}
      }
    }
    return raw || {}
  },

  async submitContactMessage(payload: { name: string; phone: string; email?: string; message: string }): Promise<{ id?: number }> {
    return fetchJson('/api/pages/contact', {
      method: 'POST',
      body: JSON.stringify(payload),
    })
  },

  async getMyProfile(): Promise<AuthUser> {
    return fetchJson('/api/users/profile', undefined, true)
  },

  async getMyOrders(): Promise<UserOrderWithItems[]> {
    return fetchJson('/api/users/orders', undefined, true)
  },

  async getMyAddresses(): Promise<UserAddress[]> {
    try {
      return await fetchJson('/api/users/addresses', undefined, true)
    } catch (error) {
      const message = error instanceof Error ? error.message : ''
      if (/Cannot GET \/api\/users\/addresses|not found/i.test(message)) {
        return []
      }
      throw error
    }
  },

  async createMyAddress(payload: {
    recipientName: string
    phone: string
    shippingArea: string
    address: string
    isDefault?: boolean
  }): Promise<UserAddress[]> {
    return fetchJson('/api/users/addresses', {
      method: 'POST',
      body: JSON.stringify(payload),
    }, true)
  },

  async updateMyAddress(
    id: number,
    payload: {
      recipientName?: string
      phone?: string
      shippingArea?: string
      address?: string
      isDefault?: boolean
    },
  ): Promise<UserAddress[]> {
    return fetchJson(`/api/users/addresses/${id}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    }, true)
  },

  async deleteMyAddress(id: number): Promise<UserAddress[]> {
    return fetchJson(`/api/users/addresses/${id}`, {
      method: 'DELETE',
    }, true)
  },

  async updateMyProfile(payload: {
    name?: string
    phone?: string
    shippingArea?: string
    shippingAddress?: string
  }): Promise<AuthUser> {
    return fetchJson('/api/users/profile', {
      method: 'PUT',
      body: JSON.stringify(payload),
    }, true)
  },

  async changeMyPassword(payload: { oldPassword: string; newPassword: string }): Promise<{ success: boolean; message: string }> {
    const response = await requestEnvelope('/api/users/change-password', {
      method: 'POST',
      body: JSON.stringify(payload),
    }, true)

    return {
      success: response.success,
      message: response.message || 'Password changed successfully',
    }
  },

  async getUsersAdmin(): Promise<AuthUser[]> {
    return fetchJson('/api/users', undefined, true)
  },

  async deleteUserAdmin(id: number): Promise<void> {
    await requestEnvelope(`/api/users/${id}`, { method: 'DELETE' }, true)
  },

  async createProduct(payload: {
    title: string
    description?: string
    price: number
    discount?: number
    categoryId?: number
    stock?: number
    sizeOptions?: string[]
    colorOptions?: Array<{ name: string; value?: string; image?: string }>
    status?: 'active' | 'inactive'
  }): Promise<Product> {
    return fetchJson<Product>(
      '/api/products',
      {
        method: 'POST',
        body: JSON.stringify(payload),
      },
      true,
    )
  },

  async updateProduct(
    id: number,
    payload: {
      title?: string
      description?: string
      price?: number
      discount?: number
      categoryId?: number
      stock?: number
      sizeOptions?: string[]
      colorOptions?: Array<{ name: string; value?: string; image?: string }>
      status?: 'active' | 'inactive'
    },
  ): Promise<Product> {
    return fetchJson<Product>(
      `/api/products/${id}`,
      {
        method: 'PUT',
        body: JSON.stringify(payload),
      },
      true,
    )
  },

  async deleteProduct(id: number): Promise<void> {
    await requestEnvelope(`/api/products/${id}`, { method: 'DELETE' }, true)
  },

  async createCategory(payload: { name: string; description?: string }): Promise<Category> {
    return fetchJson<Category>(
      '/api/categories',
      {
        method: 'POST',
        body: JSON.stringify(payload),
      },
      true,
    )
  },

  async updateCategory(id: number, payload: { name?: string; description?: string }): Promise<Category> {
    return fetchJson<Category>(
      `/api/categories/${id}`,
      {
        method: 'PUT',
        body: JSON.stringify(payload),
      },
      true,
    )
  },

  async deleteCategory(id: number): Promise<void> {
    await requestEnvelope(`/api/categories/${id}`, { method: 'DELETE' }, true)
  },

  async getCoupons(): Promise<AdminCoupon[]> {
    return fetchJson<AdminCoupon[]>('/api/coupons', undefined, true)
  },

  async createCoupon(payload: {
    code: string
    discountType: 'percentage' | 'fixed'
    discountValue: number
    minOrderAmount?: number
    expiryDate?: string
    status?: 'active' | 'inactive'
  }): Promise<AdminCoupon> {
    return fetchJson<AdminCoupon>(
      '/api/coupons',
      {
        method: 'POST',
        body: JSON.stringify(payload),
      },
      true,
    )
  },

  async updateCoupon(
    id: number,
    payload: {
      code?: string
      discountType?: 'percentage' | 'fixed'
      discountValue?: number
      minOrderAmount?: number
      expiryDate?: string
      status?: 'active' | 'inactive'
    },
  ): Promise<AdminCoupon> {
    return fetchJson<AdminCoupon>(
      `/api/coupons/${id}`,
      {
        method: 'PUT',
        body: JSON.stringify(payload),
      },
      true,
    )
  },

  async deleteCoupon(id: number): Promise<void> {
    await requestEnvelope(`/api/coupons/${id}`, { method: 'DELETE' }, true)
  },

  async getOrderById(id: number): Promise<Order & { items?: Array<{ id: number; quantity: number; price: string; product?: { title?: string } | null }> }> {
    return fetchJson(`/api/order/${id}`, undefined, true)
  },

  async updateOrderStatus(
    id: number,
    status: OrderStatus,
    note?: string,
    trackingNumber?: string,
  ): Promise<Order> {
    return fetchJson<Order>(
      `/api/order/${id}/status`,
      {
        method: 'PATCH',
        body: JSON.stringify({ status, note, trackingNumber }),
      },
      true,
    )
  },

  async verifyPayment(id: number, action: 'verified' | 'rejected', note?: string): Promise<Order> {
    return fetchJson<Order>(
      `/api/order/${id}/payment/verify`,
      {
        method: 'PATCH',
        body: JSON.stringify({ action, note }),
      },
      true,
    )
  },

  async addOrderAdminNote(id: number, note: string): Promise<Order> {
    return fetchJson<Order>(
      `/api/order/${id}/admin-note`,
      {
        method: 'PATCH',
        body: JSON.stringify({ note }),
      },
      true,
    )
  },

  async getOrderInvoice(id: number): Promise<Order & { items?: UserOrderItem[] }> {
    return fetchJson(`/api/order/${id}/invoice`, undefined, true)
  },

  async getMyOrdersApi(): Promise<UserOrderWithItems[]> {
    return fetchJson('/api/order/my-orders', undefined, true)
  },

  async deleteOrder(id: number): Promise<void> {
    await requestEnvelope(`/api/order/${id}`, { method: 'DELETE' }, true)
  },

  async addHeroSlideByLink(link: string): Promise<string[]> {
    return fetchJson<string[]>(
      '/api/settings/hero-slides/link',
      {
        method: 'POST',
        body: JSON.stringify({ link }),
      },
      true,
    )
  },

  async addHeroSlideByUpload(file: File): Promise<string[]> {
    const formData = new FormData()
    formData.append('image', file)
    const response = await fetch(resolveUrl('/api/settings/hero-slides'), {
      method: 'POST',
      headers: {
        ...(authStorage.getToken() ? { Authorization: `Bearer ${authStorage.getToken()}` } : {}),
      },
      body: formData,
    })
    const data = (await response.json()) as ApiEnvelope<string[]>
    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Request failed')
    }
    return data.data || []
  },

  async deleteHeroSlide(index: number): Promise<string[]> {
    return fetchJson<string[]>(`/api/settings/hero-slides/${index}`, { method: 'DELETE' }, true)
  },

  async getSettings(): Promise<Array<{ id: number; key: string; value: string }>> {
    return fetchJson('/api/settings')
  },

  async getTaxSettings(): Promise<{ taxRate: number; applyTaxToShipping: boolean }> {
    try {
      const settings = await this.getSettings()
      const raw = settings.find((s) => s.key === 'tax_settings')?.value
      if (raw) {
        const parsed = JSON.parse(raw) as { taxRate?: number; applyTaxToShipping?: boolean }
        return {
          taxRate: Math.max(0, Number(parsed.taxRate) || 0),
          applyTaxToShipping: Boolean(parsed.applyTaxToShipping),
        }
      }
    } catch {
      // fall through to defaults
    }
    return { taxRate: 0, applyTaxToShipping: false }
  },

  async setSetting(key: string, value: unknown): Promise<{ id: number; key: string; value: string }> {
    return fetchJson(
      '/api/settings',
      {
        method: 'PUT',
        body: JSON.stringify({ key, value }),
      },
      true,
    )
  },
}
