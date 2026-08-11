import { BadgeDollarSign, Info } from 'lucide-react'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import FormSection from '../FormSection'
import { useProductForm } from '../ProductFormContext'

interface PriceFieldProps {
  id: string
  label: string
  value: string
  placeholder?: string
  onChange: (value: string) => void
  required?: boolean
  hint?: string
}

const PriceField = ({ id, label, value, placeholder, onChange, required, hint }: PriceFieldProps) => (
  <div className="space-y-2">
    <Label htmlFor={id}>
      {label}
      {required && <span className="ml-0.5 text-destructive">*</span>}
    </Label>
    <Input id={id} type="number" step="0.01" min="0" placeholder={placeholder} value={value} onChange={(e) => onChange(e.target.value)} />
    {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
  </div>
)

const PricingSection = () => {
  const { form, set } = useProductForm()
  const hasVariants = form.variants.length > 0

  return (
    <FormSection
      title="Pricing"
      description={hasVariants ? 'Variant-based pricing — prices are managed per variant below' : 'Sale prices, costs and channel-specific pricing in Taka (৳)'}
      icon={<BadgeDollarSign className="h-4 w-4" />}
    >
      {hasVariants ? (
        <div className="rounded-lg border border-primary/30 bg-primary/5 p-4">
          <div className="flex items-start gap-3">
            <Info className="mt-0.5 h-4 w-4 shrink-0 text-primary" />
            <div>
              <p className="text-sm font-semibold text-primary">
                Variant pricing enabled
              </p>
              <p className="mt-1 text-sm text-muted-foreground">
                Prices are managed separately for each variant. Set the price, sale price, and stock for each variant in the Variants section below.
              </p>
              <p className="mt-1 text-xs text-muted-foreground">
                এই পণ্যের দাম প্রতিটি Variant অনুযায়ী নির্ধারণ করা হবে।
              </p>
            </div>
          </div>
        </div>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <PriceField id="price" label="Price" required placeholder="45000" value={form.price} onChange={(v) => set({ price: v })} />
          <PriceField id="sale-price" label="Sale Price" placeholder="42000" value={form.salePrice} onChange={(v) => set({ salePrice: v })} />
          <PriceField id="discount" label="Discount (%)" placeholder="10" value={form.discount} onChange={(v) => set({ discount: v })} />
          <PriceField id="cost-price" label="Cost Price" placeholder="35000" value={form.costPrice} onChange={(v) => set({ costPrice: v })} />
          <PriceField
            id="profit-margin"
            label="Profit Margin (%)"
            placeholder="Auto-calculated"
            value={form.profitMargin}
            onChange={(v) => set({ profitMargin: v })}
            hint="Automatically recalculated by the backend from sale and cost price."
          />
          <PriceField id="flash-sale-price" label="Flash Sale Price" placeholder="39990" value={form.flashSalePrice} onChange={(v) => set({ flashSalePrice: v })} />
          <PriceField id="wholesale-price" label="Wholesale Price" placeholder="38000" value={form.wholesalePrice} onChange={(v) => set({ wholesalePrice: v })} />
          <PriceField id="dealer-price" label="Dealer Price" placeholder="37000" value={form.dealerPrice} onChange={(v) => set({ dealerPrice: v })} />
          <PriceField id="tax" label="Tax (%)" placeholder="5" value={form.tax} onChange={(v) => set({ tax: v })} />
          <PriceField id="vat" label="VAT (%)" placeholder="15" value={form.vat} onChange={(v) => set({ vat: v })} />
          <PriceField id="shipping-charge" label="Shipping Charge" placeholder="100" value={form.shippingCharge} onChange={(v) => set({ shippingCharge: v })} />
          <PriceField id="cod-fee" label="COD Fee" placeholder="50" value={form.codFee} onChange={(v) => set({ codFee: v })} />
        </div>
      )}
    </FormSection>
  )
}

export default PricingSection
