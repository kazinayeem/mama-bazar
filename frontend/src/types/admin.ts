import type { Order, OrderStatusHistory, PaymentMethod, UserRole } from './index'

export type ProductStatus = 'active' | 'inactive'
export type BannerPosition = 'hero' | 'banner' | 'promo' | 'sidebar'

export interface Brand {
  id: number
  name: string
  slug: string
  logo?: string | null
  bannerImage?: string | null
  description?: string | null
  website?: string | null
  countryOfOrigin?: string | null
  featured: boolean
  homepageVisibility?: boolean
  sortOrder?: number
  seoTitle?: string | null
  seoDescription?: string | null
  seoKeywords?: string | null
  status: ProductStatus | 'archived'
  createdAt: string
}

export interface Banner {
  id: number
  title?: string | null
  subtitle?: string | null
  image: string
  imageMobile?: string | null
  imageTablet?: string | null
  link?: string | null
  position: BannerPosition
  buttonText?: string | null
  priority: number
  status: ProductStatus
  createdAt: string
  updatedAt: string
}

export interface MediaAsset {
  id: number
  url: string
  publicId?: string | null
  filename: string
  mimeType: string
  size: number
  width?: number | null
  height?: number | null
  provider: 'cloudinary' | 'local'
  folder: string
  alt?: string | null
  createdAt: string
  uploaderName?: string | null
}

export interface ProductCategory {
  id?: number
  name: string
  slug: string
}

export interface ProductBrand {
  id?: number
  name: string
  logo?: string | null
  slug: string
}

// ==================== CATALOG ENTITIES ====================
export type CatalogStatus = 'active' | 'inactive' | 'archived'
export type SizeType = 'clothing' | 'shoes' | 'general' | 'custom'

export interface Color {
  id: number
  name: string
  displayName?: string | null
  hex: string
  status: CatalogStatus
  sortOrder: number
  createdAt: string
}

export interface Size {
  id: number
  name: string
  type: SizeType
  status: CatalogStatus
  sortOrder: number
  createdAt: string
}

export interface Collection {
  id: number
  name: string
  slug: string
  description?: string | null
  image?: string | null
  banner?: string | null
  featured: boolean
  homepageVisibility?: boolean
  sortOrder: number
  startDate?: string | null
  endDate?: string | null
  status: CatalogStatus
  createdAt: string
}

export interface Vendor {
  id: number
  name: string
  slug: string
  logo?: string | null
  description?: string | null
  contact?: string | null
  phone?: string | null
  email?: string | null
  address?: string | null
  notes?: string | null
  status: CatalogStatus
  createdAt: string
}

export interface Supplier {
  id: number
  name: string
  slug: string
  logo?: string | null
  description?: string | null
  contact?: string | null
  phone?: string | null
  email?: string | null
  address?: string | null
  notes?: string | null
  status: CatalogStatus
  createdAt: string
}

// ==================== PRODUCT CHILDREN ====================
export type ProductRelationType = 'frequently_bought_together' | 'cross_sell' | 'up_sell' | 'accessories' | 'similar'

export interface ProductVariant {
  id: number
  name: string
  options?: Record<string, string> | null
  price?: string | null
  salePrice?: string | null
  sku?: string | null
  barcode?: string | null
  stock?: number | null
  images?: string[] | null
  thumbnail?: string | null
  weight?: string | null
  shippingCharge?: string | null
  warranty?: string | null
  availability?: boolean | null
  sortOrder?: number | null
}

export interface ProductVariantInput {
  /** Optional database id — kept when editing so the same variant row is updated. */
  id?: number
  name: string
  options?: Record<string, string>
  price?: string | number
  salePrice?: string | number
  sku?: string
  barcode?: string
  stock?: number
  images?: string[]
  thumbnail?: string
  weight?: string | number
  shippingCharge?: string | number
  warranty?: string
  availability?: boolean
}

export interface ProductSpec {
  id: number
  label: string
  value: string
  sortOrder?: number | null
}

export interface ProductRelation {
  id: number
  type: ProductRelationType
  /** The backend returns the related product object; use its id when submitting. */
  relatedProduct?: { id: number; title: string; slug?: string; price?: string | number; discount?: string | number | null; images?: string[] | null } | null
  /** Present on some older responses — prefer relatedProduct.id. */
  relatedProductId?: number
}

export interface ProductRelationInput {
  type: ProductRelationType
  relatedProductId: number
}

export type ProductStatusValue = 'draft' | 'published' | 'archived' | 'hidden'
export type StockStatusValue = 'in_stock' | 'low_stock' | 'out_of_stock' | 'on_backorder'

