import { authStorage } from './authStorage'
import { resolveUrl } from './apiConfig'
import type {
  AdminCustomer,
  AdminOrder,
  AdminProduct,
  Banner,
  Brand,
  CatalogStatus,
  Collection,
  Color,
  DashboardData,
  MediaAsset,
  ProductInput,
  Size,
  Supplier,
  Vendor,
} from '../types/admin'
import type { AdminCoupon, ApiListResult, AuthUser, Category, ShippingMethod } from '../types'
import type { ContactMessage, PolicyPage } from '../types'
import type { AdminCheckoutNotice, AdminPaymentMethod } from '../types/admin'
import type { HomepageConfig, NewsletterSubscriber } from '../types/homepage'



type Envelope<T> = {
  success: boolean
  data?: T
  pagination?: { page: number; limit: number; total: number; totalPages: number }
  message?: string
}

const token = () => (authStorage.getToken() ? `Bearer ${authStorage.getToken()}` : '')

const buildQuery = (params: Record<string, string | number | undefined>) => {
  const query = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === '' || value === null) return
    query.set(key, String(value))
  })
  const serialized = query.toString()
  return serialized ? `?${serialized}` : ''
}

async function handleResponse<T>(response: Response): Promise<Envelope<T>> {
  const raw = await response.text()
  let data: Envelope<T>
  try {
    data = JSON.parse(raw) as Envelope<T>
  } catch {
    throw new Error('Server returned invalid JSON')
  }
  if (!response.ok || !data.success) {
    throw new Error(data.message || `Request failed (${response.status})`)
  }
  return data
}

const requestJson = async <T>(path: string, init?: RequestInit): Promise<Envelope<T>> => {
  const response = await fetch(resolveUrl(path), {
    ...init,
    headers: {
      'Content-Type': 'application/json',
      Authorization: token(),
      ...(init?.headers || {}),
    },
  })
  return handleResponse<T>(response)
}

const requestForm = async <T>(path: string, formData: FormData): Promise<Envelope<T>> => {
  const response = await fetch(resolveUrl(path), {
    method: 'POST',
    headers: { Authorization: token() },
    body: formData,
  })
  return handleResponse<T>(response)
}

const asList = <T>(env: Envelope<T[]>, page = 1, limit = 20): ApiListResult<T> => ({
  data: env.data || [],
  total: env.pagination?.total || env.data?.length || 0,
  page: env.pagination?.page || page,
  limit: env.pagination?.limit || limit,
  totalPages: env.pagination?.totalPages || 1,
})

