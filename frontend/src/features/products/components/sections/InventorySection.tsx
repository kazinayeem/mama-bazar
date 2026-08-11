import { Boxes, Info } from 'lucide-react'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import FormSection from '../FormSection'
import { useProductForm } from '../ProductFormContext'
import { STOCK_STATUSES } from '../../lib/productForm'

const InventorySection = () => {
  const { form, set } = useProductForm()
  const hasVariants = form.variants.length > 0
  const totalVariantStock = form.variants.reduce((sum, v) => sum + (Number(v.stock) || 0), 0)

  return (
    <FormSection title="Inventory" description="Stock levels, reorder alerts and fulfillment rules" icon={<Boxes className="h-4 w-4" />}>
      {hasVariants && (
        <div className="mb-4 rounded-lg border border-primary/30 bg-primary/5 p-3">
          <div className="flex items-start gap-2">
            <Info className="mt-0.5 h-3.5 w-3.5 shrink-0 text-primary" />
            <div className="text-sm">
              <p className="font-medium text-primary">Variant stock: {totalVariantStock} total across {form.variants.length} variant(s)</p>
              <p className="mt-0.5 text-xs text-muted-foreground">Stock is managed per variant. Set stock quantities in the Variants section.</p>
            </div>
          </div>
        </div>
      )}

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div className="space-y-2">
          <Label htmlFor="stock">Stock Quantity{hasVariants ? ' (Base)' : ''}</Label>
          <Input id="stock" type="number" min="0" value={form.stock} onChange={(e) => set({ stock: e.target.value })} disabled={hasVariants} />
          {hasVariants && <p className="text-xs text-muted-foreground">Managed per variant</p>}
        </div>
        <div className="space-y-2">
          <Label htmlFor="low-stock-alert">Low Stock Alert</Label>
          <Input id="low-stock-alert" type="number" min="0" placeholder="5" value={form.lowStockAlert} onChange={(e) => set({ lowStockAlert: e.target.value })} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="stock-status">Stock Status</Label>
          <Select value={form.stockStatus} onValueChange={(v) => set({ stockStatus: v as typeof form.stockStatus })}>
            <SelectTrigger id="stock-status" className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {STOCK_STATUSES.map((s) => (
                <SelectItem key={s.value} value={s.value}>
                  {s.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <div className="space-y-2">
          <Label htmlFor="min-order">Minimum Order Qty</Label>
          <Input id="min-order" type="number" min="0" placeholder="1" value={form.minOrder} onChange={(e) => set({ minOrder: e.target.value })} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="max-order">Maximum Order Qty</Label>
          <Input id="max-order" type="number" min="0" placeholder="10" value={form.maxOrder} onChange={(e) => set({ maxOrder: e.target.value })} />
        </div>
      </div>

      <div className="grid gap-3 sm:grid-cols-3">
        {(
          [
            ['unlimitedStock', 'Unlimited Stock', 'Ignore stock counting'],
            ['backorder', 'Allow Backorder', 'Accept orders when out of stock'],
            ['trackInventory', 'Track Inventory', 'Decrement stock on orders'],
          ] as const
        ).map(([key, label, hint]) => (
          <div key={key} className="flex items-center justify-between gap-3 rounded-lg border p-3">
            <div className="min-w-0">
              <p className="text-sm font-medium">{label}</p>
              <p className="text-xs text-muted-foreground">{hint}</p>
            </div>
            <Switch checked={form[key]} onCheckedChange={(v) => set({ [key]: v })} />
          </div>
        ))}
      </div>
    </FormSection>
  )
}

export default InventorySection
