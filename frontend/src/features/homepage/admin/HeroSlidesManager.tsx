import { useState } from 'react'
import {
  ArrowDown,
  ArrowUp,
  Copy,
  ImagePlus,
  Loader2,
  Monitor,
  Pencil,
  Plus,
  Smartphone,
  Tablet,
  Trash2,
  X,
} from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Switch } from '@/components/ui/switch'
import { Card, CardContent } from '@/components/ui/card'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import MediaPicker from '@/components/admin/MediaPicker'
import type { HomepageHeroSlide } from '../../../types/homepage'

interface HeroSlidesManagerProps {
  slides: HomepageHeroSlide[]
  onChange: (slides: HomepageHeroSlide[]) => void
}

const uid = () => `slide-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 7)}`

const isValidButtonUrl = (url?: string) => {
  if (!url?.trim()) return true
  const value = url.trim()
  if (value.startsWith('/') || value.startsWith('#')) return true
  try {
    const parsed = new URL(value)
    return ['http:', 'https:', 'tel:', 'mailto:'].includes(parsed.protocol)
  } catch {
    return false
  }
}

const blankSlide = (): HomepageHeroSlide => ({
  id: uid(),
  desktopImage: '',
  status: 'active',
  priority: 0,
  alignment: 'left',
  overlay: true,
  overlayOpacity: 0.55,
})