export interface AdminProduct {
  id: number
  title: string
  slug: string
  description?: string | null
  shortDescription?: string | null
  price: string
  salePrice?: string | null
  discount?: string | null
  costPrice?: string | null
  profitMargin?: string | null
  tax?: string | null
  vat?: string | null
  shippingCharge?: string | null
  codFee?: string | null
  flashSalePrice?: string | null
  wholesalePrice?: string | null
  dealerPrice?: string | null
  categoryId?: number | null
  subCategoryId?: number | null
  childCategoryId?: number | null
  collectionId?: number | null
  brandId?: number | null
  brand?: string | null
  vendorId?: number | null
  supplierId?: number | null
  supplier?: string | null
  countryOfOrigin?: string | null
  sku?: string | null
  barcode?: string | null
  tags?: string[] | null
  warranty?: string | null
  weight?: string | null
  dimensions?: string | null
  features?: string[] | null
  returnPolicy?: string | null
  warehouse?: string | null
  videoUrl?: string | null
  seoTitle?: string | null
  seoDescription?: string | null
  seoKeywords?: string | null
  canonicalUrl?: string | null
  ogImage?: string | null
  twitterImage?: string | null
  structuredData?: string | null
  emiAvailable: boolean
  isFeatured: boolean
  isTrending: boolean
  isFlashSale: boolean
  isNewArrival: boolean
  isBestSeller: boolean
  isLimitedEdition: boolean
  isOfficial: boolean
  isHotDeal: boolean
  isArchived: boolean
  draft?: Record<string, unknown> | null
  meta?: Record<string, unknown> | null
  stock: number
  lowStockAlert?: number | null
  minOrder?: number | null
  maxOrder?: number | null
  unlimitedStock?: boolean | null
  backorder?: boolean | null
  trackInventory?: boolean | null
  stockStatus?: StockStatusValue | null
  productStatus?: ProductStatusValue | null
  images: string[]
  sizeOptions?: string[] | null
  colorOptions?: Array<{ name: string; value?: string; image?: string }> | null
  paymentMethods?: PaymentMethod[]
  paymentPhoneNumber?: string | null
  status: ProductStatus
  createdAt: string
  category: ProductCategory | null
  subCategory?: ProductCategory | null
  childCategory?: ProductCategory | null
  collection?: ProductCategory | null
  vendor?: { id?: number; name: string; slug: string; logo?: string | null } | null
  supplierInfo?: { id?: number; name: string; slug: string } | null
  brandInfo: ProductBrand | null
  variants?: ProductVariant[] | null
  specs?: ProductSpec[] | null
  relations?: ProductRelation[] | null
}

export type ProductSort =
  | 'newest'
  | 'oldest'
  | 'price_asc'
  | 'price_desc'
  | 'stock_asc'
  | 'stock_desc'
  | 'title_asc'

export type ProductStockFilter = 'in_stock' | 'low_stock' | 'out_of_stock'

export interface AdminProductFilters {
  page?: number
  limit?: number
  search?: string
  category?: string
  brand?: string
  supplier?: string
  vendor?: string
  collection?: string
  stock?: ProductStockFilter
  minPrice?: string | number
  maxPrice?: string | number
  dateFrom?: string
  dateTo?: string
  sort?: ProductSort
  status?: string
  productStatus?: string
  label?: string
  sku?: string
  barcode?: string
}

export interface AdminListResult<T> {
  data: T[]
  total: number
  page: number
  limit: number
  totalPages: number
}

export type AdminProductListResult = AdminListResult<AdminProduct>

export type ProductBulkAction = 'delete' | 'publish' | 'archive' | 'hide' | 'draft'

export interface ProductInput {
  title: string
  description?: string
  shortDescription?: string
  price: string | number
  salePrice?: string | number
  discount?: string | number
  costPrice?: string | number
  profitMargin?: string | number
  tax?: string | number
  vat?: string | number
  shippingCharge?: string | number
  codFee?: string | number
  flashSalePrice?: string | number
  wholesalePrice?: string | number
  dealerPrice?: string | number
  categoryId?: string | number | null
  subCategoryId?: string | number | null
  childCategoryId?: string | number | null
  collectionId?: string | number | null
  brandId?: string | number | null
  brand?: string
  vendorId?: string | number | null
  supplierId?: string | number | null
  countryOfOrigin?: string
  sku?: string
  barcode?: string
  warranty?: string
  weight?: string
  dimensions?: string
  supplier?: string
  warehouse?: string
  videoUrl?: string
  seoTitle?: string
  seoDescription?: string
  seoKeywords?: string
  canonicalUrl?: string
  ogImage?: string
  twitterImage?: string
  structuredData?: string
  returnPolicy?: string
  emiAvailable?: boolean
  isFeatured?: boolean
  isTrending?: boolean
  isFlashSale?: boolean
  isNewArrival?: boolean
  isBestSeller?: boolean
  isLimitedEdition?: boolean
  isOfficial?: boolean
  isHotDeal?: boolean
  isArchived?: boolean
  stock?: number
  lowStockAlert?: number
  minOrder?: number
  maxOrder?: number
  unlimitedStock?: boolean
  backorder?: boolean
  trackInventory?: boolean
  stockStatus?: StockStatusValue
  productStatus?: ProductStatusValue
  images?: string[]
  sizeOptions?: string[]
  colorOptions?: Array<{ name: string; value?: string; image?: string }>
  tags?: string[]
  features?: string[]
  status?: ProductStatus
  paymentPhoneNumber?: string
  variants?: ProductVariantInput[]
  specs?: ProductSpec[]
  relations?: ProductRelationInput[]
}

