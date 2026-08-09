import { Search } from 'lucide-react'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import { Switch } from '@/components/ui/switch'
import { Textarea } from '@/components/ui/textarea'
import FormSection from '../FormSection'
import { useProductForm } from '../ProductFormContext'
import type { ProductFormValues } from '../../lib/productForm'

const FLAG_SECTIONS: Array<{ key: keyof ProductFormValues; label: string; description: string }> = [
  { key: 'isFeatured', label: 'Featured', description: 'Show in featured sections' },
  { key: 'isTrending', label: 'Trending', description: 'Show in trending section' },
  { key: 'isFlashSale', label: 'Flash Sale', description: 'Show in flash sale section' },
  { key: 'isNewArrival', label: 'New Arrival', description: 'Show in new arrivals' },
  { key: 'isBestSeller', label: 'Best Seller', description: 'Show in best sellers' },
  { key: 'isLimitedEdition', label: 'Limited Edition', description: 'Mark as limited edition' },
  { key: 'isOfficial', label: 'Official', description: 'Official product' },
  { key: 'isHotDeal', label: 'Hot Deal', description: 'Show in hot deals' },
  { key: 'emiAvailable', label: 'EMI Available', description: 'Allow EMI payment' },
]

const SeoSection = () => {
  const { form, set } = useProductForm()

  return (
    <FormSection
      title="SEO & Marketing"
      description="Storefront flags and search engine metadata"
      icon={<Search className="h-4 w-4" />}
    >
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {FLAG_SECTIONS.map((flag) => (
          <div key={flag.label} className="flex items-center justify-between gap-3 rounded-lg border p-3">
            <div className="min-w-0">
              <p className="text-sm font-medium">{flag.label}</p>
              <p className="text-xs text-muted-foreground">{flag.description}</p>
            </div>
            <Switch
              checked={!!form[flag.key]}
              onCheckedChange={(v) => set({ [flag.key]: v })}
            />
          </div>
        ))}
      </div>

      <Separator />

      <div className="grid gap-4 sm:grid-cols-2">
        <div className="sm:col-span-2 space-y-2">
          <Label htmlFor="seo-title">SEO Title</Label>
          <Input id="seo-title" placeholder="Meta title" value={form.seoTitle} onChange={(e) => set({ seoTitle: e.target.value })} />
        </div>
        <div className="sm:col-span-2 space-y-2">
          <Label htmlFor="seo-description">SEO Description</Label>
          <Textarea id="seo-description" rows={2} placeholder="Meta description" value={form.seoDescription} onChange={(e) => set({ seoDescription: e.target.value })} />
        </div>
        <div className="sm:col-span-2 space-y-2">
          <Label htmlFor="seo-keywords">SEO Keywords</Label>
          <Input id="seo-keywords" placeholder="comma, separated, keywords" value={form.seoKeywords} onChange={(e) => set({ seoKeywords: e.target.value })} />
        </div>
        <div className="sm:col-span-2 space-y-2">
          <Label htmlFor="canonical-url">Canonical URL</Label>
          <Input id="canonical-url" placeholder="https://example.com/products/…" value={form.canonicalUrl} onChange={(e) => set({ canonicalUrl: e.target.value })} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="og-image">Open Graph Image URL</Label>
          <Input id="og-image" placeholder="https://…" value={form.ogImage} onChange={(e) => set({ ogImage: e.target.value })} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="twitter-image">Twitter Image URL</Label>
          <Input id="twitter-image" placeholder="https://…" value={form.twitterImage} onChange={(e) => set({ twitterImage: e.target.value })} />
        </div>
        <div className="sm:col-span-2 space-y-2">
          <Label htmlFor="structured-data">Structured Data (JSON-LD)</Label>
          <Textarea id="structured-data" rows={4} placeholder='{"@type":"Product",…}' value={form.structuredData} onChange={(e) => set({ structuredData: e.target.value })} />
        </div>
      </div>
    </FormSection>
  )
}

export default SeoSection
