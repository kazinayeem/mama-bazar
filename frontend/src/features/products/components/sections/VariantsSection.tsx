import { useState } from 'react'
import { Loader2, Palette, Plus, Ruler, Shuffle, X } from 'lucide-react'
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

  const updateVariant = (key: string, patch: Partial<VariantFormValue>) => {
    set({ variants: form.variants.map((v) => (v.key === key ? { ...v, ...patch } : v)) })
  }

  const removeVariant = (key: string) => {
    set({ variants: form.variants.filter((v) => v.key !== key) })
  }

  const toggleColor = (name: string) => {
    const next = form.colorOptions.includes(name) ? form.colorOptions.filter((c) => c !== name) : [...form.colorOptions, name]
    set({ colorOptions: next })
  }

  const toggleSize = (name: string) => {
    const next = form.sizeOptions.includes(name) ? form.sizeOptions.filter((s) => s !== name) : [...form.sizeOptions, name]
    set({ sizeOptions: next })
  }

  const addVariantFromOptions = () => {
    const combos: string[] = []
    form.sizeOptions.forEach((size) => {
      form.colorOptions.forEach((color) => {
        combos.push(`${color} / ${size}`)
      })
    })
    if (combos.length === 0) {
      toast.info('Select at least one size and one color option above to auto-generate variants')
      return
    }
    const existingNames = new Set(form.variants.map((v) => v.name.toLowerCase()))
    const additions = combos.filter((c) => !existingNames.has(c.toLowerCase()))
    if (additions.length === 0) {
      toast.info('Variants for these combinations already exist')
      return
    }
    set({
      variants: [
        ...form.variants,
        ...additions.map((name) => ({ ...newVariant(), name, options: JSON.stringify({ name }) })),
      ],
    })
    toast.success(`${additions.length} variant(s) generated`)
  }

  return (
    <FormSection
      title="Variants"
      description="Size and color options plus individually priced/stocked variants"
      icon={<Shuffle className="h-4 w-4" />}
    >
      <div className="grid gap-4 sm:grid-cols-2">
        {/* Color options from API */}
        <div className="space-y-2">
          <Label>Color Options</Label>
          {reference.colorsLoading ? (
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <Loader2 className="h-4 w-4 animate-spin" /> Loading colors…
            </div>
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
                  </button>
                )
              })}
            </div>
          )}
          <Button type="button" variant="outline" size="sm" onClick={() => setAddEntity('color')}>
            <Palette className="mr-1 h-3.5 w-3.5" /> Add Color
          </Button>
        </div>

        {/* Size options from API */}
        <div className="space-y-2">
          <Label>Size Options</Label>
          {reference.sizesLoading ? (
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <Loader2 className="h-4 w-4 animate-spin" /> Loading sizes…
            </div>
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

      <div className="flex flex-wrap items-center gap-2">
        <Button type="button" variant="outline" onClick={() => set({ variants: [...form.variants, newVariant()] })}>
          <Plus className="mr-1 h-4 w-4" /> Add Variant
        </Button>
        <Button type="button" variant="outline" onClick={addVariantFromOptions} title="Generate variants from selected color × size options">
          <Shuffle className="mr-1 h-4 w-4" /> Generate from options
        </Button>
      </div>

      {form.variants.length === 0 && (
        <p className="rounded-lg border border-dashed p-4 text-center text-sm text-muted-foreground">
          No variants yet. Variants let you sell this product with different options (color, size, etc.).
        </p>
      )}

      {form.variants.map((v, index) => (
        <div key={v.key} className="space-y-3 rounded-lg border p-4">
          <div className="flex items-center justify-between">
            <p className="text-sm font-semibold">Variant {index + 1}</p>
            <Button type="button" variant="ghost" size="sm" className="text-destructive" onClick={() => removeVariant(v.key)}>
              <X className="mr-1 h-3 w-3" /> Remove
            </Button>
          </div>
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div className="space-y-2">
              <Label>Variant Name</Label>
              <Input placeholder='e.g. "Samsung 55", "Red"' value={v.name} onChange={(e) => updateVariant(v.key, { name: e.target.value })} />
            </div>
            <div className="space-y-2">
              <Label>SKU</Label>
              <Input placeholder="TV-55-RED" value={v.sku} onChange={(e) => updateVariant(v.key, { sku: e.target.value })} />
            </div>
            <div className="space-y-2">
              <Label>Barcode</Label>
              <Input placeholder="880…" value={v.barcode} onChange={(e) => updateVariant(v.key, { barcode: e.target.value })} />
            </div>
            <div className="space-y-2">
              <Label>Price (৳)</Label>
              <Input type="number" step="0.01" min="0" placeholder="45000" value={v.price} onChange={(e) => updateVariant(v.key, { price: e.target.value })} />
            </div>
            <div className="space-y-2">
              <Label>Sale Price (৳)</Label>
              <Input type="number" step="0.01" min="0" placeholder="42000" value={v.salePrice} onChange={(e) => updateVariant(v.key, { salePrice: e.target.value })} />
            </div>
            <div className="space-y-2">
              <Label>Stock</Label>
              <Input type="number" min="0" placeholder="10" value={v.stock} onChange={(e) => updateVariant(v.key, { stock: e.target.value })} />
            </div>
            <div className="space-y-2 lg:col-span-2">
              <Label>Options (JSON)</Label>
              <Input placeholder='{"color":"Red","storage":"128GB"}' value={v.options} onChange={(e) => updateVariant(v.key, { options: e.target.value })} />
            </div>
            <div className="space-y-2">
              <Label>Thumbnail URL</Label>
              <Input placeholder="https://…" value={v.thumbnail} onChange={(e) => updateVariant(v.key, { thumbnail: e.target.value })} />
            </div>
            <div className="flex items-center justify-between rounded-lg border p-3">
              <div>
                <p className="text-sm font-medium">Availability</p>
                <p className="text-xs text-muted-foreground">Sell this variant</p>
              </div>
              <Switch checked={v.availability} onCheckedChange={(checked) => updateVariant(v.key, { availability: checked })} />
            </div>
          </div>
        </div>
      ))}

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
