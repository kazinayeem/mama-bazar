import type {
  AdminProduct,
  ProductInput,
  ProductRelationInput,
  ProductRelationType,
  ProductSpec,
  ProductStatusValue,
  ProductVariantInput,
  StockStatusValue,
} from '@/types/admin'

export type { ProductInput } from '@/types/admin'

export interface ImageItem {
  id: string
  url: string
  status: 'existing' | 'uploading' | 'done' | 'error'
  progress: number
  error?: string
}

export interface VariantFormValue {
  key: string
  /** Database id of an existing variant — preserved when editing so updates update the same row. */
  id?: number | null
  name: string
  options: string
  price: string
  salePrice: string
  sku: string
  barcode: string
  stock: string
  thumbnail: string
  availability: boolean
}

export interface SpecFormValue {
  key: string
  label: string
  value: string
}

export interface RelationFormValue {
  key: string
  type: ProductRelationType
  relatedProductId: string
}

export interface ProductFormValues {
  title: string
  slug?: string
  description: string
  shortDescription: string
  returnPolicy: string

  categoryId: string
  subCategoryId: string
  childCategoryId: string
  brandId: string
  collectionId: string
  vendorId: string
  supplierId: string
  sku: string
  barcode: string
  warehouse: string
  countryOfOrigin: string
  weight: string
  dimensions: string
  warranty: string
  videoUrl: string
  paymentPhoneNumber: string

  price: string
  salePrice: string
  discount: string
  costPrice: string
  profitMargin: string
  tax: string
  vat: string
  shippingCharge: string
  codFee: string
  flashSalePrice: string
  wholesalePrice: string
  dealerPrice: string

  stock: string
  lowStockAlert: string
  minOrder: string
  maxOrder: string
  stockStatus: StockStatusValue
  unlimitedStock: boolean
  backorder: boolean
  trackInventory: boolean

  productStatus: ProductStatusValue
  status: 'active' | 'inactive'
  isFeatured: boolean
  isTrending: boolean
  isFlashSale: boolean
  isNewArrival: boolean
  isBestSeller: boolean
  isLimitedEdition: boolean
  isOfficial: boolean
  isHotDeal: boolean
  emiAvailable: boolean

  seoTitle: string
  seoDescription: string
  seoKeywords: string
  canonicalUrl: string
  ogImage: string
  twitterImage: string
  structuredData: string

  tags: string[]
  features: string[]
  sizeOptions: string[]
  colorOptions: string[]
  images: ImageItem[]
  variants: VariantFormValue[]
  specs: SpecFormValue[]
  relations: RelationFormValue[]
}

export const PRODUCT_STATUSES: Array<{ value: ProductStatusValue; label: string }> = [
  { value: 'draft', label: 'Draft' },
  { value: 'published', label: 'Published' },
  { value: 'hidden', label: 'Hidden' },
  { value: 'archived', label: 'Archived' },
]

export const STOCK_STATUSES: Array<{ value: StockStatusValue; label: string }> = [
  { value: 'in_stock', label: 'In stock' },
  { value: 'low_stock', label: 'Low stock' },
  { value: 'out_of_stock', label: 'Out of stock' },
  { value: 'on_backorder', label: 'On backorder' },
]

export const RELATION_TYPES: Array<{ value: ProductRelationType; label: string }> = [
  { value: 'frequently_bought_together', label: 'Frequently bought together' },
  { value: 'cross_sell', label: 'Cross-sell' },
  { value: 'up_sell', label: 'Up-sell' },
  { value: 'accessories', label: 'Accessories' },
  { value: 'similar', label: 'Similar products' },
]

const newKey = () => crypto.randomUUID()

export const str = (v?: string | number | null): string => (v === undefined || v === null ? '' : String(v))

export const num = (v?: string | number | null): number | undefined => {
  if (v === undefined || v === null || v === '') return undefined
  const n = Number(v)
  return Number.isFinite(n) ? n : undefined
}

