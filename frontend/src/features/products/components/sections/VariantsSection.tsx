import { useState, useMemo, useCallback } from 'react'
import { Loader2, Palette, Plus, Ruler, Shuffle, X, Trash2, CheckSquare, ImagePlus } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import FormSection from '../FormSection'
import AddEntityModal from '../AddEntityModal'
import { useProductForm } from '../ProductFormContext'
import type { VariantFormValue } from '../../lib/productForm'

const newVariant = (): VariantFormValue => ({
  key: crypto.randomUUID(),
  id: null,
  name: '',
  options: '',
  price: '',
  salePrice: '',
  sku: '',
  barcode: '',
  stock: '',
  thumbnail: '',
  availability: true,
})

const VariantsSection = () => {
  const { form, set, reference } = useProductForm()
  const [addEntity, setAddEntity] = useState<'color' | 'size' | null>(null)
  const [selectedVariants, setSelectedVariants] = useState<Set<string>>(new Set())
  const [bulkPrice, setBulkPrice] = useState('')
  const [bulkSalePrice, setBulkSalePrice] = useState('')
  const [bulkStock, setBulkStock] = useState('')
  const [bulkStatus, setBulkStatus] = useState<'active' | 'inactive' | ''>('')
  const [showBulkEdit, setShowBulkEdit] = useState(false)
  const [validationErrors, setValidationErrors] = useState<string[]>([])

  const updateVariant = useCallback((key: string, patch: Partial<VariantFormValue>) => {
    set({ variants: form.variants.map((v) => (v.key === key ? { ...v, ...patch } : v)) })
    setValidationErrors([])
  }, [form.variants, set])

  const removeVariant = useCallback((key: string) => {
    set({ variants: form.variants.filter((v) => v.key !== key) })
    setSelectedVariants((prev) => {
      const next = new Set(prev)
      next.delete(key)
      return next
    })
  }, [form.variants, set])

  const removeSelectedVariants = useCallback(() => {
    set({ variants: form.variants.filter((v) => !selectedVariants.has(v.key)) })
    setSelectedVariants(new Set())
    setShowBulkEdit(false)
    toast.success(`${selectedVariants.size} variant(s) removed`)
  }, [form.variants, selectedVariants, set])

  const toggleColor = useCallback((name: string) => {
    const next = form.colorOptions.includes(name) ? form.colorOptions.filter((c) => c !== name) : [...form.colorOptions, name]
    set({ colorOptions: next })
  }, [form.colorOptions, set])

  const toggleSize = useCallback((name: string) => {
    const next = form.sizeOptions.includes(name) ? form.sizeOptions.filter((s) => s !== name) : [...form.sizeOptions, name]
    set({ sizeOptions: next })
  }, [form.sizeOptions, set])

  const toggleSelectVariant = useCallback((key: string) => {
    setSelectedVariants((prev) => {
      const next = new Set(prev)
      if (next.has(key)) next.delete(key)
      else next.add(key)
      return next
    })
  }, [])

  const toggleSelectAll = useCallback(() => {
    setSelectedVariants((prev) => {
      if (form.variants.every((v) => prev.has(v.key))) {
        return new Set()
      }
      return new Set(form.variants.map((v) => v.key))
    })
  }, [form.variants])

  const generateVariants = useCallback(() => {
    setValidationErrors([])
    const errors: string[] = []
    if (form.colorOptions.length === 0 && form.sizeOptions.length === 0) {
      errors.push('Select at least one color or size option')
    }
    if (errors.length) {
      setValidationErrors(errors)
      return
    }

    const colors = form.colorOptions.length ? form.colorOptions : ['']
    const sizes = form.sizeOptions.length ? form.sizeOptions : ['']

    const combos: string[] = []
    const optionsCombos: Record<string, string>[] = []
    colors.forEach((color) => {
      sizes.forEach((size) => {
        const parts: string[] = []
        const opts: Record<string, string> = {}
        if (color) { parts.push(color); opts.color = color }
        if (size) { parts.push(size); opts.size = size }
        combos.push(parts.join(' / '))
        optionsCombos.push(opts)
      })
    })

    const existingNames = new Set(form.variants.map((v) => v.name.toLowerCase()))
    const additions = combos.filter((c) => c && !existingNames.has(c.toLowerCase()))
    if (additions.length === 0) {
      toast.info('Variants for these combinations already exist')
      return
    }

    const newVariants = additions.map((name, idx) => {
      const opts = optionsCombos[combos.indexOf(name)]
      return {
        ...newVariant(),
        name,
        options: JSON.stringify(opts),
      }
    })

    set({ variants: [...form.variants, ...newVariants] })
    toast.success(`${additions.length} variant(s) generated`)
    setSelectedVariants(new Set())
  }, [form.colorOptions, form.sizeOptions, form.variants, set])

  const applyBulkEdit = useCallback(() => {
    const patch: Partial<VariantFormValue> = {}
    if (bulkPrice) patch.price = bulkPrice
    if (bulkSalePrice) patch.salePrice = bulkSalePrice
    if (bulkStock) patch.stock = bulkStock
    if (bulkStatus) patch.availability = bulkStatus === 'active'

    if (Object.keys(patch).length === 0) {
      toast.info('Select at least one field to bulk edit')
      return
    }

    set({
      variants: form.variants.map((v) =>
        selectedVariants.has(v.key) ? { ...v, ...patch } : v
      ),
    })
    toast.success(`Updated ${selectedVariants.size} variant(s)`)
    setBulkPrice('')
    setBulkSalePrice('')
    setBulkStock('')
    setBulkStatus('')
    setShowBulkEdit(false)
  }, [bulkPrice, bulkSalePrice, bulkStock, bulkStatus, form.variants, selectedVariants, set])

  const validateVariants = useCallback(() => {
    const errors: string[] = []
    const seenNames = new Set<string>()
    const seenSkus = new Set<string>()

    form.variants.forEach((v, idx) => {
      if (!v.name.trim()) {
        errors.push(`Variant ${idx + 1}: Name is required`)
      } else {
        const nameLower = v.name.trim().toLowerCase()
        if (seenNames.has(nameLower)) {
          errors.push(`Variant ${idx + 1}: Duplicate variant name "${v.name}"`)
        }
        seenNames.add(nameLower)
      }
      if (v.price && Number(v.price) < 0) {
        errors.push(`Variant ${idx + 1}: Price cannot be negative`)
      }
      if (v.salePrice && Number(v.salePrice) < 0) {
        errors.push(`Variant ${idx + 1}: Sale price cannot be negative`)
      }
      if (v.price && v.salePrice && Number(v.salePrice) > Number(v.price)) {
        errors.push(`Variant ${idx + 1}: Sale price cannot exceed regular price`)
      }
      if (v.stock && Number(v.stock) < 0) {
        errors.push(`Variant ${idx + 1}: Stock cannot be negative`)
      }
      if (v.sku.trim()) {
        const skuLower = v.sku.trim().toLowerCase()
        if (seenSkus.has(skuLower)) {
          errors.push(`Variant ${idx + 1}: Duplicate SKU "${v.sku}"`)
        }
        seenSkus.add(skuLower)
      }
    })
    return errors
  }, [form.variants])

  const runValidation = useCallback(() => {
    const errors = validateVariants()
    if (errors.length === 0) {
      toast.success('All variants are valid')
    } else {
      setValidationErrors(errors)
      toast.error(`${errors.length} validation issue(s) found`)
    }
  }, [validateVariants])

  const isAllSelected = form.variants.length > 0 && form.variants.every((v) => selectedVariants.has(v.key))
  const isSomeSelected = selectedVariants.size > 0

  const thClass = 'px-2 py-2 text-left text-[10px] font-bold uppercase tracking-[0.15em] text-slate-500 border-b border-slate-200 dark:border-slate-700'
  const tdClass = 'px-2 py-2'

  return (
    <FormSection
      title="Variants"
      description="Create product variants with individual pricing, stock, and images"
      icon={<Shuffle className="h-4 w-4" />}
    >
      {/* Color & Size Selection */}
      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-2">
          <Label>Color Options</Label>
          {reference.colorsLoading ? (
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <Loader2 className="h-4 w-4 animate-spin" /> Loading colors…</div>
          ) : (
            <div className="flex flex-wrap gap-1.5">
              {reference.colors.length === 0 && <span className="text-xs text-muted-foreground">No colors in the catalog yet</span>}
              {reference.colors.map((color) => {
                const selected = form.colorOptions.includes(color.name)
                return (
                  <button
                    key={color.id}
                    type="button"
                    onClick={() => toggleColor(color.name)}
                    className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs transition-colors ${
                      selected ? 'border-primary bg-primary/10 text-primary' : 'border-border hover:border-primary/50'
                    }`}
                  >
                    <span className="h-3 w-3 rounded-full border" style={{ backgroundColor: color.hex }} />
                    {color.name}
                    {selected && <span className="text-primary ml-0.5">✓</span>}
                  </button>
                )
              })}
            </div>
          )}
          <Button type="button" variant="outline" size="sm" onClick={() => setAddEntity('color')}>
            <Palette className="mr-1 h-3.5 w-3.5" /> Add Color
          </Button>
        </div>

        <div className="space-y-2">
          <Label>Size Options</Label>
          {reference.sizesLoading ? (
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <Loader2 className="h-4 w-4 animate-spin" /> Loading sizes…</div>
          ) : (
            <div className="flex flex-wrap gap-1.5">
              {reference.sizes.length === 0 && <span className="text-xs text-muted-foreground">No sizes in the catalog yet</span>}
              {reference.sizes.map((size) => {
                const selected = form.sizeOptions.includes(size.name)
                return (
                  <button
                    key={size.id}
                    type="button"
                    onClick={() => toggleSize(size.name)}
                    className={`inline-flex items-center rounded-full border px-2.5 py-1 text-xs transition-colors ${
                      selected ? 'border-primary bg-primary/10 text-primary' : 'border-border hover:border-primary/50'
                    }`}
                  >
                    {size.name}
                    {selected && <span className="text-primary ml-0.5">✓</span>}
                  </button>
                )
              })}
            </div>
          )}
          <Button type="button" variant="outline" size="sm" onClick={() => setAddEntity('size')}>
            <Ruler className="mr-1 h-3.5 w-3.5" /> Add Size
          </Button>
        </div>
      </div>

      {/* Action Buttons */}
      <div className="flex flex-wrap items-center gap-2">
        <Button type="button" variant="outline" onClick={generateVariants} disabled={form.colorOptions.length === 0 && form.sizeOptions.length === 0}>
          <Shuffle className="mr-1 h-4 w-4" /> Generate Variants
        </Button>
        <Button type="button" variant="outline" onClick={() => set({ variants: [...form.variants, newVariant()] })}>
          <Plus className="mr-1 h-4 w-4" /> Add Variant
        </Button>
        {isSomeSelected && (
          <>
            <Button type="button" variant="outline" onClick={removeSelectedVariants} className="text-destructive border-destructive/30 hover:bg-destructive/10">
              <Trash2 className="mr-1 h-3.5 w-3.5" /> Delete Selected ({selectedVariants.size})
            </Button>
            <Button type="button" variant="outline" onClick={() => setShowBulkEdit(!showBulkEdit)}>
              {showBulkEdit ? 'Cancel Bulk Edit' : `Bulk Edit (${selectedVariants.size})`}
            </Button>
          </>
        )}
        {form.variants.length > 0 && (
          <Button type="button" variant="outline" onClick={runValidation} className="ml-auto">
            <CheckSquare className="mr-1 h-3.5 w-3.5" /> Validate
          </Button>
        )}
      </div>

      {/* Bulk Edit Panel */}
      {showBulkEdit && isSomeSelected && (
        <div className="space-y-3 rounded-lg border border-primary/30 bg-primary/5 p-4">
          <p className="text-sm font-semibold">Bulk Edit {selectedVariants.size} Selected Variants</p>
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div className="space-y-1.5">
              <Label className="text-xs">Set Price (৳)</Label>
              <Input type="number" step="0.01" min="0" placeholder="e.g. 1490" value={bulkPrice} onChange={(e) => setBulkPrice(e.target.value)} className="h-9 text-sm" />
            </div>
            <div className="space-y-1.5">
              <Label className="text-xs">Set Sale Price (৳)</Label>
              <Input type="number" step="0.01" min="0" placeholder="e.g. 1290" value={bulkSalePrice} onChange={(e) => setBulkSalePrice(e.target.value)} className="h-9 text-sm" />
            </div>
            <div className="space-y-1.5">
              <Label className="text-xs">Set Stock</Label>
              <Input type="number" min="0" placeholder="e.g. 20" value={bulkStock} onChange={(e) => setBulkStock(e.target.value)} className="h-9 text-sm" />
            </div>
            <div className="space-y-1.5">
              <Label className="text-xs">Set Status</Label>
              <select value={bulkStatus} onChange={(e) => setBulkStatus(e.target.value as any)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm">
                <option value="">No change</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
          <Button type="button" variant="outline" onClick={applyBulkEdit} size="sm">
            Apply Bulk Changes
          </Button>
        </div>
      )}

      {/* Validation Errors */}
      {validationErrors.length > 0 && (
        <div className="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-950">
          <p className="text-sm font-semibold text-red-700 dark:text-red-400">Validation Errors:</p>
          <ul className="mt-1 list-inside list-disc space-y-0.5">
            {validationErrors.map((err, i) => (
              <li key={i} className="text-xs text-red-600 dark:text-red-400">{err}</li>
            ))}
          </ul>
        </div>
      )}

      {/* Variant Table */}
      {form.variants.length > 0 && (
        <div className="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-slate-50 dark:bg-slate-800/50">
                <th className={`${thClass} w-8`}>
                  <input
                    type="checkbox"
                    checked={isAllSelected}
                    onChange={toggleSelectAll}
                    className="h-3.5 w-3.5 accent-primary"
                  />
                </th>
                <th className={thClass}>Variant</th>
                <th className={thClass}>SKU</th>
                <th className={thClass}>Price (৳)</th>
                <th className={thClass}>Sale Price (৳)</th>
                <th className={thClass}>Stock</th>
                <th className={thClass}>Image</th>
                <th className={thClass}>Active</th>
                <th className={`${thClass} w-8`}></th>
              </tr>
            </thead>
            <tbody>
              {form.variants.map((v, index) => (
                <tr key={v.key} className={`border-t border-slate-100 dark:border-slate-800 ${index % 2 === 0 ? 'bg-white dark:bg-slate-900' : 'bg-slate-50/50 dark:bg-slate-800/30'}`}>
                  <td className={tdClass}>
                    <input
                      type="checkbox"
                      checked={selectedVariants.has(v.key)}
                      onChange={() => toggleSelectVariant(v.key)}
                      className="h-3.5 w-3.5 accent-primary"
                    />
                  </td>
                  <td className={tdClass}>
                    <Input
                      className="h-8 w-40 text-xs"
                      placeholder="Black / M"
                      value={v.name}
                      onChange={(e) => updateVariant(v.key, { name: e.target.value })}
                    />
                  </td>
                  <td className={tdClass}>
                    <Input
                      className="h-8 w-24 text-xs"
                      placeholder="SKU"
                      value={v.sku}
                      onChange={(e) => updateVariant(v.key, { sku: e.target.value })}
                    />
                  </td>
                  <td className={tdClass}>
                    <Input
                      className="h-8 w-24 text-xs"
                      type="number" step="0.01" min="0"
                      placeholder="1490"
                      value={v.price}
                      onChange={(e) => updateVariant(v.key, { price: e.target.value })}
                    />
                  </td>
                  <td className={tdClass}>
                    <Input
                      className="h-8 w-24 text-xs"
                      type="number" step="0.01" min="0"
                      placeholder="1290"
                      value={v.salePrice}
                      onChange={(e) => updateVariant(v.key, { salePrice: e.target.value })}
                    />
                  </td>
                  <td className={tdClass}>
                    <Input
                      className="h-8 w-16 text-xs"
                      type="number" min="0"
                      placeholder="0"
                      value={v.stock}
                      onChange={(e) => updateVariant(v.key, { stock: e.target.value })}
                    />
                  </td>
                  <td className={tdClass}>
                    <Input
                      className="h-8 w-32 text-xs"
                      placeholder="Image URL"
                      value={v.thumbnail}
                      onChange={(e) => updateVariant(v.key, { thumbnail: e.target.value })}
                    />
                  </td>
                  <td className={tdClass}>
                    <Switch
                      checked={v.availability}
                      onCheckedChange={(checked) => updateVariant(v.key, { availability: checked })}
                    />
                  </td>
                  <td className={tdClass}>
                    <Button type="button" variant="ghost" size="sm" className="h-8 w-8 p-0 text-destructive hover:bg-destructive/10" onClick={() => removeVariant(v.key)}>
                      <X className="h-3.5 w-3.5" />
                    </Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {form.variants.length === 0 && (
        <p className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
          No variants yet. Select color and size options above, then click <strong>Generate Variants</strong> to automatically create all combinations.
        </p>
      )}

      <AddEntityModal
        open={!!addEntity}
        onOpenChange={(v) => {
          if (!v) setAddEntity(null)
        }}
        entity={addEntity || 'color'}
        onCreated={(result) => {
          if (addEntity === 'color') toggleColor(result.name)
          if (addEntity === 'size') toggleSize(result.name)
        }}
      />
    </FormSection>
  )
}

export default VariantsSection