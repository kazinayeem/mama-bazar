import { useMemo, useState } from 'react'
import { Info, ListFilter, RefreshCcw } from 'lucide-react'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import FormSection from '../FormSection'
import ReferenceSelect from '../ReferenceSelect'
import TagInput from '../TagInput'
import RichTextEditor from '../RichTextEditor'
import AddEntityModal, { type MasterEntity, type AddEntityResult } from '../AddEntityModal'
import { useProductForm } from '../ProductFormContext'
import { sanitizeSlugInput, slugValidationError, slugifyAscii } from '../../lib/slug'

const GeneralSection = () => {
  const { form, set, slugTouched, markSlugTouched, reference } = useProductForm()
  const [addEntity, setAddEntity] = useState<{ entity: MasterEntity; parent?: number | null } | null>(null)
  const [slugError, setSlugError] = useState<string | null>(null)

  const handleSlugChange = (value: string) => {
    markSlugTouched()
    set({ slug: sanitizeSlugInput(value) })
    setSlugError(slugValidationError(value))
  }

  const resetSlugFromTitle = () => {
    setSlugError(null)
    set({ slug: slugifyAscii(form.title) })
  }

  const rootCategories = useMemo(() => {
    const roots = reference.categories.filter((c) => !c.parentId)
    return roots.length ? roots : reference.categories
  }, [reference.categories])

  const isPublished = form.productStatus === 'published'
  // Keep every option reachable: sub-categories are not filtered by the selected category,
  // child categories are not filtered by the selected sub-category. Parent/child consistency
  // is enforced by the backend (validateProductRelations) instead of silently limiting options.
  const subCategories = useMemo(() => {
    const subs = reference.categories.filter((c) => c.parentId != null)
    return subs.length ? subs : reference.categories.filter((c) => !rootCategories.some((r) => r.id === c.id))
  }, [reference.categories, rootCategories])

  const childCategories = useMemo(() => {
    const children = reference.categories.filter((c) => c.parentId != null)
    return children.length ? children : reference.categories.filter((c) => !rootCategories.some((r) => r.id === c.id))
  }, [reference.categories, rootCategories])

  const handleCreated = (kind: MasterEntity, parent: number | null | undefined, result: AddEntityResult) => {
    const id = String(result.id)
    switch (kind) {
      case 'category':
        if (parent == null) {
          set({ categoryId: id, subCategoryId: '', childCategoryId: '' })
        } else if (parent === Number(form.categoryId)) {
          set({ subCategoryId: id, childCategoryId: '' })
        } else {
          set({ childCategoryId: id })
        }
        break
      case 'brand':
        set({ brandId: id })
        break
      case 'collection':
        set({ collectionId: id })
        break
      case 'vendor':
        set({ vendorId: id })
        break
      case 'supplier':
        set({ supplierId: id })
        break
      case 'color':
      case 'size':
        break
    }
  }

  const openAdd = (entity: MasterEntity, parent?: number | null) => setAddEntity({ entity, parent })

  return (
    <FormSection title="General Information" description="Core product details, organization and media" icon={<Info className="h-4 w-4" />}>
      <div className="grid gap-4 sm:grid-cols-2">
        <div className="sm:col-span-2">
          <Label htmlFor="product-title">
            Product Title <span className="text-destructive">*</span>
          </Label>
          <Input
            id="product-title"
            placeholder='e.g. Samsung 55" 4K Smart TV'
            value={form.title}
            onChange={(e) => set({ title: e.target.value })}
          />
        </div>

        <div className="sm:col-span-2">
          <Label htmlFor="product-slug">Slug (URL)</Label>
          <div className="relative">
            <Input
              id="product-slug"
              className={slugError ? 'border-destructive focus-visible:ring-destructive' : ''}
              placeholder="auto-generated from title, e.g. samsung-tv"
              value={form.slug}
              onChange={(e) => handleSlugChange(e.target.value)}
            />
            {slugTouched && form.slug && (
              <button
                aria-label="Reset slug from title"
                className="absolute right-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-md text-muted-foreground transition hover:bg-muted hover:text-foreground"
                onClick={resetSlugFromTitle}
                title="Reset from title"
                type="button"
              >
                <RefreshCcw className="h-3.5 w-3.5" />
              </button>
            )}
          </div>
          {slugError ? (
            <p className="mt-1 text-xs text-destructive" role="alert">
              {slugError}
            </p>
          ) : (
            <p className="mt-1 text-xs text-muted-foreground">
              Auto-generated from the title (Bangla is transliterated to English). You can edit it — only English
              letters, numbers and hyphens are allowed.
            </p>
          )}
        </div>

        <div className="sm:col-span-2">
          <Label htmlFor="product-short-description">Short Description</Label>
          <Textarea
            id="product-short-description"
            rows={2}
            placeholder="One-line product highlight shown on cards"
            value={form.shortDescription}
            onChange={(e) => set({ shortDescription: e.target.value })}
          />
        </div>

        <div className="sm:col-span-2">
          <Label>Full Description</Label>
          <RichTextEditor value={form.description} onChange={(html) => set({ description: html })} placeholder="Write a detailed description…" />
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <ReferenceSelect
          label="Category"
          value={form.categoryId}
          onChange={(v) => set({ categoryId: v === 'none' ? '' : v, subCategoryId: '', childCategoryId: '' })}
          options={rootCategories.map((c) => ({ value: String(c.id), label: c.name }))}
          loading={reference.categoriesLoading}
          placeholder="Select category"
          disabled={isPublished}
          onAddNew={() => openAdd('category', null)}
        />
        <ReferenceSelect
          label="Sub-category"
          value={form.subCategoryId}
          onChange={(v) => set({ subCategoryId: v === 'none' ? '' : v, childCategoryId: '' })}
          options={subCategories.map((c) => ({ value: String(c.id), label: c.name }))}
          loading={reference.categoriesLoading}
          placeholder={subCategories.length ? 'Select sub-category' : 'No sub-categories yet'}
          onAddNew={form.categoryId ? () => openAdd('category', Number(form.categoryId)) : undefined}
        />
        <ReferenceSelect
          label="Child category"
          value={form.childCategoryId}
          onChange={(v) => set({ childCategoryId: v === 'none' ? '' : v })}
          options={childCategories.map((c) => ({ value: String(c.id), label: c.name }))}
          loading={reference.categoriesLoading}
          placeholder={childCategories.length ? 'Select child category' : 'No child categories yet'}
          onAddNew={form.subCategoryId ? () => openAdd('category', Number(form.subCategoryId)) : undefined}
        />
        <ReferenceSelect
          label="Brand"
          value={form.brandId}
          onChange={(v) => set({ brandId: v === 'none' ? '' : v })}
          options={reference.brands.map((b) => ({ value: String(b.id), label: b.name }))}
          loading={reference.brandsLoading}
          placeholder="Select brand"
          onAddNew={() => openAdd('brand')}
        />
        <ReferenceSelect
          label="Collection"
          value={form.collectionId}
          onChange={(v) => set({ collectionId: v === 'none' ? '' : v })}
          options={reference.collections.map((c) => ({ value: String(c.id), label: c.name }))}
          loading={reference.collectionsLoading}
          placeholder="Select collection"
          onAddNew={() => openAdd('collection')}
        />
        <ReferenceSelect
          label="Vendor"
          value={form.vendorId}
          onChange={(v) => set({ vendorId: v === 'none' ? '' : v })}
          options={reference.vendors.map((v) => ({ value: String(v.id), label: v.name }))}
          loading={reference.vendorsLoading}
          placeholder="Select vendor"
          onAddNew={() => openAdd('vendor')}
        />
        <ReferenceSelect
          label="Supplier"
          value={form.supplierId}
          onChange={(v) => set({ supplierId: v === 'none' ? '' : v })}
          options={reference.suppliers.map((s) => ({ value: String(s.id), label: s.name }))}
          loading={reference.suppliersLoading}
          placeholder="Select supplier"
          onAddNew={() => openAdd('supplier')}
        />
        <div className="space-y-2">
          <Label htmlFor="product-sku">SKU</Label>
          <Input id="product-sku" placeholder="TV-55-4K" value={form.sku} onChange={(e) => set({ sku: e.target.value })} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="product-barcode">Barcode</Label>
          <Input id="product-barcode" placeholder="8801234567890" value={form.barcode} onChange={(e) => set({ barcode: e.target.value })} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="product-country">Country of Origin</Label>
          <Input id="product-country" placeholder="Bangladesh" value={form.countryOfOrigin} onChange={(e) => set({ countryOfOrigin: e.target.value })} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="product-warehouse">Warehouse</Label>
          <Input id="product-warehouse" placeholder="Main warehouse" value={form.warehouse} onChange={(e) => set({ warehouse: e.target.value })} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="product-video">Video URL</Label>
          <Input id="product-video" placeholder="https://youtube.com/watch?v=…" value={form.videoUrl} onChange={(e) => set({ videoUrl: e.target.value })} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="product-warranty">Warranty</Label>
          <Input id="product-warranty" placeholder="1 year" value={form.warranty} onChange={(e) => set({ warranty: e.target.value })} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="product-weight">Weight</Label>
          <Input id="product-weight" placeholder="5.5 kg" value={form.weight} onChange={(e) => set({ weight: e.target.value })} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="product-dimensions">Dimensions</Label>
          <Input id="product-dimensions" placeholder="123 x 71 x 8 cm" value={form.dimensions} onChange={(e) => set({ dimensions: e.target.value })} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="product-payment-phone">Payment Phone (bKash / Nagad)</Label>
          <Input id="product-payment-phone" placeholder="01711111111" value={form.paymentPhoneNumber} onChange={(e) => set({ paymentPhoneNumber: e.target.value })} />
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <TagInput label="Tags" value={form.tags} onChange={(tags) => set({ tags })} placeholder="Add tag, press Enter" />
        <TagInput label="Features" value={form.features} onChange={(features) => set({ features })} placeholder="Add feature, press Enter" />
      </div>

      <div className="space-y-2">
        <Label htmlFor="product-return-policy">Return Policy</Label>
        <Textarea id="product-return-policy" rows={3} placeholder="Return & exchange policy" value={form.returnPolicy} onChange={(e) => set({ returnPolicy: e.target.value })} />
      </div>

      <div className="flex items-center gap-2 rounded-md border bg-muted/40 px-3 py-2 text-xs text-muted-foreground">
        <ListFilter className="h-3.5 w-3.5" />
        Categories, brands, collections, vendors and suppliers are loaded live from the backend.
      </div>

      <AddEntityModal
        open={!!addEntity}
        onOpenChange={(v) => {
          if (!v) setAddEntity(null)
        }}
        entity={addEntity?.entity || 'category'}
        defaultParentId={addEntity?.parent}
        onCreated={(result) => {
          if (addEntity) handleCreated(addEntity.entity, addEntity.parent, result)
        }}
      />
    </FormSection>
  )
}

export default GeneralSection
