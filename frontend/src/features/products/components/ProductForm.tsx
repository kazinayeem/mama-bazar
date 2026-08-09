import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { ArrowLeft, Eye, Loader2, Save, Send } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { ProductFormContext, useReferenceData } from './ProductFormContext'
import GeneralSection from './sections/GeneralSection'
import PricingSection from './sections/PricingSection'
import InventorySection from './sections/InventorySection'
import VariantsSection from './sections/VariantsSection'
import ImagesSection from './sections/ImagesSection'
import SpecificationsSection from './sections/SpecificationsSection'
import SeoSection from './sections/SeoSection'
import RelatedProductsSection from './sections/RelatedProductsSection'
import {
  PRODUCT_STATUSES,
  formValuesToPayload,
  productTitle,
  type ProductFormValues,
  type ProductInput,
} from '../lib/productForm'

export type SaveMode = 'draft' | 'publish' | 'save'

interface ProductFormProps {
  initialValues: ProductFormValues
  isEditing: boolean
  submitting: boolean
  onCancel: () => void
  onSubmit: (payload: ProductInput, mode: SaveMode) => void
  onPreview?: () => void
}

const validate = (form: ProductFormValues): string[] => {
  const errors: string[] = []
  if (!form.title.trim()) errors.push('Product title is required')
  const price = Number(form.price)
  if (!form.price.trim() || Number.isNaN(price) || price <= 0) errors.push('Price must be a positive number')
  return errors
}

const ProductForm = ({ initialValues, isEditing, submitting, onCancel, onSubmit, onPreview }: ProductFormProps) => {
  const navigate = useNavigate()
  const reference = useReferenceData()
  const [form, setForm] = useState<ProductFormValues>(initialValues)

  const set = (patch: Partial<ProductFormValues>) => setForm((prev) => ({ ...prev, ...patch }))

  const categoriesByParent = useMemo(() => {
    const map = new Map<string | number, typeof reference.categories>()
    reference.categories.forEach((c) => {
      const key = c.parentId ?? 'root'
      if (!map.has(key)) map.set(key, [])
      map.get(key)!.push(c)
    })
    return map
  }, [reference])

  const contextValue = useMemo(
    () => ({ form, set, reference, categoriesByParent }),
    [form, reference, categoriesByParent],
  )

  const handleSubmit = (mode: SaveMode) => {
    if (mode === 'publish') {
      const errors = validate(form)
      if (errors.length > 0) {
        errors.forEach((e) => toast.error(e))
        return
      }
    }
    onSubmit(formValuesToPayload(form), mode)
  }

  const currentStatus = PRODUCT_STATUSES.find((s) => s.value === form.productStatus)

  return (
    <ProductFormContext.Provider value={contextValue}>
      <div className="mx-auto w-full max-w-[1400px] space-y-5">
        {/* Sticky top action bar */}
        <div className="sticky top-0 z-30 -mx-4 border-b bg-background/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6">
          <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div className="flex min-w-0 items-center gap-3">
              <Button
                type="button"
                variant="ghost"
                size="icon"
                onClick={onCancel}
                title="Back to products"
                className="shrink-0"
              >
                <ArrowLeft className="h-5 w-5" />
              </Button>
              <div className="min-w-0">
                <p className="truncate text-sm font-semibold sm:text-base">{productTitle(form)}</p>
                <p className="text-xs text-muted-foreground">{isEditing ? 'Editing product' : 'Creating a new product'}</p>
              </div>
              <Badge variant="secondary" className="hidden shrink-0 sm:inline-flex">
                {currentStatus?.label || 'Draft'}
              </Badge>
            </div>

            <div className="flex flex-wrap items-center gap-2">
              <Select value={form.productStatus} onValueChange={(v) => set({ productStatus: v as ProductFormValues['productStatus'] })}>
                <SelectTrigger className="w-36">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {PRODUCT_STATUSES.map((s) => (
                    <SelectItem key={s.value} value={s.value}>
                      {s.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>

              <Button type="button" variant="outline" disabled={submitting} onClick={onCancel}>
                Cancel
              </Button>

              <Button type="button" variant="outline" disabled={submitting || !isEditing} onClick={onPreview} title={!isEditing ? 'Save the product first to preview it' : 'Open read-only preview'}>
                {submitting ? <Loader2 className="mr-1 h-4 w-4 animate-spin" /> : <Eye className="mr-1 h-4 w-4" />}
                Preview
              </Button>

              <Button type="button" variant="secondary" disabled={submitting} onClick={() => handleSubmit('draft')}>
                {submitting ? <Loader2 className="mr-1 h-4 w-4 animate-spin" /> : <Save className="mr-1 h-4 w-4" />}
                Save Draft
              </Button>

              <Button type="button" disabled={submitting} onClick={() => handleSubmit('publish')}>
                {submitting ? <Loader2 className="mr-1 h-4 w-4 animate-spin" /> : <Send className="mr-1 h-4 w-4" />}
                {isEditing ? 'Save & Publish' : 'Publish'}
              </Button>
            </div>
          </div>
        </div>

        {/* Sections */}
        <div className="space-y-5 pb-10">
          <GeneralSection />
          <PricingSection />
          <InventorySection />
          <ImagesSection />
          <VariantsSection />
          <SpecificationsSection />
          <SeoSection />
          <RelatedProductsSection />
        </div>

        {/* Mobile bottom action bar */}
        <div className="sticky bottom-0 z-30 -mx-4 flex items-center gap-2 border-t bg-background/95 px-4 py-3 backdrop-blur lg:hidden">
          <Button type="button" variant="outline" className="flex-1" disabled={submitting} onClick={() => handleSubmit('draft')}>
            {submitting ? <Loader2 className="mr-1 h-4 w-4 animate-spin" /> : <Save className="mr-1 h-4 w-4" />}
            Draft
          </Button>
          <Button type="button" className="flex-1" disabled={submitting} onClick={() => handleSubmit('publish')}>
            {submitting ? <Loader2 className="mr-1 h-4 w-4 animate-spin" /> : <Send className="mr-1 h-4 w-4" />}
            {isEditing ? 'Save & Publish' : 'Publish'}
          </Button>
          <Button type="button" variant="ghost" size="icon" onClick={() => navigate('/admin/products')} aria-label="Back to products">
            <ArrowLeft className="h-5 w-5" />
          </Button>
        </div>
      </div>
    </ProductFormContext.Provider>
  )
}

export default ProductForm
