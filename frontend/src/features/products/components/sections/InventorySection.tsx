import { Boxes } from 'lucide-react'
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

  return (
    <FormSection title="Inventory" description="Stock levels, reorder alerts and fulfillment rules" icon={<Boxes className="h-4 w-4" />}>
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div className="space-y-2">
          <Label htmlFor="stock">Stock Quantity</Label>
          <Input id="stock" type="number" min="0" value={form.stock} onChange={(e) => set({ stock: e.target.value })} />
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
