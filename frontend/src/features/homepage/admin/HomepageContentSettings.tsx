import { Plus, Trash2, X } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { iconByName } from '../iconMap'
import type {
  HomepageAnnouncement,
  HomepageContentItem,
  HomepageFlashSaleWindow,
  HomepageNewsletterSettings,
} from '../../../types/homepage'

const ICON_OPTIONS = ['Truck', 'ShieldCheck', 'BadgeCheck', 'RefreshCcw', 'Headphones', 'CreditCard', 'Wallet', 'Lock', 'Boxes', 'Package', 'Sparkles', 'ThumbsUp', 'HeartHandshake', 'Zap']

interface ContentSettingsProps {
  announcement: HomepageAnnouncement
  trustStrip: HomepageContentItem[]
  whyChooseUs: HomepageContentItem[]
  newsletter: HomepageNewsletterSettings
  flashSaleWindow: HomepageFlashSaleWindow
  popularSearches: string[]
  onChange: (patch: {
    announcement?: HomepageAnnouncement
    trustStrip?: HomepageContentItem[]
    whyChooseUs?: HomepageContentItem[]
    newsletter?: HomepageNewsletterSettings
    flashSaleWindow?: HomepageFlashSaleWindow
    popularSearches?: string[]
  }) => void
}

const IconPicker = ({ value, onChange }: { value?: string; onChange: (value?: string) => void }) => (
  <Select onValueChange={onChange} value={value || 'none'}>
    <SelectTrigger className="h-9">
      <SelectValue />
    </SelectTrigger>
    <SelectContent>
      <SelectItem value="none">No icon</SelectItem>
      {ICON_OPTIONS.map((name) => {
        const Icon = iconByName(name)
        return (
          <SelectItem key={name} value={name}>
            <span className="flex items-center gap-2">
              <Icon className="h-4 w-4" /> {name}
            </span>
          </SelectItem>
        )
      })}
    </SelectContent>
  </Select>
)

const ContentItemsEditor = ({
  items,
  onChange,
  placeholder,
}: {
  items: HomepageContentItem[]
  onChange: (items: HomepageContentItem[]) => void
  placeholder: string
}) => (
  <div className="space-y-2">
    {items.map((item, index) => (
      <div className="flex flex-col gap-2 rounded-md border p-3 sm:flex-row" key={index}>
        <div className="w-full sm:w-36">
          <Label className="text-xs">Icon</Label>
          <div className="mt-1">
            <IconPicker onChange={(icon) => onChange(items.map((it, i) => (i === index ? { ...it, icon: icon === 'none' ? undefined : icon } : it)))} value={item.icon} />
          </div>
        </div>
        <div className="flex-1">
          <Label className="text-xs">Title</Label>
          <Input
            className="mt-1 h-9"
            onChange={(e) => onChange(items.map((it, i) => (i === index ? { ...it, title: e.target.value } : it)))}
            placeholder={placeholder}
            value={item.title}
          />
        </div>
        <div className="flex-1">
          <Label className="text-xs">Text</Label>
          <Input
            className="mt-1 h-9"
            onChange={(e) => onChange(items.map((it, i) => (i === index ? { ...it, text: e.target.value } : it)))}
            placeholder="Supporting text"
            value={item.text || ''}
          />
        </div>
        <Button
          className="self-end"
          onClick={() => onChange(items.filter((_, i) => i !== index))}
          size="icon"
          variant="ghost"
        >
          <Trash2 className="h-4 w-4" />
        </Button>
      </div>
    ))}
    <Button
      onClick={() => onChange([...items, { icon: ICON_OPTIONS[0], title: '', text: '' }])}
      size="sm"
      variant="outline"
    >
      <Plus className="h-4 w-4" /> Add item
    </Button>
  </div>
)

const PopularSearchesEditor = ({ values, onChange }: { values: string[]; onChange: (values: string[]) => void }) => {
  const add = () => {
    const term = window.prompt('Enter a popular search term')
    if (term?.trim() && !values.includes(term.trim())) {
      onChange([...values, term.trim()].slice(0, 12))
    }
  }
  return (
    <div>
      <div className="flex flex-wrap gap-2">
        {values.map((term) => (
          <span className="inline-flex items-center gap-1.5 rounded-full border bg-muted px-3 py-1 text-xs font-semibold" key={term}>
            {term}
            <button aria-label={`Remove ${term}`} onClick={() => onChange(values.filter((t) => t !== term))} type="button">
              <X className="h-3 w-3" />
            </button>
          </span>
        ))}
      </div>
      <Button className="mt-3" onClick={add} size="sm" variant="outline">
        <Plus className="h-4 w-4" /> Add search term
      </Button>
    </div>
  )
}

