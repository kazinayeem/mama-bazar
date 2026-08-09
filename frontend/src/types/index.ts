export type PaymentMethod = 'cod' | 'bkash' | 'nagad' | 'rocket' | 'bank' | 'stripe' | 'sslcommerz' | 'paypal'
export type OrderStatus =
  | 'pending'
  | 'payment_pending'
  | 'payment_verification'
  | 'confirmed'
  | 'processing'
  | 'packed'
  | 'shipped'
  | 'out_for_delivery'
  | 'delivered'
  | 'returned'
  | 'cancelled'
  | 'refunded'
export type PaymentStatus =
  | 'pending'
  | 'payment_pending'
  | 'payment_verification'
  | 'verified'
  | 'success'
  | 'failed'
  | 'rejected'
  | 'refunded'
export type UserRole = 'admin' | 'manager' | 'user'

export type MasterDataStatus = 'active' | 'inactive' | 'archived'

export interface Category {
  id: number
  name: string
  slug: string
  image?: string | null
  icon?: string | null
  banner?: string | null
  thumbnail?: string | null
  description?: string | null
  parentId?: number | null
  featured?: boolean
  homepageVisibility?: boolean
  sortOrder?: number
  seoTitle?: string | null
  seoDescription?: string | null
  seoKeywords?: string | null
  status?: MasterDataStatus
  createdAt?: string
  children?: Category[]
}

export interface ProductVariant {
  id: number
  productId?: number
  name: string
  options: Record<string, string>
  price?: string | null
  discountPrice?: string | null
  sku?: string | null
  barcode?: string | null
  stock: number
  weight?: string | null
  dimensions?: string | null
  images?: string[] | null
  thumbnail?: string | null
  status?: 'active' | 'inactive' | null
  shippingCost?: string | null
  warranty?: string | null
  availability?: boolean
}

export function getVariantEffectivePrice(v: ProductVariant, basePrice?: string | number | null, baseDiscount?: string | number | null): number {
  if (v.discountPrice) return Number(v.discountPrice)
  if (v.price) return Number(v.price)
  const p = Number(basePrice || 0)
  const d = Number(baseDiscount || 0)
  if (d > 0) return Math.round(p - (p * d) / 100)
  return p
}

export function getVariantDiscountPercent(v: ProductVariant): number {
  const price = Number(v.price)
  const discountPrice = Number(v.discountPrice)
  if (!price || !discountPrice || discountPrice >= price) return 0
  return Math.round(((price - discountPrice) / price) * 100)
}

export function findVariantByOptions(
  variants: ProductVariant[] | null | undefined,
  color?: string,
  size?: string
): ProductVariant | undefined {
  if (!variants || variants.length === 0) return undefined
  return variants.find((v) => {
    if (v.status === 'inactive') return false
    const opts = v.options
    const colorMatch = !color || (opts.color && opts.color.toLowerCase() === color.toLowerCase()) || Object.values(opts).some((val) => val.toLowerCase() === color.toLowerCase())
    const sizeMatch = !size || (opts.size && opts.size.toLowerCase() === size.toLowerCase()) || Object.values(opts).some((val) => val.toLowerCase() === size.toLowerCase())
    if (color && size) return colorMatch && sizeMatch
    if (color) return colorMatch
    if (size) return sizeMatch
    return false
  })
}

export function getProductCardPrice(
  variants: ProductVariant[] | null | undefined,
  productPrice: string | number,
  productDiscount?: string | number | null,
  productSalePrice?: string | number | null
): { price: number; displayPrice: string; isRange: boolean; priceFrom?: number } {
  if (!variants || variants.length === 0) {
    const p = Number(productSalePrice) || Number(productPrice) || 0
    const d = Number(productDiscount || 0)
    const final = d > 0 ? Math.round(p - (p * d) / 100) : p
    return { price: final, displayPrice: '', isRange: false }
  }

  const activeVariants = variants.filter((v) => v.status !== 'inactive' && v.availability !== false)
  if (activeVariants.length === 0) {
    const p = Number(productSalePrice) || Number(productPrice) || 0
    return { price: p, displayPrice: '', isRange: false }
  }

  const prices = activeVariants.map((v) => getVariantEffectivePrice(v, productPrice, productDiscount))
  const uniquePrices = [...new Set(prices)]
  const minPrice = Math.min(...prices)

  if (uniquePrices.length === 1) {
    return { price: minPrice, displayPrice: '', isRange: false }
  }

  return { price: minPrice, displayPrice: '', isRange: true, priceFrom: minPrice }
}

