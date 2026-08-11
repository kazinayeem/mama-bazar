import { createContext, useContext } from 'react'
import type { Brand, Collection, Color, Size, Supplier, Vendor } from '@/types/admin'
import type { Category } from '@/types'
import type { ProductFormValues } from '../lib/productForm'
import {
  useGetAdminBrandsQuery,
  useGetAdminCategoriesQuery,
  useGetAdminCollectionsQuery,
  useGetAdminColorsQuery,
  useGetAdminSizesQuery,
  useGetAdminSuppliersQuery,
  useGetAdminVendorsQuery,
} from '@/store/services/adminProductsApi'

export interface ReferenceData {
  categories: Category[]
  categoriesLoading: boolean
  brands: Brand[]
  brandsLoading: boolean
  collections: Collection[]
  collectionsLoading: boolean
  vendors: Vendor[]
  vendorsLoading: boolean
  suppliers: Supplier[]
  suppliersLoading: boolean
  colors: Color[]
  colorsLoading: boolean
  sizes: Size[]
  sizesLoading: boolean
}

export interface ProductFormContextValue {
  form: ProductFormValues
  set: (patch: Partial<ProductFormValues>) => void
  reference: ReferenceData
  categoriesByParent: Map<string | number, Category[]>
  variantErrors: string[]
  setVariantErrors: (errors: string[]) => void
}

export const ProductFormContext = createContext<ProductFormContextValue | null>(null)

export const useProductForm = (): ProductFormContextValue => {
  const ctx = useContext(ProductFormContext)
  if (!ctx) throw new Error('useProductForm must be used within ProductFormContext.Provider')
  return ctx
}

export const useReferenceData = (): ReferenceData => {
  const { data: categories, isLoading: categoriesLoading } = useGetAdminCategoriesQuery()
  const { data: brands, isLoading: brandsLoading } = useGetAdminBrandsQuery()
  const { data: collections, isLoading: collectionsLoading } = useGetAdminCollectionsQuery()
  const { data: vendors, isLoading: vendorsLoading } = useGetAdminVendorsQuery()
  const { data: suppliers, isLoading: suppliersLoading } = useGetAdminSuppliersQuery()
  const { data: colors, isLoading: colorsLoading } = useGetAdminColorsQuery()
  const { data: sizes, isLoading: sizesLoading } = useGetAdminSizesQuery()

  return {
    categories: categories || [],
    categoriesLoading,
    brands: brands || [],
    brandsLoading,
    collections: collections || [],
    collectionsLoading,
    vendors: vendors || [],
    vendorsLoading,
    suppliers: suppliers || [],
    suppliersLoading,
    colors: colors || [],
    colorsLoading,
    sizes: sizes || [],
    sizesLoading,
  }
}
