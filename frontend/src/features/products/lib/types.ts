import type { ProductSort } from '@/types/admin'

export interface ListFilters {
  page: number
  search: string
  category: string
  brand: string
  supplier: string
  vendor: string
  collection: string
  stock: string
  productStatus: string
  label: string
  minPrice: string
  maxPrice: string
  dateFrom: string
  dateTo: string
  sort: ProductSort
}

export const DEFAULT_FILTERS: ListFilters = {
  page: 1,
  search: '',
  category: '',
  brand: '',
  supplier: '',
  vendor: '',
  collection: '',
  stock: '',
  productStatus: '',
  label: '',
  minPrice: '',
  maxPrice: '',
  dateFrom: '',
  dateTo: '',
  sort: 'newest',
}

export const SORT_OPTIONS: Array<{ value: ProductSort; label: string }> = [
  { value: 'newest', label: 'Newest' },
  { value: 'oldest', label: 'Oldest' },
  { value: 'price_asc', label: 'Price: Low to High' },
  { value: 'price_desc', label: 'Price: High to Low' },
  { value: 'stock_asc', label: 'Stock: Low to High' },
  { value: 'stock_desc', label: 'Stock: High to Low' },
  { value: 'title_asc', label: 'Alphabetical (A–Z)' },
]

export const STATUS_OPTIONS: Array<{ value: string; label: string }> = [
  { value: 'draft', label: 'Draft' },
  { value: 'published', label: 'Published' },
  { value: 'hidden', label: 'Hidden' },
  { value: 'archived', label: 'Archived' },
]

export const STOCK_FILTER_OPTIONS: Array<{ value: string; label: string }> = [
  { value: 'in_stock', label: 'In stock' },
  { value: 'low_stock', label: 'Low stock' },
  { value: 'out_of_stock', label: 'Out of stock' },
]

export const LABEL_OPTIONS: Array<{ value: string; label: string }> = [
  { value: 'featured', label: 'Featured' },
  { value: 'trending', label: 'Trending' },
  { value: 'flash_sale', label: 'Flash sale' },
  { value: 'new_arrival', label: 'New arrival' },
  { value: 'best_seller', label: 'Best seller' },
  { value: 'hot_deal', label: 'Hot deal' },
]

export const isFiltered = (f: ListFilters): boolean =>
  f.search !== '' ||
  f.category !== '' ||
  f.brand !== '' ||
  f.supplier !== '' ||
  f.vendor !== '' ||
  f.collection !== '' ||
  f.stock !== '' ||
  f.productStatus !== '' ||
  f.label !== '' ||
  f.minPrice !== '' ||
  f.maxPrice !== '' ||
  f.dateFrom !== '' ||
  f.dateTo !== '' ||
  f.sort !== 'newest'
