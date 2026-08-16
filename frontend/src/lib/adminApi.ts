/**
 * Thin compatibility layer over the centralized RTK Query API.
 *
 * Admin pages that still call `adminApi.*` now hit the same cache as hooks
 * (`useGetAdminDashboardQuery`, etc.). Prefer RTK Query hooks in new code.
 */
import { store } from '../store'
import { adminProductsApi } from '../store/services/adminProductsApi'
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
import type { AdminCheckoutNotice, AdminPaymentMethod, MarketingIntegration, MarketingIntegrationType } from '../types/admin'
import type { HomepageConfig, NewsletterSubscriber } from '../types/homepage'
import { commerceApi } from '../store/services/commerceApi'

// Dispatch an RTK Query/mutation initiate and await the result.
// Queries expose unsubscribe() — drop the temporary subscription so
// keepUnusedDataFor still owns the cache entry lifetime.
const q = async <T>(
  result: { unwrap: () => Promise<T>; unsubscribe?: () => void },
): Promise<T> => {
  try {
    return await result.unwrap()
  } finally {
    result.unsubscribe?.()
  }
}

export const adminApi = {
  // ==================== Dashboard ====================
  async getDashboard(range?: string): Promise<DashboardData> {
    return q(store.dispatch(adminProductsApi.endpoints.getAdminDashboard.initiate(range || undefined)))
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
    return q(store.dispatch(adminProductsApi.endpoints.getAdminProducts.initiate(params as never)))
  },

  async getProduct(id: number): Promise<AdminProduct> {
    return q(store.dispatch(adminProductsApi.endpoints.getAdminProductById.initiate(id)))
  },

  async createProduct(payload: ProductInput): Promise<AdminProduct> {
    return q(store.dispatch(adminProductsApi.endpoints.createProduct.initiate(payload)))
  },

  async updateProduct(id: number, payload: Partial<ProductInput>): Promise<AdminProduct> {
    return q(store.dispatch(adminProductsApi.endpoints.updateProduct.initiate({ id, payload })))
  },

  async deleteProduct(id: number): Promise<void> {
    await q(store.dispatch(adminProductsApi.endpoints.deleteProduct.initiate(id)))
  },

  async bulkProductAction(action: 'delete' | 'publish' | 'archive' | 'hide' | 'draft', ids: number[]): Promise<{ affected: number }> {
    return q(store.dispatch(adminProductsApi.endpoints.bulkProductAction.initiate({ action, ids })))
  },

  async duplicateProduct(id: number): Promise<AdminProduct> {
    return q(store.dispatch(adminProductsApi.endpoints.duplicateProduct.initiate(id)))
  },

  async exportProductsCsv(params?: { search?: string; category?: string; status?: string }): Promise<string> {
    return q(store.dispatch(adminProductsApi.endpoints.exportProductsCsv.initiate(params || {})))
  },

  async importProductsCsv(file: File): Promise<{ imported: number }> {
    const text = await file.text()
    return q(store.dispatch(adminProductsApi.endpoints.importProductsCsv.initiate(text)))
  },

  async uploadProductImages(files: File[]): Promise<string[]> {
    const assets = await q(
      store.dispatch(adminProductsApi.endpoints.uploadMedia.initiate({ files, folder: 'products' })),
    )
    return assets.map((a) => a.url)
  },

  // ==================== Colors / Sizes / Collections / Vendors / Suppliers ====================
  async getColors(): Promise<Color[]> {
    return q(store.dispatch(adminProductsApi.endpoints.getAdminColors.initiate()))
  },
  async createColor(payload: { name: string; hex: string; status?: CatalogStatus; sortOrder?: number }): Promise<Color> {
    return q(store.dispatch(adminProductsApi.endpoints.createColor.initiate(payload)))
  },
  async updateColor(id: number, payload: Partial<Color>): Promise<Color> {
    return q(store.dispatch(adminProductsApi.endpoints.updateColor.initiate({ id, payload })))
  },
  async deleteColor(id: number): Promise<void> {
    await q(store.dispatch(adminProductsApi.endpoints.deleteColor.initiate(id)))
  },

  async getSizes(): Promise<Size[]> {
    return q(store.dispatch(adminProductsApi.endpoints.getAdminSizes.initiate()))
  },
  async createSize(payload: { name: string; status?: CatalogStatus; sortOrder?: number }): Promise<Size> {
    return q(store.dispatch(adminProductsApi.endpoints.createSize.initiate(payload)))
  },
  async updateSize(id: number, payload: Partial<Size>): Promise<Size> {
    return q(store.dispatch(adminProductsApi.endpoints.updateSize.initiate({ id, payload })))
  },
  async deleteSize(id: number): Promise<void> {
    await q(store.dispatch(adminProductsApi.endpoints.deleteSize.initiate(id)))
  },

  async getCollections(): Promise<Collection[]> {
    return q(store.dispatch(commerceApi.endpoints.getCollections.initiate()))
  },
  async createCollection(payload: Partial<Collection> & { name: string }): Promise<Collection> {
    return q(store.dispatch(adminProductsApi.endpoints.createCollection.initiate(payload)))
  },
  async updateCollection(id: number, payload: Partial<Collection>): Promise<Collection> {
    return q(store.dispatch(adminProductsApi.endpoints.updateCollection.initiate({ id, payload })))
  },
  async deleteCollection(id: number): Promise<void> {
    await q(store.dispatch(adminProductsApi.endpoints.deleteCollection.initiate(id)))
  },

  async getVendors(): Promise<Vendor[]> {
    return q(store.dispatch(adminProductsApi.endpoints.getAdminVendors.initiate()))
  },
  async createVendor(payload: Partial<Vendor> & { name: string }): Promise<Vendor> {
    return q(store.dispatch(adminProductsApi.endpoints.createVendor.initiate(payload)))
  },
  async updateVendor(id: number, payload: Partial<Vendor>): Promise<Vendor> {
    return q(store.dispatch(adminProductsApi.endpoints.updateVendor.initiate({ id, payload })))
  },
  async deleteVendor(id: number): Promise<void> {
    await q(store.dispatch(adminProductsApi.endpoints.deleteVendor.initiate(id)))
  },

  async getSuppliers(): Promise<Supplier[]> {
    return q(store.dispatch(adminProductsApi.endpoints.getAdminSuppliers.initiate()))
  },
  async createSupplier(payload: Partial<Supplier> & { name: string }): Promise<Supplier> {
    return q(store.dispatch(adminProductsApi.endpoints.createSupplier.initiate(payload)))
  },
  async updateSupplier(id: number, payload: Partial<Supplier>): Promise<Supplier> {
    return q(store.dispatch(adminProductsApi.endpoints.updateSupplier.initiate({ id, payload })))
  },
  async deleteSupplier(id: number): Promise<void> {
    await q(store.dispatch(adminProductsApi.endpoints.deleteSupplier.initiate(id)))
  },

  async getCategories(): Promise<Category[]> {
    return q(store.dispatch(commerceApi.endpoints.getCategories.initiate()))
  },
  async createCategory(payload: { name: string; description?: string; image?: string }): Promise<Category> {
    return q(store.dispatch(adminProductsApi.endpoints.createCategory.initiate(payload)))
  },
  async updateCategory(id: number, payload: { name?: string; description?: string; image?: string }): Promise<Category> {
    return q(store.dispatch(adminProductsApi.endpoints.updateCategory.initiate({ id, payload })))
  },
  async deleteCategory(id: number): Promise<void> {
    await q(store.dispatch(adminProductsApi.endpoints.deleteCategory.initiate(id)))
  },

  async getBrands(): Promise<Brand[]> {
    return q(store.dispatch(commerceApi.endpoints.getBrands.initiate()))
  },
  async createBrand(payload: Partial<Brand> & { name: string }): Promise<Brand> {
    return q(store.dispatch(adminProductsApi.endpoints.createBrand.initiate(payload)))
  },
  async updateBrand(id: number, payload: Partial<Brand>): Promise<Brand> {
    return q(store.dispatch(adminProductsApi.endpoints.updateBrand.initiate({ id, payload })))
  },
  async deleteBrand(id: number): Promise<void> {
    await q(store.dispatch(adminProductsApi.endpoints.deleteBrand.initiate(id)))
  },

  async uploadBrandLogo(file: File): Promise<string> {
    const assets = await q(
      store.dispatch(adminProductsApi.endpoints.uploadMedia.initiate({ files: [file], folder: 'brands' })),
    )
    return assets[0]?.url || ''
  },

  // ==================== Banners ====================
  async getBanners(): Promise<Banner[]> {
    return q(store.dispatch(adminProductsApi.endpoints.getBanners.initiate()))
  },
  async createBanner(payload: Partial<Banner>): Promise<Banner> {
    return q(store.dispatch(adminProductsApi.endpoints.createBanner.initiate(payload)))
  },
  async updateBanner(id: number, payload: Partial<Banner>): Promise<Banner> {
    return q(store.dispatch(adminProductsApi.endpoints.updateBanner.initiate({ id, payload })))
  },
  async deleteBanner(id: number): Promise<void> {
    await q(store.dispatch(adminProductsApi.endpoints.deleteBanner.initiate(id)))
  },

  async uploadBannerImages(images: { image?: File; imageTablet?: File; imageMobile?: File }): Promise<{
    image?: string
    imageTablet?: string
    imageMobile?: string
  }> {
    const result: { image?: string; imageTablet?: string; imageMobile?: string } = {}
    const uploadOne = async (file: File) => {
      const assets = await q(
        store.dispatch(adminProductsApi.endpoints.uploadMedia.initiate({ files: [file], folder: 'banners' })),
      )
      return assets[0]?.url
    }
    if (images.image) result.image = await uploadOne(images.image)
    if (images.imageTablet) result.imageTablet = await uploadOne(images.imageTablet)
    if (images.imageMobile) result.imageMobile = await uploadOne(images.imageMobile)
    return result
  },

  // ==================== Media ====================
  async getMedia(params?: { page?: number; limit?: number; folder?: string; search?: string }): Promise<ApiListResult<MediaAsset>> {
    const result = await q(store.dispatch(adminProductsApi.endpoints.getAdminMedia.initiate(params)))
    return {
      data: result.data,
      total: result.data.length,
      page: params?.page || 1,
      limit: params?.limit || 20,
      totalPages: result.totalPages,
    }
  },

  async getMediaFolders(): Promise<Array<{ name: string; count: number }>> {
    return q(store.dispatch(adminProductsApi.endpoints.getAdminMediaFolders.initiate()))
  },

  async getMediaConfig(): Promise<{ configured: boolean; cloudName: string | null }> {
    return q(store.dispatch(adminProductsApi.endpoints.getMediaConfig.initiate()))
  },

  async uploadMedia(files: File[], folder = 'general'): Promise<MediaAsset[]> {
    return q(store.dispatch(adminProductsApi.endpoints.uploadMedia.initiate({ files, folder })))
  },

  async deleteMedia(id: number): Promise<void> {
    await q(store.dispatch(adminProductsApi.endpoints.deleteMedia.initiate(id)))
  },

  async updateMediaAlt(id: number, alt: string): Promise<MediaAsset> {
    return q(store.dispatch(adminProductsApi.endpoints.updateMediaAlt.initiate({ id, alt })))
  },

  // ==================== Orders ====================
  async getOrders(params?: { page?: number; limit?: number; status?: string; search?: string }): Promise<ApiListResult<AdminOrder>> {
    return q(store.dispatch(adminProductsApi.endpoints.getAdminOrders.initiate(params)))
  },

  async getOrder(id: number): Promise<AdminOrder> {
    return q(store.dispatch(adminProductsApi.endpoints.getAdminOrderById.initiate(id)))
  },

  async updateOrderStatus(
    id: number,
    payload: { status: string; note?: string; trackingNumber?: string },
  ): Promise<AdminOrder> {
    return q(store.dispatch(adminProductsApi.endpoints.updateOrderStatus.initiate({ id, payload })))
  },

  async verifyOrderPayment(id: number, action: 'verified' | 'rejected', note?: string): Promise<AdminOrder> {
    return q(store.dispatch(adminProductsApi.endpoints.verifyOrderPayment.initiate({ id, action, note })))
  },

  async addOrderAdminNote(id: number, note: string): Promise<AdminOrder> {
    return q(store.dispatch(adminProductsApi.endpoints.addOrderAdminNote.initiate({ id, note })))
  },

  async getOrderInvoice(id: number): Promise<AdminOrder> {
    return q(store.dispatch(adminProductsApi.endpoints.getOrderInvoice.initiate(id)))
  },

  async deleteOrder(id: number): Promise<void> {
    await q(store.dispatch(adminProductsApi.endpoints.deleteOrder.initiate(id)))
  },

  // ==================== Shipping ====================
  async getShippingMethodsAdmin(): Promise<ShippingMethod[]> {
    return q(store.dispatch(adminProductsApi.endpoints.getAdminShippingMethods.initiate()))
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
    return q(store.dispatch(adminProductsApi.endpoints.createShippingMethod.initiate(payload)))
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
    return q(store.dispatch(adminProductsApi.endpoints.updateShippingMethod.initiate({ id, payload })))
  },

  async deleteShippingMethod(id: number): Promise<void> {
    await q(store.dispatch(adminProductsApi.endpoints.deleteShippingMethod.initiate(id)))
  },

  // ==================== Payment methods ====================
  async getPaymentMethodsAdmin(): Promise<AdminPaymentMethod[]> {
    return q(store.dispatch(adminProductsApi.endpoints.getAdminPaymentMethods.initiate()))
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
    return q(store.dispatch(adminProductsApi.endpoints.createPaymentMethod.initiate(payload)))
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
    return q(store.dispatch(adminProductsApi.endpoints.updatePaymentMethod.initiate({ id, payload })))
  },

  async setPaymentMethodsStatus(ids: number[], enabled: boolean): Promise<void> {
    await q(store.dispatch(adminProductsApi.endpoints.setPaymentMethodsStatus.initiate({ ids, enabled })))
  },

  async deletePaymentMethod(id: number): Promise<void> {
    await q(store.dispatch(adminProductsApi.endpoints.deletePaymentMethod.initiate(id)))
  },

  // ==================== Checkout notices ====================
  async getCheckoutNoticesAdmin(): Promise<AdminCheckoutNotice[]> {
    return q(store.dispatch(adminProductsApi.endpoints.getAdminCheckoutNotices.initiate()))
  },

  async createCheckoutNotice(payload: {
    text: string
    priority?: number
    backgroundColor?: string
    textColor?: string
    icon?: string
    status?: 'active' | 'inactive'
  }): Promise<AdminCheckoutNotice> {
    return q(store.dispatch(adminProductsApi.endpoints.createCheckoutNotice.initiate(payload)))
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
    return q(store.dispatch(adminProductsApi.endpoints.updateCheckoutNotice.initiate({ id, payload })))
  },

  async deleteCheckoutNotice(id: number): Promise<void> {
    await q(store.dispatch(adminProductsApi.endpoints.deleteCheckoutNotice.initiate(id)))
  },

  // ==================== Customers ====================
  async getCustomers(): Promise<ApiListResult<AdminCustomer>> {
    return q(store.dispatch(adminProductsApi.endpoints.getAdminCustomers.initiate()))
  },

  async deleteCustomer(id: number): Promise<void> {
    await q(store.dispatch(adminProductsApi.endpoints.deleteCustomer.initiate(id)))
  },

  async createAdmin(payload: {
    name: string
    email: string
    phone: string
    password: string
    role: 'admin' | 'manager'
  }): Promise<AdminCustomer> {
    return q(store.dispatch(adminProductsApi.endpoints.createAdmin.initiate(payload)))
  },

  // ==================== Coupons ====================
  async getCoupons(): Promise<AdminCoupon[]> {
    return q(store.dispatch(adminProductsApi.endpoints.getAdminCoupons.initiate()))
  },

  async createCoupon(payload: {
    code: string
    discountType: 'percentage' | 'fixed'
    discountValue: number
    minOrderAmount?: number
    expiryDate?: string
    status?: 'active' | 'inactive'
  }): Promise<AdminCoupon> {
    return q(store.dispatch(adminProductsApi.endpoints.createCoupon.initiate(payload)))
  },

  async updateCoupon(
    id: number,
    payload: Partial<{
      code: string
      discountType: 'percentage' | 'fixed'
      discountValue: number
      minOrderAmount?: number
      expiryDate?: string
      status?: 'active' | 'inactive'
    }>,
  ): Promise<AdminCoupon> {
    return q(store.dispatch(adminProductsApi.endpoints.updateCoupon.initiate({ id, payload })))
  },

  async deleteCoupon(id: number): Promise<void> {
    await q(store.dispatch(adminProductsApi.endpoints.deleteCoupon.initiate(id)))
  },

  // ==================== Settings ====================
  async getSettings(): Promise<Array<{ id: number; key: string; value: string }>> {
    return q(store.dispatch(commerceApi.endpoints.getSettings.initiate()))
  },

  async setSetting(key: string, value: unknown): Promise<void> {
    await q(store.dispatch(adminProductsApi.endpoints.setSetting.initiate({ key, value })))
  },

  // ==================== Tracking ====================
  async getTrackingIntegrations(): Promise<MarketingIntegration[]> {
    return q(store.dispatch(adminProductsApi.endpoints.getTrackingIntegrations.initiate()))
  },

  async createTrackingIntegration(input: {
    name: string
    type: MarketingIntegrationType
    pixelId?: string
    status?: 'active' | 'inactive'
  }): Promise<MarketingIntegration> {
    return q(store.dispatch(adminProductsApi.endpoints.createTrackingIntegration.initiate(input)))
  },

  async updateTrackingIntegration(
    id: number,
    input: { name?: string; type?: MarketingIntegrationType; pixelId?: string; status?: 'active' | 'inactive' },
  ): Promise<MarketingIntegration> {
    return q(store.dispatch(adminProductsApi.endpoints.updateTrackingIntegration.initiate({ id, payload: input })))
  },

  async getMe(): Promise<AuthUser> {
    const user = await q(store.dispatch(commerceApi.endpoints.getCurrentUser.initiate()))
    if (!user) throw new Error('Not authenticated')
    return user
  },

  // ==================== Homepage ====================
  async getHomepageConfig(): Promise<HomepageConfig> {
    return q(store.dispatch(adminProductsApi.endpoints.getHomepageConfig.initiate()))
  },

  async saveHomepageConfig(config: Partial<HomepageConfig>): Promise<HomepageConfig> {
    return q(store.dispatch(adminProductsApi.endpoints.saveHomepageConfig.initiate(config)))
  },

  async resetHomepageConfig(): Promise<HomepageConfig> {
    return q(store.dispatch(adminProductsApi.endpoints.resetHomepageConfig.initiate()))
  },

  async getNewsletterSubscribers(): Promise<NewsletterSubscriber[]> {
    return q(store.dispatch(adminProductsApi.endpoints.getNewsletterSubscribers.initiate()))
  },

  // ==================== Policies ====================
  async getPolicyPages(): Promise<PolicyPage[]> {
    return q(store.dispatch(adminProductsApi.endpoints.getPolicyPages.initiate()))
  },

  async createPolicyPage(payload: {
    slug: string
    title: string
    content: string
    status: 'published' | 'draft'
  }): Promise<PolicyPage> {
    return q(store.dispatch(adminProductsApi.endpoints.createPolicyPage.initiate(payload)))
  },

  async updatePolicyPage(
    id: number,
    payload: { title?: string; content?: string; status?: 'published' | 'draft' },
  ): Promise<PolicyPage> {
    return q(store.dispatch(adminProductsApi.endpoints.updatePolicyPage.initiate({ id, payload })))
  },

  async deletePolicyPage(id: number): Promise<void> {
    await q(store.dispatch(adminProductsApi.endpoints.deletePolicyPage.initiate(id)))
  },

  async getContactMessages(): Promise<ContactMessage[]> {
    return q(store.dispatch(adminProductsApi.endpoints.getContactMessages.initiate()))
  },

  async setContactMessageStatus(id: number, status: 'new' | 'read' | 'archived'): Promise<ContactMessage> {
    return q(store.dispatch(adminProductsApi.endpoints.setContactMessageStatus.initiate({ id, status })))
  },
}