const ContentSettings = ({ announcement, trustStrip, whyChooseUs, newsletter, flashSaleWindow, popularSearches, onChange }: ContentSettingsProps) => {
  const setAnnouncement = (patch: Partial<HomepageAnnouncement>) => onChange({ announcement: { ...announcement, ...patch } })
  const setNewsletter = (patch: Partial<HomepageNewsletterSettings>) => onChange({ newsletter: { ...newsletter, ...patch } })
  const setWindow = (patch: Partial<HomepageFlashSaleWindow>) => onChange({ flashSaleWindow: { ...flashSaleWindow, ...patch } })

  return (
    <div className="grid gap-4">
      <Card>
        <CardHeader>
          <CardTitle className="text-base">Announcement Bar</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <div className="flex items-center gap-2">
            <Switch checked={announcement.enabled} onCheckedChange={(v) => setAnnouncement({ enabled: v })} />
            <span className="text-sm">Show announcement bar on top of the site</span>
          </div>
          <Input
            onChange={(e) => setAnnouncement({ text: e.target.value })}
            placeholder="e.g. Free delivery on orders over ৳2,000"
            value={announcement.text}
          />
          <div className="grid gap-3 sm:grid-cols-2">
            <div>
              <Label className="text-xs">Background color</Label>
              <div className="mt-1 flex items-center gap-2">
                <input
                  className="h-8 w-10 cursor-pointer rounded border"
                  onChange={(e) => setAnnouncement({ backgroundColor: e.target.value })}
                  type="color"
                  value={announcement.backgroundColor || '#1e293b'}
                />
                <Input
                  className="h-8 flex-1"
                  onChange={(e) => setAnnouncement({ backgroundColor: e.target.value })}
                  value={announcement.backgroundColor || ''}
                />
              </div>
            </div>
            <div>
              <Label className="text-xs">Text color</Label>
              <div className="mt-1 flex items-center gap-2">
                <input
                  className="h-8 w-10 cursor-pointer rounded border"
                  onChange={(e) => setAnnouncement({ textColor: e.target.value })}
                  type="color"
                  value={announcement.textColor || '#ffffff'}
                />
                <Input
                  className="h-8 flex-1"
                  onChange={(e) => setAnnouncement({ textColor: e.target.value })}
                  value={announcement.textColor || ''}
                />
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Trust Strip</CardTitle>
          <p className="text-xs text-muted-foreground">Icon perks shown right below the hero.</p>
        </CardHeader>
        <CardContent>
          <ContentItemsEditor items={trustStrip} onChange={(items) => onChange({ trustStrip: items })} placeholder="e.g. Fast delivery" />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Why Choose Us</CardTitle>
          <p className="text-xs text-muted-foreground">Value proposition cards near the bottom of the homepage.</p>
        </CardHeader>
        <CardContent>
          <ContentItemsEditor items={whyChooseUs} onChange={(items) => onChange({ whyChooseUs: items })} placeholder="e.g. Authentic products" />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Flash Sale Window</CardTitle>
          <p className="text-xs text-muted-foreground">
            Optional date range that powers the Flash Deals countdown. Leave empty for a daily sale ending at midnight.
          </p>
        </CardHeader>
        <CardContent className="grid gap-3 sm:grid-cols-3">
          <div className="flex items-center gap-2 sm:col-span-3">
            <Switch checked={flashSaleWindow.enabled} onCheckedChange={(v) => setWindow({ enabled: v })} />
            <span className="text-sm">Enable scheduled flash sale window</span>
          </div>
          <div>
            <Label className="text-xs">Starts</Label>
            <Input
              className="mt-1 h-9"
              onChange={(e) => setWindow({ start: e.target.value || null })}
              type="datetime-local"
              value={flashSaleWindow.start ? flashSaleWindow.start.slice(0, 16) : ''}
            />
          </div>
          <div>
            <Label className="text-xs">Ends</Label>
            <Input
              className="mt-1 h-9"
              onChange={(e) => setWindow({ end: e.target.value || null })}
              type="datetime-local"
              value={flashSaleWindow.end ? flashSaleWindow.end.slice(0, 16) : ''}
            />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Newsletter Section</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-3 sm:grid-cols-2">
          <div className="flex items-center gap-2 sm:col-span-2">
            <Switch checked={newsletter.enabled} onCheckedChange={(v) => setNewsletter({ enabled: v })} />
            <span className="text-sm">Show newsletter signup block</span>
          </div>
          <div>
            <Label className="text-xs">Title</Label>
            <Input className="mt-1 h-9" onChange={(e) => setNewsletter({ title: e.target.value })} value={newsletter.title || ''} />
          </div>
          <div>
            <Label className="text-xs">Button text</Label>
            <Input className="mt-1 h-9" onChange={(e) => setNewsletter({ buttonText: e.target.value })} value={newsletter.buttonText || ''} />
          </div>
          <div className="sm:col-span-2">
            <Label className="text-xs">Subtitle</Label>
            <Input className="mt-1 h-9" onChange={(e) => setNewsletter({ subtitle: e.target.value })} value={newsletter.subtitle || ''} />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Popular Searches</CardTitle>
          <p className="text-xs text-muted-foreground">Suggested chips shown in the search bar overlay.</p>
        </CardHeader>
        <CardContent>
          <PopularSearchesEditor onChange={(values) => onChange({ popularSearches: values })} values={popularSearches} />
        </CardContent>
      </Card>

      <div className="flex justify-end">
        <Button
          onClick={() => {
            navigator.clipboard?.writeText('')
            toast.info('Tip: switch to the Layout tab to reorder homepage sections.')
          }}
          variant="ghost"
        >
          Need help?
        </Button>
      </div>
    </div>
  )
}

export default ContentSettings