export const emptyForm = (): ProductFormValues => ({
  title: '',
  description: '',
  shortDescription: '',
  returnPolicy: '',

  categoryId: '',
  subCategoryId: '',
  childCategoryId: '',
  brandId: '',
  collectionId: '',
  vendorId: '',
  supplierId: '',
  sku: '',
  barcode: '',
  warehouse: '',
  countryOfOrigin: '',
  weight: '',
  dimensions: '',
  warranty: '',
  videoUrl: '',
  paymentPhoneNumber: '',

  price: '',
  salePrice: '',
  discount: '',
  costPrice: '',
  profitMargin: '',
  tax: '',
  vat: '',
  shippingCharge: '',
  codFee: '',
  flashSalePrice: '',
  wholesalePrice: '',
  dealerPrice: '',

  stock: '0',
  lowStockAlert: '',
  minOrder: '',
  maxOrder: '',
  stockStatus: 'in_stock',
  unlimitedStock: false,
  backorder: false,
  trackInventory: true,

  productStatus: 'draft',
  status: 'inactive',
  isFeatured: false,
  isTrending: false,
  isFlashSale: false,
  isNewArrival: false,
  isBestSeller: false,
  isLimitedEdition: false,
  isOfficial: false,
  isHotDeal: false,
  emiAvailable: false,

  seoTitle: '',
  seoDescription: '',
  seoKeywords: '',
  canonicalUrl: '',
  ogImage: '',
  twitterImage: '',
  structuredData: '',

  tags: [],
  features: [],
  sizeOptions: [],
  colorOptions: [],
  images: [],
  variants: [],
  specs: [],
  relations: [],
})

export const productToFormValues = (product: AdminProduct): ProductFormValues => ({
  title: product.title || '',
  description: product.description || '',
  shortDescription: product.shortDescription || '',
  returnPolicy: product.returnPolicy || '',

  categoryId: product.categoryId ? String(product.categoryId) : '',
  subCategoryId: product.subCategoryId ? String(product.subCategoryId) : '',
  childCategoryId: product.childCategoryId ? String(product.childCategoryId) : '',
  brandId: product.brandId ? String(product.brandId) : '',
  collectionId: product.collectionId ? String(product.collectionId) : '',
  vendorId: product.vendorId ? String(product.vendorId) : '',
  supplierId: product.supplierId ? String(product.supplierId) : '',
  sku: product.sku || '',
  barcode: product.barcode || '',
  warehouse: product.warehouse || '',
  countryOfOrigin: product.countryOfOrigin || '',
  weight: product.weight || '',
  dimensions: product.dimensions || '',
  warranty: product.warranty || '',
  videoUrl: product.videoUrl || '',
  paymentPhoneNumber: product.paymentPhoneNumber || '',

  price: str(product.price),
  salePrice: str(product.salePrice),
  discount: str(product.discount),
  costPrice: str(product.costPrice),
  profitMargin: str(product.profitMargin),
  tax: str(product.tax),
  vat: str(product.vat),
  shippingCharge: str(product.shippingCharge),
  codFee: str(product.codFee),
  flashSalePrice: str(product.flashSalePrice),
  wholesalePrice: str(product.wholesalePrice),
  dealerPrice: str(product.dealerPrice),

  stock: str(product.stock),
  lowStockAlert: str(product.lowStockAlert),
  minOrder: str(product.minOrder),
  maxOrder: str(product.maxOrder),
  stockStatus: product.stockStatus || 'in_stock',
  unlimitedStock: product.unlimitedStock ?? false,
  backorder: product.backorder ?? false,
  trackInventory: product.trackInventory ?? true,

  productStatus: product.productStatus || 'draft',
  status: product.status,
  isFeatured: product.isFeatured,
  isTrending: product.isTrending,
  isFlashSale: product.isFlashSale,
  isNewArrival: product.isNewArrival,
  isBestSeller: product.isBestSeller,
  isLimitedEdition: product.isLimitedEdition,
  isOfficial: product.isOfficial,
  isHotDeal: product.isHotDeal,
  emiAvailable: product.emiAvailable,

  seoTitle: product.seoTitle || '',
  seoDescription: product.seoDescription || '',
  seoKeywords: product.seoKeywords || '',
  canonicalUrl: product.canonicalUrl || '',
  ogImage: product.ogImage || '',
  twitterImage: product.twitterImage || '',
  structuredData: product.structuredData || '',

  tags: product.tags || [],
  features: product.features || [],
  sizeOptions: product.sizeOptions || [],
  colorOptions: (product.colorOptions || []).map((c) => c.name),
  images: (product.images || []).map((url) => ({ id: newKey(), url, status: 'existing', progress: 100 })),
  variants: (product.variants || []).map((v) => ({
    key: newKey(),
    id: v.id ?? null,
    name: v.name,
    options: v.options ? JSON.stringify(v.options) : '',
    price: str(v.price),
    salePrice: str(v.salePrice),
    sku: v.sku || '',
    barcode: v.barcode || '',
    stock: str(v.stock),
    thumbnail: v.thumbnail || '',
    availability: v.availability ?? true,
  })),
  specs: (product.specs || []).map((s) => ({ key: newKey(), label: s.label, value: s.value })),
  relations: (product.relations || []).map((r) => ({
    key: newKey(),
    type: r.type,
    relatedProductId: String(r.relatedProductId),
  })),
})