export const adminApi = {
  // ==================== Dashboard ====================
  async getDashboard(range?: string): Promise<DashboardData> {
    const env = await requestJson<DashboardData>(`/api/admin/dashboard${buildQuery({ range })}`)
    return env.data!
  },

  // ==================== Products ====================
  async getProducts(params?: {
    page?: number
    limit?: number
    search?: string
    category?: string
    status?: string
    productStatus?: string
    sort?: string
  }): Promise<ApiListResult<AdminProduct>> {
    const env = await requestJson<AdminProduct[]>(
      `/api/products${buildQuery({ page: params?.page, limit: params?.limit, search: params?.search, category: params?.category, status: params?.status, productStatus: params?.productStatus, sort: params?.sort })}`,
    )
    return asList(env, params?.page, params?.limit)
  },

  async getProduct(id: number): Promise<AdminProduct> {
    const env = await requestJson<AdminProduct>(`/api/products/${id}`)
    return env.data!
  },

  async createProduct(payload: ProductInput): Promise<AdminProduct> {
    const env = await requestJson<AdminProduct>('/api/products', { method: 'POST', body: JSON.stringify(payload) })
    return env.data!
  },

  async updateProduct(id: number, payload: Partial<ProductInput>): Promise<AdminProduct> {
    const env = await requestJson<AdminProduct>(`/api/products/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
    return env.data!
  },

  async deleteProduct(id: number): Promise<void> {
    await requestJson(`/api/products/${id}`, { method: 'DELETE' })
  },

  async bulkProductAction(action: 'delete' | 'publish' | 'archive' | 'hide' | 'draft', ids: number[]): Promise<{ affected: number }> {
    const env = await requestJson<{ affected: number }>('/api/products/bulk', {
      method: 'POST',
      body: JSON.stringify({ action, ids }),
    })
    return env.data || { affected: ids.length }
  },

  async duplicateProduct(id: number): Promise<AdminProduct> {
    const env = await requestJson<AdminProduct>(`/api/products/${id}/duplicate`, { method: 'POST' })
    return env.data!
  },

  async exportProductsCsv(params?: { search?: string; category?: string; status?: string }): Promise<string> {
    const env = await requestJson<{ csv?: string }>(
      `/api/products/export/csv${buildQuery({ search: params?.search, category: params?.category, status: params?.status })}`,
    )
    return env.data?.csv || ''
  },

  async importProductsCsv(file: File): Promise<{ imported: number }> {
    const csv = await file.text()
    const env = await requestJson<{ imported?: number }>('/api/products/import/csv', {
      method: 'POST',
      body: JSON.stringify({ csv }),
    })
    return { imported: env.data?.imported ?? (env as { imported?: number }).imported ?? 0 }
  },

  async uploadProductImages(files: File[]): Promise<string[]> {
    if (files.length === 0) return []
    const formData = new FormData()
    formData.append('folder', 'products')
    files.forEach((f) => formData.append('files', f))
    const env = await requestForm<MediaAsset[]>('/api/media/upload/multiple', formData)
    return (env.data || []).map((m) => m.url)
  },

  // ==================== Catalog: Colors ====================
  async getColors(): Promise<Color[]> {
    const env = await requestJson<Color[]>('/api/colors')
    return env.data || []
  },

  async createColor(payload: { name: string; hex: string; status?: CatalogStatus; sortOrder?: number }): Promise<Color> {
    const env = await requestJson<Color>('/api/colors', { method: 'POST', body: JSON.stringify(payload) })
    return env.data!
  },

  async updateColor(id: number, payload: Partial<Color>): Promise<Color> {
    const env = await requestJson<Color>(`/api/colors/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
    return env.data!
  },

  async deleteColor(id: number): Promise<void> {
    await requestJson(`/api/colors/${id}`, { method: 'DELETE' })
  },

  // ==================== Catalog: Sizes ====================
  async getSizes(): Promise<Size[]> {
    const env = await requestJson<Size[]>('/api/sizes')
    return env.data || []
  },

  async createSize(payload: { name: string; status?: CatalogStatus; sortOrder?: number }): Promise<Size> {
    const env = await requestJson<Size>('/api/sizes', { method: 'POST', body: JSON.stringify(payload) })
    return env.data!
  },

  async updateSize(id: number, payload: Partial<Size>): Promise<Size> {
    const env = await requestJson<Size>(`/api/sizes/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
    return env.data!
  },

  async deleteSize(id: number): Promise<void> {
    await requestJson(`/api/sizes/${id}`, { method: 'DELETE' })
  },

  // ==================== Catalog: Collections ====================
  async getCollections(): Promise<Collection[]> {
    const env = await requestJson<Collection[]>('/api/collections')
    return env.data || []
  },

  async createCollection(payload: Partial<Collection> & { name: string }): Promise<Collection> {
    const env = await requestJson<Collection>('/api/collections', { method: 'POST', body: JSON.stringify(payload) })
    return env.data!
  },

  async updateCollection(id: number, payload: Partial<Collection>): Promise<Collection> {
    const env = await requestJson<Collection>(`/api/collections/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
    return env.data!
  },

  async deleteCollection(id: number): Promise<void> {
    await requestJson(`/api/collections/${id}`, { method: 'DELETE' })
  },

  // ==================== Catalog: Vendors ====================
  async getVendors(): Promise<Vendor[]> {
    const env = await requestJson<Vendor[]>('/api/vendors')
    return env.data || []
  },

  async createVendor(payload: Partial<Vendor> & { name: string }): Promise<Vendor> {
    const env = await requestJson<Vendor>('/api/vendors', { method: 'POST', body: JSON.stringify(payload) })
    return env.data!
  },

  async updateVendor(id: number, payload: Partial<Vendor>): Promise<Vendor> {
    const env = await requestJson<Vendor>(`/api/vendors/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
    return env.data!
  },

  async deleteVendor(id: number): Promise<void> {
    await requestJson(`/api/vendors/${id}`, { method: 'DELETE' })
  },

  // ==================== Catalog: Suppliers ====================
  async getSuppliers(): Promise<Supplier[]> {
    const env = await requestJson<Supplier[]>('/api/suppliers')
    return env.data || []
  },

  async createSupplier(payload: Partial<Supplier> & { name: string }): Promise<Supplier> {
    const env = await requestJson<Supplier>('/api/suppliers', { method: 'POST', body: JSON.stringify(payload) })
    return env.data!
  },

  async updateSupplier(id: number, payload: Partial<Supplier>): Promise<Supplier> {
    const env = await requestJson<Supplier>(`/api/suppliers/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
    return env.data!
  },

  async deleteSupplier(id: number): Promise<void> {
    await requestJson(`/api/suppliers/${id}`, { method: 'DELETE' })
  },

  // ==================== Categories ====================
  async getCategories(): Promise<Category[]> {
    const env = await requestJson<Category[]>('/api/categories')
    return env.data || []
  },

  async createCategory(payload: { name: string; description?: string; image?: string }): Promise<Category> {
    const env = await requestJson<Category>('/api/categories', { method: 'POST', body: JSON.stringify(payload) })
    return env.data!
  },

  async updateCategory(id: number, payload: { name?: string; description?: string; image?: string }): Promise<Category> {
    const env = await requestJson<Category>(`/api/categories/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
    return env.data!
  },

  async deleteCategory(id: number): Promise<void> {
    await requestJson(`/api/categories/${id}`, { method: 'DELETE' })
  },

  // ==================== Brands ====================
  async getBrands(): Promise<Brand[]> {
    const env = await requestJson<Brand[]>('/api/brands')
    return env.data || []
  },

  async createBrand(payload: Partial<Brand> & { name: string }): Promise<Brand> {
    const env = await requestJson<Brand>('/api/brands', { method: 'POST', body: JSON.stringify(payload) })
    return env.data!
  },

  async updateBrand(id: number, payload: Partial<Brand>): Promise<Brand> {
    const env = await requestJson<Brand>(`/api/brands/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
    return env.data!
  },

  async deleteBrand(id: number): Promise<void> {
    await requestJson(`/api/brands/${id}`, { method: 'DELETE' })
  },

  async uploadBrandLogo(file: File): Promise<string> {
    const formData = new FormData()
    formData.append('logo', file)
    formData.append('name', 'logo-upload')
    const env = await requestForm<{ logo?: string }>('/api/brands', formData)
    return env.data?.logo || ''
  },

  // ==================== Banners ====================
  async getBanners(): Promise<Banner[]> {
    const env = await requestJson<Banner[]>('/api/banners')
    return env.data || []
  },

  async createBanner(payload: Partial<Banner>): Promise<Banner> {
    const env = await requestJson<Banner>('/api/banners', { method: 'POST', body: JSON.stringify(payload) })
    return env.data!
  },

  async updateBanner(id: number, payload: Partial<Banner>): Promise<Banner> {
    const env = await requestJson<Banner>(`/api/banners/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
    return env.data!
  },

  async deleteBanner(id: number): Promise<void> {
    await requestJson(`/api/banners/${id}`, { method: 'DELETE' })
  },

  async uploadBannerImages(images: { image?: File; imageTablet?: File; imageMobile?: File }): Promise<{
    image?: string
    imageTablet?: string
    imageMobile?: string
  }> {
    const formData = new FormData()
    formData.append('folder', 'banners')
    if (images.image) formData.append('image', images.image)
    if (images.imageTablet) formData.append('imageTablet', images.imageTablet)
    if (images.imageMobile) formData.append('imageMobile', images.imageMobile)
    formData.append('title', 'banner-upload')
    const env = await requestForm<Record<string, string>>('/api/banners', formData)
    const d = env.data || {}
    return { image: d.image, imageTablet: d.imageTablet, imageMobile: d.imageMobile }
  },

  // ==================== Media ====================
  async getMedia(params?: { page?: number; limit?: number; folder?: string; search?: string }): Promise<ApiListResult<MediaAsset>> {
    const env = await requestJson<MediaAsset[]>(
      `/api/media${buildQuery({ page: params?.page, limit: params?.limit, folder: params?.folder, search: params?.search })}`,
    )
    return asList(env, params?.page, params?.limit)
  },

  async getMediaFolders(): Promise<Array<{ name: string; count: number }>> {
    const env = await requestJson<Array<{ name: string; count: number }>>('/api/media/folders')
    return env.data || []
  },

  async getMediaConfig(): Promise<{ configured: boolean; cloudName: string | null }> {
    const env = await requestJson<{ configured: boolean; cloudName: string | null }>('/api/media/config')
    return env.data || { configured: false, cloudName: null }
  },

  async uploadMedia(files: File[], folder = 'general'): Promise<MediaAsset[]> {
    const formData = new FormData()
    formData.append('folder', folder)
    if (files.length === 1) {
      formData.append('file', files[0])
      const env = await requestForm<MediaAsset>('/api/media/upload', formData)
      return env.data ? [env.data] : []
    }
    files.forEach((f) => formData.append('files', f))
    const env = await requestForm<MediaAsset[]>('/api/media/upload/multiple', formData)
    return env.data || []
  },

  async deleteMedia(id: number): Promise<void> {
    await requestJson(`/api/media/${id}`, { method: 'DELETE' })
  },

  async updateMediaAlt(id: number, alt: string): Promise<MediaAsset> {
    const env = await requestJson<MediaAsset>(`/api/media/${id}`, { method: 'PUT', body: JSON.stringify({ alt }) })
    return env.data!
  },

  // ==================== Orders ====================
  async getOrders(params?: { page?: number; limit?: number; status?: string; search?: string }): Promise<ApiListResult<AdminOrder>> {
    const env = await requestJson<AdminOrder[]>(
      `/api/order${buildQuery({ page: params?.page, limit: params?.limit, status: params?.status, search: params?.search })}`,
    )
    return asList(env, params?.page, params?.limit)
  },

  async getOrder(id: number): Promise<AdminOrder> {
    const env = await requestJson<AdminOrder>(`/api/order/${id}`)
    return env.data!
  },

  async updateOrderStatus(
    id: number,
    payload: { status: string; note?: string; trackingNumber?: string },
  ): Promise<AdminOrder> {
    const env = await requestJson<AdminOrder>(`/api/order/${id}/status`, { method: 'PATCH', body: JSON.stringify(payload) })
    return env.data!
  },

  async verifyOrderPayment(id: number, action: 'verified' | 'rejected', note?: string): Promise<AdminOrder> {
    const env = await requestJson<AdminOrder>(`/api/order/${id}/payment/verify`, {
      method: 'PATCH',
      body: JSON.stringify({ action, note }),
    })
    return env.data!
  },

  async addOrderAdminNote(id: number, note: string): Promise<AdminOrder> {
    const env = await requestJson<AdminOrder>(`/api/order/${id}/admin-note`, {
      method: 'PATCH',
      body: JSON.stringify({ note }),
    })
    return env.data!
  },

  async getOrderInvoice(id: number): Promise<AdminOrder> {
    const env = await requestJson<AdminOrder>(`/api/order/${id}/invoice`)
    return env.data!
  },

  async deleteOrder(id: number): Promise<void> {
    await requestJson(`/api/order/${id}`, { method: 'DELETE' })
  },

  // ==================== Shipping Methods ====================
  async getShippingMethodsAdmin(): Promise<ShippingMethod[]> {
    const env = await requestJson<ShippingMethod[]>('/api/shipping-methods')
    return env.data || []
  },

  async createShippingMethod(payload: {
    name: string
    charge: number
    estimatedDelivery?: string
    description?: string
    priority?: number
    freeShippingMinAmount?: number
    codAvailable?: boolean
    status?: 'active' | 'inactive'
  }): Promise<ShippingMethod> {
    const env = await requestJson<ShippingMethod>('/api/shipping-methods', { method: 'POST', body: JSON.stringify(payload) })
    return env.data!
  },

  async updateShippingMethod(
    id: number,
    payload: Partial<{
      name: string
      charge: number
      estimatedDelivery: string
      description: string
      priority: number
      freeShippingMinAmount: number | null
      codAvailable: boolean
      status: 'active' | 'inactive'
    }>,
  ): Promise<ShippingMethod> {
    const env = await requestJson<ShippingMethod>(`/api/shipping-methods/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
    return env.data!
  },

  async deleteShippingMethod(id: number): Promise<void> {
    await requestJson(`/api/shipping-methods/${id}`, { method: 'DELETE' })
  },

  // ==================== Payment Methods ====================
  async getPaymentMethodsAdmin(): Promise<AdminPaymentMethod[]> {
    const env = await requestJson<AdminPaymentMethod[]>('/api/payment-methods')
    return env.data || []
  },

  async createPaymentMethod(payload: {
    code: string
    name: string
    type: 'cod' | 'mobile_banking' | 'bank' | 'online'
    enabled?: boolean
    sortOrder?: number
    maintenanceMode?: boolean
    config?: Record<string, unknown>
  }): Promise<AdminPaymentMethod> {
    const env = await requestJson<AdminPaymentMethod>('/api/payment-methods', { method: 'POST', body: JSON.stringify(payload) })
    return env.data!
  },

  async updatePaymentMethod(
    id: number,
    payload: Partial<{
      code: string
      name: string
      type: 'cod' | 'mobile_banking' | 'bank' | 'online'
      enabled: boolean
      sortOrder: number
      maintenanceMode: boolean
      config: Record<string, unknown>
    }>,
  ): Promise<AdminPaymentMethod> {
    const env = await requestJson<AdminPaymentMethod>(`/api/payment-methods/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
    return env.data!
  },

  async setPaymentMethodsStatus(ids: number[], enabled: boolean): Promise<void> {
    await requestJson(`/api/payment-methods`, { method: 'PUT', body: JSON.stringify({ ids, enabled }) })
  },

  async deletePaymentMethod(id: number): Promise<void> {
    await requestJson(`/api/payment-methods/${id}`, { method: 'DELETE' })
  },

  // ==================== Checkout Notices ====================
  async getCheckoutNoticesAdmin(): Promise<AdminCheckoutNotice[]> {
    const env = await requestJson<AdminCheckoutNotice[]>('/api/checkout-notices')
    return env.data || []
  },

  async createCheckoutNotice(payload: {
    text: string
    priority?: number
    backgroundColor?: string
    textColor?: string
    icon?: string
    status?: 'active' | 'inactive'
  }): Promise<AdminCheckoutNotice> {
    const env = await requestJson<AdminCheckoutNotice>('/api/checkout-notices', { method: 'POST', body: JSON.stringify(payload) })
    return env.data!
  },

  async updateCheckoutNotice(
    id: number,
    payload: Partial<{
      text: string
      priority: number
      backgroundColor: string
      textColor: string
      icon: string
      status: 'active' | 'inactive'
    }>,
  ): Promise<AdminCheckoutNotice> {
    const env = await requestJson<AdminCheckoutNotice>(`/api/checkout-notices/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
    return env.data!
  },

  async deleteCheckoutNotice(id: number): Promise<void> {
    await requestJson(`/api/checkout-notices/${id}`, { method: 'DELETE' })
  },

  // ==================== Customers ====================
  async getCustomers(): Promise<ApiListResult<AdminCustomer>> {
    const env = await requestJson<AdminCustomer[]>('/api/users')
    return asList(env)
  },

  async deleteCustomer(id: number): Promise<void> {
    await requestJson(`/api/users/${id}`, { method: 'DELETE' })
  },

  // ==================== Coupons ====================
  async getCoupons(): Promise<AdminCoupon[]> {
    const env = await requestJson<AdminCoupon[]>('/api/coupons')
    return env.data || []
  },

  async createCoupon(payload: {
    code: string
    discountType: 'percentage' | 'fixed'
    discountValue: number
    minOrderAmount?: number
    expiryDate?: string
    status?: 'active' | 'inactive'
  }): Promise<AdminCoupon> {
    const env = await requestJson<AdminCoupon>('/api/coupons', { method: 'POST', body: JSON.stringify(payload) })
    return env.data!
  },

  async updateCoupon(id: number, payload: Partial<{
    code: string
    discountType: 'percentage' | 'fixed'
    discountValue: number
    minOrderAmount?: number
    expiryDate?: string
    status?: 'active' | 'inactive'
  }>): Promise<AdminCoupon> {
    const env = await requestJson<AdminCoupon>(`/api/coupons/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
    return env.data!
  },

  async deleteCoupon(id: number): Promise<void> {
    await requestJson(`/api/coupons/${id}`, { method: 'DELETE' })
  },

  // ==================== Settings ====================
  async getSettings(): Promise<Array<{ id: number; key: string; value: string }>> {
    const env = await requestJson<Array<{ id: number; key: string; value: string }>>('/api/settings')
    return env.data || []
  },

  async setSetting(key: string, value: unknown): Promise<void> {
    await requestJson('/api/settings', { method: 'PUT', body: JSON.stringify({ key, value }) })
  },

  async getMe(): Promise<AuthUser> {
    const env = await requestJson<AuthUser>('/api/users/profile')
    return env.data!
  },

  // ==================== Homepage ====================
  async getHomepageConfig(): Promise<HomepageConfig> {
    const env = await requestJson<HomepageConfig>('/api/homepage/admin/config')
    return env.data!
  },

  async saveHomepageConfig(config: Partial<HomepageConfig>): Promise<HomepageConfig> {
    const env = await requestJson<HomepageConfig>('/api/homepage/admin/config', {
      method: 'PUT',
      body: JSON.stringify(config),
    })
    return env.data!
  },

  async resetHomepageConfig(): Promise<HomepageConfig> {
    const env = await requestJson<HomepageConfig>('/api/homepage/admin/reset-defaults', { method: 'POST' })
    return env.data!
  },

  async getNewsletterSubscribers(): Promise<NewsletterSubscriber[]> {
    const env = await requestJson<NewsletterSubscriber[]>('/api/homepage/admin/subscribers')
    return env.data || []
  },

  // ==================== Policies ====================
  async getPolicyPages(): Promise<PolicyPage[]> {
    const env = await requestJson<PolicyPage[]>('/api/pages')
    return env.data || []
  },

  async createPolicyPage(payload: { slug: string; title: string; content: string; status: 'published' | 'draft' }): Promise<PolicyPage> {
    const env = await requestJson<PolicyPage>('/api/pages', { method: 'POST', body: JSON.stringify(payload) })
    return env.data!
  },

  async updatePolicyPage(id: number, payload: { title?: string; content?: string; status?: 'published' | 'draft' }): Promise<PolicyPage> {
    const env = await requestJson<PolicyPage>(`/api/pages/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
    return env.data!
  },

  async deletePolicyPage(id: number): Promise<void> {
    await requestJson(`/api/pages/${id}`, { method: 'DELETE' })
  },

  async getContactMessages(): Promise<ContactMessage[]> {
    const env = await requestJson<ContactMessage[]>('/api/pages/contact')
    return env.data || []
  },

  async setContactMessageStatus(id: number, status: 'new' | 'read' | 'archived'): Promise<ContactMessage> {
    const env = await requestJson<ContactMessage>(`/api/pages/contact/${id}`, {
      method: 'PATCH',
      body: JSON.stringify({ status }),
    })
    return env.data!
  },
}