export function hasVariantPriceRange(variants: ProductVariant[] | null | undefined, productPrice: string | number, productDiscount?: string | number | null): boolean {
  const { isRange } = getProductCardPrice(variants, productPrice, productDiscount)
  return isRange
}

export interface ProductSpec {
  id: number
  label: string
  value: string
  sortOrder: number
}

export interface ProductRelation {
  id: number
  type: 'frequently_bought_together' | 'cross_sell' | 'up_sell' | 'accessories' | 'similar'
  relatedProduct: { id: number; title: string; slug: string; price: string; discount: string | null; images: string[] | null } | null
}

export interface Product {
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
  emiAvailable?: boolean
  isFeatured?: boolean
  isTrending?: boolean
  isFlashSale?: boolean
  isNewArrival?: boolean
  isBestSeller?: boolean
  isLimitedEdition?: boolean
  isOfficial?: boolean
  isHotDeal?: boolean
  stock: number
  lowStockAlert?: number
  minOrder?: number
  maxOrder?: number | null
  unlimitedStock?: boolean
  backorder?: boolean
  trackInventory?: boolean
  stockStatus?: string | null
  productStatus?: string | null
  images: string[]
  sizeOptions?: string[] | null
  colorOptions?: Array<{ name: string; value?: string; image?: string }> | null
  paymentMethods?: PaymentMethod[]
  paymentPhoneNumber?: string | null
  status: 'active' | 'inactive'
  createdAt?: string
  rating?: number | null
  reviewCount?: number
  category?: { id: number; name: string; slug: string; parentId?: number | null } | null
  subCategory?: { id: number; name: string; slug: string } | null
  childCategory?: { id: number; name: string; slug: string } | null
  collection?: { id: number; name: string; slug: string; image?: string | null } | null
  vendor?: { id: number; name: string; slug: string; logo?: string | null } | null
  supplierInfo?: { id: number; name: string; slug: string } | null
  brandInfo?: { id?: number; name: string; logo?: string | null; slug: string } | null
  variants?: ProductVariant[] | null
  specs?: ProductSpec[] | null
  relations?: ProductRelation[] | null
}

export interface ProductReview {
  id: number
  productId: number
  userId?: number | null
  customerName?: string | null
  rating: number
  title?: string | null
  comment: string
  status: 'pending' | 'approved' | 'rejected'
  createdAt: string
  productTitle?: string
  productSlug?: string
  productImage?: string | null
  customerPhone?: string
}

export interface OrderItemInput {
  productId: number
  variantId?: number
  quantity: number
  size?: string
  color?: string
}

export interface CheckoutInput {
  name: string
  phone: string
  alternativePhone?: string
  email?: string
  country?: string
  division?: string
  district?: string
  upazila?: string
  area?: string
  apartment?: string
  postalCode?: string
  address: string
  shippingArea: string
  shippingCost?: number
  shippingMethodId?: number
  couponCode?: string
  orderNote?: string
  checkoutNotes?: string
  paymentMethod?: PaymentMethod
  transactionId?: string
  senderNumber?: string
  paymentScreenshot?: string
  amountSent?: number
  paymentInstructions?: string
  taxAmount?: number
  items: OrderItemInput[]
}

export interface ShippingMethod {
  id: number
  name: string
  charge: string | number
  estimatedDelivery?: string | null
  description?: string | null
  priority: number
  freeShippingMinAmount: string | number | null
  codAvailable: boolean
  status: 'active' | 'inactive'
  estimatedCost?: number
}