export interface AdminOrder extends Order {
  orderNote?: string | null
  transactionId?: string | null
  couponCode?: string | null
  userId?: number | null
  items?: Array<{
    id: number
    productId: number
    variantId?: number | null
    quantity: number
    price: string
    size?: string | null
    color?: string | null
    variantName?: string | null
    product?: { title?: string; image?: string | null } | null
  }>
  statusHistory?: OrderStatusHistory[]
}

export interface AdminPaymentMethod {
  id: number
  code: string
  name: string
  type: 'cod' | 'mobile_banking' | 'bank' | 'online'
  enabled: boolean
  sortOrder: number
  maintenanceMode: boolean
  config?: Record<string, unknown> | null
  createdAt?: string
  updatedAt?: string
}

export interface AdminCheckoutNotice {
  id: number
  text: string
  priority: number
  backgroundColor: string
  textColor: string
  icon: string
  status: 'active' | 'inactive'
  createdAt?: string
}

export interface AdminCustomer {
  id: number
  name: string
  phone: string
  role: UserRole
  shippingArea?: string | null
  shippingAddress?: string | null
  createdAt: string
}

export interface DashboardKpis {
  totalRevenue: number
  totalOrders: number
  avgOrderValue: number
  totalCustomers: number
  totalProducts: number
  todayOrders: number
  periodRevenue: number
  periodOrders: number
  deliveredThisPeriod: number
  cancelledThisPeriod: number
  lowStock: number
  outOfStock: number
  conversionRate: number
  periodVisitors: number
}

export interface DashboardData {
  kpis: DashboardKpis
  revenueChart: Array<{ date: string; revenue: number; orders: number }>
  statusBreakdown: Record<string, number>
  paymentBreakdown: Array<{ method: string; count: number; revenue: number }>
  recentOrders: Array<{ id: number; orderId: string; customerName: string; totalPrice: string; status: Order['status']; createdAt: string }>
  topProducts: Array<{ id: number; title: string; slug: string; image: string | null; quantity: number; revenue: number }>
  topCategories: Array<{ id: number | null; name: string | null; count: number }>
  lowStockProducts: Array<{ id: number; title: string; slug: string; stock: number; image: string | null; price: string }>
}

// ==================== EXPENSES & FINANCE ====================
export type ExpenseStatus = 'pending' | 'approved' | 'rejected'

export interface ExpenseCategory {
  id: number
  name: string
  description?: string | null
  status: 'active' | 'inactive'
  sortOrder: number
  createdAt?: string
}

export interface Expense {
  id: number
  title: string
  description?: string | null
  categoryId?: number | null
  categoryName?: string | null
  amount: string
  paymentMethod: string
  vendor?: string | null
  memberId?: number | null
  memberName?: string | null
  expenseDate: string
  referenceNumber?: string | null
  attachmentUrl?: string | null
  notes?: string | null
  status: ExpenseStatus
  createdById?: number | null
  createdByName?: string | null
  createdAt?: string
  updatedAt?: string
}

export interface ExpenseFilters {
  page?: number
  limit?: number
  search?: string
  status?: string
  memberId?: string
  categoryId?: string
  paymentMethod?: string
  dateFrom?: string
  dateTo?: string
  amountMin?: string
  amountMax?: string
}

export interface ExpenseListResult {
  data: Expense[]
  total: number
  page: number
  limit: number
  totalPages: number
}

export interface ExpenseInput {
  title: string
  description?: string | null
  categoryId?: number | null
  amount: number
  paymentMethod?: string
  memberId?: number | null
  expenseDate: string
  referenceNumber?: string | null
  attachmentUrl?: string | null
  notes?: string | null
  status?: ExpenseStatus
}

export interface ExpenseSummary {
  total: number
  totalCount: number
  thisMonth: number
  thisMonthCount: number
  thisWeek: number
  thisWeekCount: number
  today: number
  todayCount: number
}

export interface ExpenseMemberRow {
  memberId?: number | null
  memberName: string
  total: number
  count: number
}

export interface ExpenseCategoryRow {
  categoryId?: number | null
  categoryName: string
  total: number
  count: number
}

export interface ExpenseMonthlyReport {
  year: number
  month: number
  total: number
  count: number
  average: number
  highest: number
  lowest: number
  byMember: ExpenseMemberRow[]
  byCategory: ExpenseCategoryRow[]
  expenses: Expense[]
  pagination: { page: number; limit: number; total: number; totalPages: number }
}

export interface ExpenseTrendRow {
  month: number
  label: string
  total: number
  count: number
}

export interface ExpenseRangeReport {
  dateFrom: string | null
  dateTo: string | null
  total: number
  count: number
  byMember: ExpenseMemberRow[]
  byCategory: ExpenseCategoryRow[]
  expenses: Expense[]
}

export interface ProfitOverview {
  year: number
  month: number
  revenue: number
  productCost: number
  operatingExpenses: number
  netProfit: number
  hasRevenueData: boolean
}

export interface TeamMember {
  id: number
  name: string
  phone: string
  role: UserRole
}