export const formValuesToPayload = (values: ProductFormValues): ProductInput => {
  const hasVariants = values.variants.length > 0
  const priceNum = num(values.price)
  return {
    title: values.title.trim(),
    description: values.description || undefined,
    shortDescription: values.shortDescription || undefined,
    returnPolicy: values.returnPolicy || undefined,
    price: hasVariants ? (priceNum ?? 0) : (priceNum ?? 0),
    salePrice: num(values.salePrice),
    discount: num(values.discount),
    costPrice: num(values.costPrice),
    profitMargin: num(values.profitMargin),
    tax: num(values.tax),
    vat: num(values.vat),
    shippingCharge: num(values.shippingCharge),
    codFee: num(values.codFee),
    flashSalePrice: num(values.flashSalePrice),
    wholesalePrice: num(values.wholesalePrice),
    dealerPrice: num(values.dealerPrice),
    categoryId: values.categoryId || null,
    subCategoryId: values.subCategoryId || null,
    childCategoryId: values.childCategoryId || null,
    brandId: values.brandId || null,
    collectionId: values.collectionId || null,
    vendorId: values.vendorId || null,
    supplierId: values.supplierId || null,
    sku: values.sku || undefined,
    barcode: values.barcode || undefined,
    warehouse: values.warehouse || undefined,
    countryOfOrigin: values.countryOfOrigin || undefined,
    weight: values.weight || undefined,
    dimensions: values.dimensions || undefined,
    warranty: values.warranty || undefined,
    videoUrl: values.videoUrl || undefined,
    paymentPhoneNumber: values.paymentPhoneNumber || undefined,
    stock: num(values.stock) ?? 0,
    lowStockAlert: num(values.lowStockAlert),
    minOrder: num(values.minOrder),
    maxOrder: num(values.maxOrder),
    stockStatus: values.stockStatus,
    unlimitedStock: values.unlimitedStock,
    backorder: values.backorder,
    trackInventory: values.trackInventory,
    productStatus: values.productStatus,
    status: values.status,
    isFeatured: values.isFeatured,
    isTrending: values.isTrending,
    isFlashSale: values.isFlashSale,
    isNewArrival: values.isNewArrival,
    isBestSeller: values.isBestSeller,
    isLimitedEdition: values.isLimitedEdition,
    isOfficial: values.isOfficial,
    isHotDeal: values.isHotDeal,
    emiAvailable: values.emiAvailable,
    seoTitle: values.seoTitle || undefined,
    seoDescription: values.seoDescription || undefined,
    seoKeywords: values.seoKeywords || undefined,
    canonicalUrl: values.canonicalUrl || undefined,
    ogImage: values.ogImage || undefined,
    twitterImage: values.twitterImage || undefined,
    structuredData: values.structuredData || undefined,
    tags: values.tags.length > 0 ? values.tags : undefined,
    features: values.features.length > 0 ? values.features : undefined,
    sizeOptions: values.sizeOptions.length > 0 ? values.sizeOptions : undefined,
    colorOptions:
      values.colorOptions.length > 0
        ? values.colorOptions.map((name) => ({ name, value: name.toLowerCase().replace(/\s+/g, '-') }))
        : undefined,
    images: values.images.filter((i) => i.status === 'done' || i.status === 'existing').map((i) => i.url),
    variants:
      values.variants.length > 0
        ? values.variants
            .filter((v) => v.name.trim())
            .map<ProductVariantInput>((v) => ({
              id: v.id ?? undefined,
              name: v.name.trim(),
              options: v.options.trim() ? safeParseOptions(v.options) : undefined,
              price: v.price || undefined,
              salePrice: v.salePrice || undefined,
              sku: v.sku || undefined,
              barcode: v.barcode || undefined,
              stock: num(v.stock),
              thumbnail: v.thumbnail || undefined,
              availability: v.availability,
            }))
        : undefined,
    specs:
      values.specs.length > 0
        ? values.specs
            .filter((s) => s.label.trim() || s.value.trim())
            .map<ProductSpec>((s, index) => ({
              id: Date.now() + index,
              label: s.label.trim(),
              value: s.value.trim(),
              sortOrder: index + 1,
            }))
        : undefined,
    relations:
      values.relations.length > 0
        ? values.relations
            .filter((r) => r.relatedProductId.trim())
            .map<ProductRelationInput>((r) => ({
              type: r.type,
              relatedProductId: Number(r.relatedProductId),
            }))
        : undefined,
  }
}

const safeParseOptions = (json: string): Record<string, string> | undefined => {
  try {
    const parsed = JSON.parse(json) as unknown
    if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) return parsed as Record<string, string>
  } catch {
    return undefined
  }
  return undefined
}

export const productTitle = (values: ProductFormValues): string => values.title.trim() || 'Untitled product'