const HeroSlidesManager = ({ slides, onChange }: HeroSlidesManagerProps) => {
  const [editing, setEditing] = useState<HomepageHeroSlide | null>(null)
  const [isNew, setIsNew] = useState(false)
  const [deleteTarget, setDeleteTarget] = useState<HomepageHeroSlide | null>(null)
  const [pickerTarget, setPickerTarget] = useState<null | 'desktopImage' | 'tabletImage' | 'mobileImage'>(null)
  const [saving, setSaving] = useState(false)

  const updateSlide = (patch: Partial<HomepageHeroSlide>) => {
    if (!editing) return
    setEditing({ ...editing, ...patch })
  }

  const openCreate = () => {
    setIsNew(true)
    setEditing(blankSlide())
  }

  const openEdit = (slide: HomepageHeroSlide) => {
    setIsNew(false)
    setEditing({ ...slide })
  }

  const handleSave = () => {
    if (!editing) return
    if (!editing.desktopImage) {
      toast.error('A desktop image is required')
      return
    }
    if ((editing.title?.trim().length ?? 0) > 120) {
      toast.error('Title must be 120 characters or fewer')
      return
    }
    if (!isValidButtonUrl(editing.primaryButtonUrl)) {
      toast.error('Primary button link must be a valid URL or internal path (e.g. /shop)')
      return
    }
    if (!isValidButtonUrl(editing.secondaryButtonUrl)) {
      toast.error('Secondary button link must be a valid URL or internal path (e.g. /shop)')
      return
    }
    setSaving(true)
    try {
      if (isNew) {
        onChange([...slides, { ...editing, priority: slides.length + 1 }])
        toast.success('Slide created')
      } else {
        onChange(slides.map((s) => (s.id === editing.id ? editing : s)))
        toast.success('Slide updated')
      }
      setEditing(null)
    } finally {
      setSaving(false)
    }
  }

  const handleDelete = () => {
    if (!deleteTarget) return
    onChange(slides.filter((s) => s.id !== deleteTarget.id))
    setDeleteTarget(null)
    toast.success('Slide deleted')
  }

  const move = (index: number, direction: -1 | 1) => {
    const target = index + direction
    if (target < 0 || target >= slides.length) return
    const next = [...slides]
    const [item] = next.splice(index, 1)
    next.splice(target, 0, item)
    onChange(next.map((s, i) => ({ ...s, priority: next.length - i })))
  }

  const duplicate = (slide: HomepageHeroSlide) => {
    onChange([...slides, { ...slide, id: uid(), title: slide.title ? `${slide.title} (Copy)` : undefined, priority: slides.length + 1 }])
    toast.success('Slide duplicated')
  }

  const pickFieldLabel: Record<string, string> = {
    desktopImage: 'Desktop (16:6)',
    tabletImage: 'Tablet',
    mobileImage: 'Mobile (4:5)',
  }

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <p className="text-sm text-muted-foreground">{slides.length} slides · drag-free reorder via arrows · first slide shows first</p>
        <Button onClick={openCreate} size="sm">
          <Plus className="h-4 w-4" /> Add Slide
        </Button>
      </div>

      {slides.length === 0 ? (
        <Card>
          <CardContent className="py-14 text-center">
            <p className="text-sm text-muted-foreground">No slides yet. Add your first hero slide — or leave empty to hide the carousel.</p>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-2">
          {slides.map((slide, index) => (
            <Card key={slide.id}>
              <CardContent className="flex flex-col gap-3 p-3 sm:flex-row sm:items-center">
                <div className="flex items-center gap-1">
                  <Button disabled={index === 0} onClick={() => move(index, -1)} size="icon" variant="ghost">
                    <ArrowUp className="h-4 w-4" />
                  </Button>
                  <Button disabled={index === slides.length - 1} onClick={() => move(index, 1)} size="icon" variant="ghost">
                    <ArrowDown className="h-4 w-4" />
                  </Button>
                </div>

                <div className="relative h-16 w-28 shrink-0 overflow-hidden rounded-md border bg-muted sm:h-14 sm:w-40">
                  {slide.desktopImage ? (
                    <img alt={slide.title || 'slide'} className="h-full w-full object-cover" src={slide.desktopImage} />
                  ) : (
                    <div className="flex h-full w-full items-center justify-center text-xs text-muted-foreground">No image</div>
                  )}
                </div>

                <div className="min-w-0 flex-1">
                  <div className="flex items-center gap-2">
                    <p className="truncate font-semibold">{slide.title?.trim() || slide.badge?.trim() || `Slide ${index + 1}`}</p>
                    <Badge variant={slide.status === 'active' ? 'success' : 'muted'}>{slide.status}</Badge>
                  </div>
                  <p className="truncate text-xs text-muted-foreground">
                    {slide.subtitle || slide.description || (slide.primaryButtonText ? `CTA: ${slide.primaryButtonText}` : 'No caption')}
                  </p>
                </div>

                <label className="flex items-center gap-2 text-xs font-semibold text-muted-foreground" title={slide.status === 'active' ? 'Slide is live on the homepage' : 'Slide is hidden from the homepage'}>
                  <Switch
                    checked={slide.status === 'active'}
                    onCheckedChange={(checked) =>
                      onChange(slides.map((s) => (s.id === slide.id ? { ...s, status: checked ? 'active' : 'inactive' } : s)))
                    }
                  />
                  Live
                </label>

                <div className="flex items-center gap-1">
                  <Button onClick={() => duplicate(slide)} size="icon" title="Duplicate" variant="ghost">
                    <Copy className="h-4 w-4" />
                  </Button>
                  <Button onClick={() => openEdit(slide)} size="icon" title="Edit" variant="ghost">
                    <Pencil className="h-4 w-4" />
                  </Button>
                  <Button
                    className="text-destructive hover:text-destructive"
                    onClick={() => setDeleteTarget(slide)}
                    size="icon"
                    title="Delete"
                    variant="ghost"
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      <Dialog open={!!editing} onOpenChange={(v) => !v && setEditing(null)}>
        <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
          <DialogHeader>
            <DialogTitle>{isNew ? 'Add Hero Slide' : 'Edit Hero Slide'}</DialogTitle>
          </DialogHeader>
          {editing && (
            <div className="space-y-4">
              <div className="rounded-md border p-3">
                <p className="mb-3 text-sm font-semibold">Responsive Images</p>
                <div className="grid grid-cols-3 gap-3">
                  {(['desktopImage', 'tabletImage', 'mobileImage'] as const).map((field) => (
                    <div key={field}>
                      <button
                        className="relative flex aspect-video w-full flex-col items-center justify-center gap-1 overflow-hidden rounded-md border-2 border-dashed text-muted-foreground hover:border-primary hover:text-primary"
                        onClick={() => setPickerTarget(field)}
                        type="button"
                      >
                        {editing[field] ? (
                          <>
                            <img alt="" className="absolute inset-0 h-full w-full object-cover" src={editing[field]} />
                            <button
                              className="absolute right-1 top-1 rounded-full bg-black/60 p-1 text-white"
                              onClick={(e) => {
                                e.stopPropagation()
                                updateSlide({ [field]: undefined })
                              }}
                              type="button"
                            >
                              <X className="h-3 w-3" />
                            </button>
                          </>
                        ) : (
                          <>
                            <ImagePlus className="h-5 w-5" />
                            <span className="px-1 text-center text-[10px]">{pickFieldLabel[field]}</span>
                          </>
                        )}
                      </button>
                      <p className="mt-1 flex items-center justify-center gap-1 text-[10px] text-muted-foreground">
                        {field === 'desktopImage' ? <Monitor className="h-3 w-3" /> : field === 'tabletImage' ? <Tablet className="h-3 w-3" /> : <Smartphone className="h-3 w-3" />}
                        {field === 'desktopImage' ? 'Desktop' : field === 'tabletImage' ? 'Tablet' : 'Mobile'}
                      </p>
                    </div>
                  ))}
                </div>
                {!editing.desktopImage && <p className="mt-2 text-xs text-destructive">Desktop image is required</p>}
              </div>

              <div className="grid gap-3 sm:grid-cols-2">
                <div>
                  <Label>Badge (small pill above title)</Label>
                  <Input className="mt-1" onChange={(e) => updateSlide({ badge: e.target.value })} placeholder="e.g. New Season" value={editing.badge || ''} />
                </div>
                <div>
                  <Label>Title</Label>
                  <Input className="mt-1" maxLength={120} onChange={(e) => updateSlide({ title: e.target.value })} placeholder="Slide headline" value={editing.title || ''} />
                </div>
                <div>
                  <Label>Subtitle</Label>
                  <Input className="mt-1" onChange={(e) => updateSlide({ subtitle: e.target.value })} placeholder="Supporting line" value={editing.subtitle || ''} />
                </div>
                <div>
                  <Label>Description</Label>
                  <Input className="mt-1" onChange={(e) => updateSlide({ description: e.target.value })} placeholder="Short paragraph" value={editing.description || ''} />
                </div>
                <div>
                  <Label>Primary button text</Label>
                  <Input className="mt-1" onChange={(e) => updateSlide({ primaryButtonText: e.target.value })} placeholder="Shop Now" value={editing.primaryButtonText || ''} />
                </div>
                <div>
                  <Label>Primary button link</Label>
                  <Input className="mt-1" onChange={(e) => updateSlide({ primaryButtonUrl: e.target.value })} placeholder="/shop or https://..." value={editing.primaryButtonUrl || ''} />
                </div>
                <div>
                  <Label>Secondary button text</Label>
                  <Input className="mt-1" onChange={(e) => updateSlide({ secondaryButtonText: e.target.value })} placeholder="Learn More" value={editing.secondaryButtonText || ''} />
                </div>
                <div>
                  <Label>Secondary button link</Label>
                  <Input className="mt-1" onChange={(e) => updateSlide({ secondaryButtonUrl: e.target.value })} placeholder="/shop?sort=price_asc" value={editing.secondaryButtonUrl || ''} />
                </div>
                <div>
                  <Label>Text alignment</Label>
                  <Select onValueChange={(v) => updateSlide({ alignment: v as HomepageHeroSlide['alignment'] })} value={editing.alignment || 'left'}>
                    <SelectTrigger className="mt-1">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="left">Left</SelectItem>
                      <SelectItem value="center">Center</SelectItem>
                      <SelectItem value="right">Right</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div>
                  <Label>Fallback background color</Label>
                  <Input
                    className="mt-1"
                    onChange={(e) => updateSlide({ backgroundColor: e.target.value })}
                    placeholder="#0f172a"
                    type="color"
                    value={editing.backgroundColor || '#0f172a'}
                  />
                </div>
              </div>

              <div className="flex flex-wrap items-end gap-6">
                <label className="flex items-center gap-2 text-sm">
                  <Switch checked={editing.overlay !== false} onCheckedChange={(v) => updateSlide({ overlay: v })} />
                  Dark overlay
                </label>
                {editing.overlay !== false && (
                  <div>
                    <Label className="text-xs">Overlay strength · {Math.round((editing.overlayOpacity ?? 0.55) * 100)}%</Label>
                    <input
                      className="mt-1 block w-56"
                      max={1}
                      min={0}
                      onChange={(e) => updateSlide({ overlayOpacity: Number(e.target.value) })}
                      step={0.05}
                      type="range"
                      value={editing.overlayOpacity ?? 0.55}
                    />
                  </div>
                )}
                <label className="flex items-center gap-2 text-sm">
                  <Switch checked={editing.status === 'active'} onCheckedChange={(v) => updateSlide({ status: v ? 'active' : 'inactive' })} />
                  Active
                </label>
              </div>
            </div>
          )}
          <DialogFooter>
            <Button onClick={() => setEditing(null)} variant="outline">
              Cancel
            </Button>
            <Button disabled={saving} onClick={handleSave}>
              {saving && <Loader2 className="h-4 w-4 animate-spin" />}
              {isNew ? 'Create Slide' : 'Save Changes'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <AlertDialog open={!!deleteTarget} onOpenChange={(v) => !v && setDeleteTarget(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete slide?</AlertDialogTitle>
            <AlertDialogDescription>This slide will be removed from the homepage carousel.</AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction className="bg-destructive text-destructive-foreground hover:bg-destructive/90" onClick={handleDelete}>
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      <MediaPicker
        multiple={false}
        onOpenChange={(v) => !v && setPickerTarget(null)}
        onSelect={(assets) => {
          if (pickerTarget && assets[0]) updateSlide({ [pickerTarget]: assets[0].url })
        }}
        open={pickerTarget !== null}
      />
    </div>
  )
}

export default HeroSlidesManager
