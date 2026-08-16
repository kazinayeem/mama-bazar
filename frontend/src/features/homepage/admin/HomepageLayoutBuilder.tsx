import { Reorder, useDragControls } from 'framer-motion'
import {
  ChevronDown,
  EyeOff,
  GripVertical,
  Plus,
  Trash2,
} from 'lucide-react'
import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useGetCategoriesQuery } from '@/store/services/commerceApi'
import { SECTION_META, SECTION_ORDER } from './sectionMeta'
import type { HomepageSectionConfig } from '../../../types/homepage'

interface LayoutBuilderProps {
  sections: HomepageSectionConfig[]
  onChange: (sections: HomepageSectionConfig[]) => void
}

const SectionSettings = ({ section, onChange }: { section: HomepageSectionConfig; onChange: (next: HomepageSectionConfig) => void }) => {
  const canLimit = !['hero', 'trust_strip', 'promo_banner', 'why_choose_us', 'newsletter'].includes(section.type)
  const canColumns = ['categories'].includes(section.type)
  const { data: categories = [] } = useGetCategoriesQuery()

  const set = (patch: Partial<HomepageSectionConfig>) => onChange({ ...section, ...patch })

  return (
    <div className="grid gap-3 border-t border-border pt-3 sm:grid-cols-2 lg:grid-cols-4">
      {section.type === 'category_products' && (
        <div>
          <Label className="text-xs">Category</Label>
          <Select
            onValueChange={(v) => {
              const cat = categories.find((c) => String(c.id) === v)
              const patch: Partial<HomepageSectionConfig> = {
                categoryId: cat ? cat.id : null,
                categorySlug: cat ? cat.slug : null,
              }
              if (!section.title?.trim() && cat) patch.title = cat.name
              set(patch)
            }}
            value={section.categoryId ? String(section.categoryId) : 'none'}
          >
            <SelectTrigger className="mt-1 h-9">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="none">Select a category…</SelectItem>
              {categories.map((c) => (
                <SelectItem key={c.id} value={String(c.id)}>
                  {c.name}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      )}
      <div>
        <Label className="text-xs">Title</Label>
        <Input
          className="mt-1 h-9"
          onChange={(e) => set({ title: e.target.value })}
          placeholder={SECTION_META[section.type].label}
          value={section.title || ''}
        />
      </div>
      <div>
        <Label className="text-xs">Subtitle</Label>
        <Input className="mt-1 h-9" onChange={(e) => set({ subtitle: e.target.value })} placeholder="Optional" value={section.subtitle || ''} />
      </div>
      <div>
        <Label className="text-xs">Eyebrow label</Label>
        <Input className="mt-1 h-9" onChange={(e) => set({ eyebrow: e.target.value })} placeholder="Optional" value={section.eyebrow || ''} />
      </div>
      {canLimit && (
        <div>
          <Label className="text-xs">Max items</Label>
          <Input
            className="mt-1 h-9"
            max={24}
            min={1}
            onChange={(e) => set({ limit: Math.max(1, Math.min(24, Number(e.target.value) || 12)) })}
            type="number"
            value={section.limit || 12}
          />
        </div>
      )}
      {canColumns && (
        <div>
          <Label className="text-xs">Columns</Label>
          <Select onValueChange={(v) => set({ columns: Number(v) })} value={String(section.columns || 4)}>
            <SelectTrigger className="mt-1 h-9">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {[4, 5, 6].map((n) => (
                <SelectItem key={n} value={String(n)}>
                  {n} columns
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      )}
      <div>
        <Label className="text-xs">Background</Label>
        <Select onValueChange={(v) => set({ background: v as HomepageSectionConfig['background'] })} value={section.background || 'default'}>
          <SelectTrigger className="mt-1 h-9">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="default">White</SelectItem>
            <SelectItem value="muted">Soft grey</SelectItem>
            <SelectItem value="dark">Dark</SelectItem>
          </SelectContent>
        </Select>
      </div>
      <div>
        <Label className="text-xs">CTA text</Label>
        <Input
          className="mt-1 h-9"
          onChange={(e) => set({ ctaText: e.target.value })}
          placeholder="View all"
          value={section.ctaText || ''}
        />
      </div>
      <div>
        <Label className="text-xs">CTA link</Label>
        <Input
          className="mt-1 h-9"
          onChange={(e) => set({ ctaUrl: e.target.value })}
          placeholder="/shop"
          value={section.ctaUrl || ''}
        />
      </div>
    </div>
  )
}

const SectionRow = ({ section, index, onChange, onRemove }: { section: HomepageSectionConfig; index: number; onChange: (next: HomepageSectionConfig) => void; onRemove?: () => void }) => {
  const dragControls = useDragControls()
  const [open, setOpen] = useState(false)
  const meta = SECTION_META[section.type]

  return (
    <Reorder.Item className="list-none" dragControls={dragControls} dragListener={false} value={section}>
      <Card>
        <CardContent className="p-0">
          <div className="flex items-center gap-3 p-3">
            <button
              aria-label="Drag to reorder"
              className="cursor-grab touch-none text-muted-foreground transition hover:text-foreground active:cursor-grabbing"
              onPointerDown={(e) => dragControls.start(e)}
              type="button"
            >
              <GripVertical size={18} />
            </button>
            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
              <meta.icon size={18} />
            </span>
            <div className="min-w-0 flex-1">
              <div className="flex items-center gap-2">
                <p className="truncate text-sm font-semibold">{section.title?.trim() || meta.label}</p>
                <Badge className="hidden sm:inline-flex" variant="outline">
                  #{index + 1}
                </Badge>
              </div>
              <p className="truncate text-xs text-muted-foreground">{meta.description}</p>
            </div>
            {!section.enabled && <EyeOff className="h-4 w-4 text-muted-foreground" />}
            <Switch checked={section.enabled} onCheckedChange={(v) => onChange({ ...section, enabled: v })} />
            {onRemove && (
              <Button aria-label={`Remove ${meta.label} section`} onClick={onRemove} size="icon" variant="ghost">
                <Trash2 className="h-4 w-4 text-destructive" />
              </Button>
            )}
            <Button onClick={() => setOpen((prev) => !prev)} size="sm" variant="ghost">
              <ChevronDown className={`h-4 w-4 transition-transform ${open ? 'rotate-180' : ''}`} />
            </Button>
          </div>
          {open && (
            <div className="px-4 pb-4 pt-1">
              <SectionSettings onChange={onChange} section={section} />
            </div>
          )}
        </CardContent>
      </Card>
    </Reorder.Item>
  )
}

const HomepageLayoutBuilder = ({ sections, onChange }: LayoutBuilderProps) => {
  const knownTypes = new Set(SECTION_ORDER)
  const unknown = sections.filter((s) => !knownTypes.has(s.type))
  const known = sections.filter((s) => knownTypes.has(s.type))

  // Sections must keep the order stored in the config — sorting here would undo
  // every drag and made reordering appear broken.
  const handleReorder = (next: HomepageSectionConfig[]) => {
    const others = sections.filter((s) => !knownTypes.has(s.type))
    onChange([...next, ...others])
  }

  const addCategoryProducts = () => {
    const id = `category_products_${Date.now()}`
    onChange([
      ...sections,
      { id, type: 'category_products', enabled: true, title: '', limit: 6 },
    ])
  }

  return (
    <div className="space-y-3">
      <p className="text-sm text-muted-foreground">
        Drag to reorder, toggle to show or hide. Empty sections are automatically hidden on the storefront.
      </p>
      <Reorder.Group axis="y" onReorder={handleReorder} values={known}>
        <div className="space-y-2">
          {known.map((section, index) => (
            <SectionRow
              index={index}
              key={section.id}
              onChange={(next) => onChange(sections.map((s) => (s.id === next.id ? next : s)))}
              onRemove={section.type === 'category_products' ? () => onChange(sections.filter((s) => s.id !== section.id)) : undefined}
              section={section}
            />
          ))}
        </div>
      </Reorder.Group>
      {unknown.length > 0 && (
        <div className="space-y-2">
          {unknown.map((section) => (
            <SectionRow
              index={sections.indexOf(section)}
              key={section.id}
              onChange={(next) => onChange(sections.map((s) => (s.id === next.id ? next : s)))}
              section={section}
            />
          ))}
        </div>
      )}
      <Button className="w-full border-dashed" onClick={addCategoryProducts} variant="outline">
        <Plus className="h-4 w-4" /> Add Category Products section
      </Button>
    </div>
  )
}

export default HomepageLayoutBuilder
