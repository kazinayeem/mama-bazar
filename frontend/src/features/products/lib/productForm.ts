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
  hasVariants: boolean
  title: string
  slug: string
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
  hasVariants: false,
  title: '',
  slug: '',
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
  hasVariants: (product.variants?.length ?? 0) > 0,
  title: product.title || '',
  slug: product.slug || '',
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
    relatedProductId: r.relatedProduct?.id ? String(r.relatedProduct.id) : r.relatedProductId ? String(r.relatedProductId) : '',
  })),
})

export const formValuesToPayload = (values: ProductFormValues): ProductInput => {
  const hasVariants = values.hasVariants && values.variants.length > 0
  const priceNum = num(values.price)

  const variants =
    values.hasVariants || values.variants.length > 0
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
      : []

  const variantPrices = variants
    .map((v) => Number(v.price))
    .filter((n) => Number.isFinite(n) && n > 0)

  return {
    title: values.title.trim(),
    slug: values.slug.trim().toLowerCase() || undefined,
    description: values.description || undefined,
    shortDescription: values.shortDescription || undefined,
    returnPolicy: values.returnPolicy || undefined,
    // When variants exist the parent price is a fallback for the storefront;
    // derive it from the cheapest variant so the parent product always has a valid price.
    price: hasVariants
      ? priceNum && priceNum > 0
        ? priceNum
        : variantPrices.length
          ? Math.min(...variantPrices)
          : 0
      : priceNum ?? 0,
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
    variants: variants.length > 0 ? variants : [],
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
    relations: toRelationPayload(values.relations),
  }
}

/**
 * Normalize relation rows for the API.
 * The backend expects `relations: [{ relatedProductId: number, type }]` — a JSON
 * array. Rows without a valid numeric product id are dropped, and the whole
 * field is omitted (not null / empty) when nothing is linked.
 */
const toRelationPayload = (
  relations: RelationFormValue[],
): ProductRelationInput[] | undefined => {
  if (!relations || relations.length === 0) return undefined
  const valid = relations
    .map((r) => ({ type: r.type, relatedProductId: Number(String(r.relatedProductId).trim()) }))
    .filter((r) => Number.isInteger(r.relatedProductId) && r.relatedProductId > 0)
  return valid.length > 0 ? valid : undefined
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

/** Build the variant `options` payload: option names for display plus the DB ids for mapping. */
export const buildVariantOptions = (
  color?: { name: string; id?: number } | null,
  size?: { name: string; id?: number } | null,
): Record<string, string> => {
  const options: Record<string, string> = {}
  if (color?.name) {
    options.color = color.name
    if (color.id) options.colorId = String(color.id)
  }
  if (size?.name) {
    options.size = size.name
    if (size.id) options.sizeId = String(size.id)
  }
  return options
}

/** Unique per-combination key used to detect existing variants when generating. */
export const variantCombinationKey = (options: Record<string, string>): string =>
  [options.color || '', options.size || ''].join('::').toLowerCase()

const slugifyToken = (value: string): string =>
  value
    .toUpperCase()
    .replace(/[^A-Z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')

const shortCode = (value: string, length: number): string =>
  value
    .split(/[\s/-]+/)
    .map((part) => part.replace(/[^A-Za-z0-9]/g, '').toUpperCase())
    .filter(Boolean)
    .map((part) => (part.length > length ? part.slice(0, length) : part))
    .join('-')

/** Generate a unique SKU like JBL-510BT-BLK-L from the product SKU + color + size. */
export const suggestVariantSku = (productSku: string, color?: string, size?: string): string => {
  const base = productSku.trim() ? slugifyToken(productSku.trim()) : ''
  const colorCode = color ? shortCode(color, 3) : ''
  const sizeCode = size ? shortCode(size, 3) : ''
  const parts = [base, colorCode, sizeCode].filter(Boolean)
  return parts.length > 0 ? parts.join('-') : ''
}

export interface VariantValidationResult {
  errors: string[]
  duplicateSkus: string[]
}

/** Validate variant rows before saving. Returns errors with inline-friendly messages. */
export const validateVariants = (values: Pick<ProductFormValues, 'hasVariants' | 'variants' | 'price'>): VariantValidationResult => {
  const errors: string[] = []
  const duplicateSkus: string[] = []
  const seenNames = new Set<string>()
  const seenSkus = new Set<string>()

  if (values.hasVariants && values.variants.length === 0) {
    errors.push('Variant mode is enabled, but no variants were added. Add at least one variant or turn the toggle off.')
  }

  values.variants.forEach((v, idx) => {
    const label = v.name.trim() ? `"${v.name.trim()}"` : `Variant ${idx + 1}`
    if (!v.name.trim()) {
      errors.push(`Variant ${idx + 1}: Name is required`)
    } else {
      const nameLower = v.name.trim().toLowerCase()
      if (seenNames.has(nameLower)) {
        errors.push(`Variant ${idx + 1}: Duplicate variant name "${v.name.trim()}"`)
      }
      seenNames.add(nameLower)
    }

    const price = Number(v.price)
    if (v.price.trim() && (Number.isNaN(price) || price < 0)) {
      errors.push(`${label}: Price must be a non-negative number`)
    }
    const salePrice = Number(v.salePrice)
    if (v.salePrice.trim() && (Number.isNaN(salePrice) || salePrice < 0)) {
      errors.push(`${label}: Sale price must be a non-negative number`)
    }
    if (v.price.trim() && v.salePrice.trim() && salePrice > price) {
      errors.push(`${label}: Sale price cannot exceed regular price`)
    }
    const stock = Number(v.stock)
    if (v.stock.trim() && (Number.isNaN(stock) || stock < 0)) {
      errors.push(`${label}: Stock cannot be negative`)
    }
    if (v.sku.trim()) {
      const skuLower = v.sku.trim().toLowerCase()
      if (seenSkus.has(skuLower)) {
        errors.push(`Duplicate SKU detected: ${v.sku.trim()}`)
        duplicateSkus.push(v.sku.trim())
      }
      seenSkus.add(skuLower)
    }
  })

  const hasVariantPrices = values.variants.some((v) => Number(v.price) > 0)
  const parentPrice = Number(values.price)
  if (values.hasVariants && values.variants.length > 0 && !hasVariantPrices && !(Number.isFinite(parentPrice) && parentPrice > 0)) {
    errors.push('At least one variant must have a price (or set the main price)')
  }

  return { errors, duplicateSkus }
}

export const productTitle = (values: ProductFormValues): string => values.title.trim() || 'Untitled product'