export interface PaymentMethodInfo {
  id: number
  code: PaymentMethod
  name: string
  type: 'cod' | 'mobile_banking' | 'bank' | 'online'
  config?: {
    merchantNumber?: string
    merchantName?: string
    bankName?: string
    accountName?: string
    accountNumber?: string
    routingNumber?: string
    branch?: string
    instructions?: string
    qrCode?: string
    minAmount?: number
    maxAmount?: number
    extraFee?: number
    extraFeePercent?: number
  }
}

export interface CheckoutNotice {
  id: number
  text: string
  priority: number
  backgroundColor: string
  textColor: string
  icon: string
  status: 'active' | 'inactive'
}

export interface Order {
  id: number
  orderId: string
  userId?: number | null
  customerName: string
  phone: string
  alternativePhone?: string | null
  email?: string | null
  country?: string | null
  division?: string | null
  district?: string | null
  upazila?: string | null
  area?: string | null
  address: string
  apartment?: string | null
  postalCode?: string | null
  shippingMethodId?: number | null
  shippingMethodName?: string | null
  shippingCost: string
  subtotal?: string
  discount?: string
  tax?: string
  orderNote?: string | null
  checkoutNotes?: string | null
  adminNotes?: string | null
  totalPrice: string
  paymentMethod: PaymentMethod
  transactionId?: string | null
  senderNumber?: string | null
  paymentScreenshot?: string | null
  paymentDate?: string | null
  amountSent?: string | null
  paymentInstructions?: string | null
  courierTrackingNumber?: string | null
  paymentStatus: PaymentStatus
  status: OrderStatus
  createdAt: string
  historyCount?: number
  statusHistory?: OrderStatusHistory[]
}

export interface OrderStatusHistory {
  id: number
  orderId: number
  status: OrderStatus
  note?: string | null
  createdByUserId?: number | null
  createdAt: string
}

export interface ApiListResult<T> {
  data: T[]
  total: number
  page: number
  limit: number
  totalPages: number
}

export interface AdminCoupon {
  id: number
  code: string
  discountType: 'percentage' | 'fixed'
  discountValue: string
  minOrderAmount?: string | null
  expiryDate?: string | null
  status: 'active' | 'inactive'
  createdAt?: string
}

export interface AuthUser {
  id: number
  name: string
  phone: string
  role: UserRole
  shippingArea?: string | null
  shippingAddress?: string | null
  createdAt?: string
}

export interface AuthResponse {
  token: string
  user: AuthUser
}

export interface PolicyPage {
  id: number
  slug: string
  title: string
  content: string
  status: 'published' | 'draft'
  lastUpdated: number
  createdAt: string
}

export interface ContactMessage {
  id: number
  name: string
  phone: string
  email: string | null
  message: string
  status: 'new' | 'read' | 'archived'
  createdAt: string
}

export interface UserAddress {
  id: number
  userId: number
  recipientName: string
  phone: string
  alternativePhone?: string | null
  email?: string | null
  country?: string | null
  division?: string | null
  district?: string | null
  upazila?: string | null
  area?: string | null
  shippingArea: string
  address: string
  apartment?: string | null
  postalCode?: string | null
  isDefault: boolean
  createdAt: string
}

export interface OrderCreateResponse {
  orderId?: string
  order?: Order
  message: string
  auth?: AuthResponse
}

export interface AuthCredentials {
  phone: string
  password: string
}

export type DevLoginRole = 'SUPER_ADMIN' | 'USER'

export interface AuthRegisterInput extends AuthCredentials {
  name: string
  role?: UserRole
}

export interface UserOrderItem {
  id: number
  productId: number
  quantity: number
  price: string
  size?: string | null
  color?: string | null
  product?: { title?: string; image?: string | null } | null
}

export interface UserOrderWithItems extends Order {
  items?: UserOrderItem[]
}
