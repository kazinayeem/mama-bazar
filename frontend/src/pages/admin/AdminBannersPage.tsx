import { useCallback, useEffect, useState } from 'react'
import { ArrowDown, ArrowUp, ImagePlus, Loader2, Monitor, Pencil, Plus, Smartphone, Tablet, Trash2, X } from 'lucide-react'
import { toast } from 'sonner'
import AdminLayout from '../../components/layout/AdminLayout'
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
import { Skeleton } from '@/components/ui/skeleton'
import MediaPicker from '@/components/admin/MediaPicker'
import type { Banner, BannerPosition } from '@/types/admin'
import { SEO } from '../../components/common/SEO'
import {
  useCreateBannerMutation,
  useDeleteBannerMutation,
  useGetBannersQuery,
  useUpdateBannerMutation,
} from '@/store/services/adminProductsApi'

const POSITION_LABELS: Record<BannerPosition, string> = {
  hero: 'Hero Slider',
  banner: 'Banner',
  promo: 'Promo',
  sidebar: 'Sidebar',
}

const AdminBannersPage = () => {
  const { data: bannersData, isLoading: loading, refetch } = useGetBannersQuery()
  const [createBanner] = useCreateBannerMutation()
  const [updateBanner] = useUpdateBannerMutation()
  const [deleteBanner] = useDeleteBannerMutation()
  const [banners, setBanners] = useState<Banner[]>([])
  const [dialogOpen, setDialogOpen] = useState(false)
  const [editing, setEditing] = useState<Banner | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<Banner | null>(null)
  const [pickerTarget, setPickerTarget] = useState<null | 'image' | 'imageTablet' | 'imageMobile'>(null)
  const [saving, setSaving] = useState(false)

  const [title, setTitle] = useState('')
  const [subtitle, setSubtitle] = useState('')
  const [link, setLink] = useState('')
  const [position, setPosition] = useState<BannerPosition>('hero')
  const [buttonText, setButtonText] = useState('')
  const [priority, setPriority] = useState(0)
  const [status, setStatus] = useState<'active' | 'inactive'>('active')
  const [images, setImages] = useState<{ image?: string; imageTablet?: string; imageMobile?: string }>({})

  useEffect(() => {
    if (!bannersData) return
    setBanners([...bannersData].sort((a, b) => b.priority - a.priority))
  }, [bannersData])

  const load = useCallback(() => {
    refetch()
  }, [refetch])

  const openCreate = () => {
    setEditing(null)
    setTitle('')
    setSubtitle('')
    setLink('')
    setPosition('hero')
    setButtonText('')
    setPriority(0)
    setStatus('active')
    setImages({})
    setDialogOpen(true)
  }

  const openEdit = (banner: Banner) => {
    setEditing(banner)
    setTitle(banner.title || '')
    setSubtitle(banner.subtitle || '')
    setLink(banner.link || '')
    setPosition(banner.position)
    setButtonText(banner.buttonText || '')
    setPriority(banner.priority)
    setStatus(banner.status)
    setImages({
      image: banner.image,
      imageTablet: banner.imageTablet || undefined,
      imageMobile: banner.imageMobile || undefined,
    })
    setDialogOpen(true)
  }

  const handleSave = async () => {
    if (!images.image) {
      toast.error('A main image is required')
      return
    }
    setSaving(true)
    try {
      const payload = {
        title,
        subtitle,
        link,
        position,
        buttonText,
        priority,
        status,
        ...images,
      }
      if (editing) {
        await updateBanner({ id: editing.id, payload }).unwrap()
        toast.success('Banner updated')
      } else {
        await createBanner(payload).unwrap()
        toast.success('Banner created')
      }
      setDialogOpen(false)
      load()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  const handleDelete = async () => {
    if (!deleteTarget) return
    try {
      await deleteBanner(deleteTarget.id).unwrap()
      toast.success('Banner deleted')
      setDeleteTarget(null)
      load()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Delete failed')
    }
  }

  const move = async (index: number, direction: -1 | 1) => {
    const target = index + direction
    if (target < 0 || target >= banners.length) return
    const next = [...banners]
    const [item] = next.splice(index, 1)
    next.splice(target, 0, item)
    setBanners(next)
    // persist priority = position index reversed
    const updates = next.map((b, i) => ({ ...b, priority: next.length - i }))
    try {
      for (const b of updates) {
        await updateBanner({ id: b.id, payload: { priority: b.priority } }).unwrap()
      }
    } catch {
      toast.error('Reorder failed to save')
      load()
    }
  }

  const pickFieldLabel: Record<string, string> = {
    image: 'Main image',
    imageTablet: 'Tablet image',
    imageMobile: 'Mobile image',
  }

  return (
    <AdminLayout>
      <SEO title="Manage Banners" description="Create and manage promotional banners." url="/admin/banners" />
      <div className="space-y-4">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Banners</h1>
            <p className="text-sm text-muted-foreground">{banners.length} banners · hero slider, promos and more</p>
          </div>
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4" /> Add Banner
          </Button>
        </div>

        {loading ? (
          <div className="space-y-4">
            {Array.from({ length: 3 }).map((_, i) => (
              <Skeleton key={i} className="h-36 w-full" />
            ))}
          </div>
        ) : banners.length === 0 ? (
          <Card>
            <CardContent className="py-16 text-center">
              <p className="text-sm text-muted-foreground">No banners yet. Add your first banner.</p>
            </CardContent>
          </Card>
        ) : (
          <div className="space-y-4">
            {banners.map((banner, index) => (
              <Card key={banner.id}>
                <CardContent className="flex flex-col gap-4 p-4 sm:flex-row sm:items-center">
                  <div className="flex items-center gap-1 self-start sm:self-auto">
                    <Button variant="ghost" size="icon" disabled={index === 0} onClick={() => move(index, -1)}>
                      <ArrowUp className="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="icon" disabled={index === banners.length - 1} onClick={() => move(index, 1)}>
                      <ArrowDown className="h-4 w-4" />
                    </Button>
                  </div>

                  <div className="relative h-24 w-40 shrink-0 overflow-hidden rounded-md border bg-muted">
                    <img src={banner.image} alt={banner.title || 'banner'} className="h-full w-full object-cover" />
                    {banner.imageMobile && (
                      <span className="absolute bottom-1 right-1 rounded bg-black/60 p-1 text-white">
                        <Smartphone className="h-3 w-3" />
                      </span>
                    )}
                    {banner.imageTablet && (
                      <span className="absolute bottom-1 right-6 rounded bg-black/60 p-1 text-white">
                        <Tablet className="h-3 w-3" />
                      </span>
                    )}
                  </div>

                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <p className="truncate font-semibold">{banner.title || 'Untitled banner'}</p>
                      <Badge variant={banner.status === 'active' ? 'success' : 'muted'}>
                        {banner.status}
                      </Badge>
                    </div>
                    <p className="text-xs text-muted-foreground">{POSITION_LABELS[banner.position]}</p>
                    {banner.subtitle && <p className="mt-1 line-clamp-1 text-xs text-muted-foreground">{banner.subtitle}</p>}
                  </div>

                  <div className="flex items-center gap-1">
                    <Button variant="ghost" size="icon" onClick={() => openEdit(banner)}>
                      <Pencil className="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="icon" className="text-destructive" onClick={() => setDeleteTarget(banner)}>
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </div>

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>{editing ? 'Edit Banner' : 'Add Banner'}</DialogTitle>
          </DialogHeader>
          <div className="space-y-4">
            <div className="grid gap-2 sm:grid-cols-2">
              <div>
                <Label htmlFor="bn-title">Title</Label>
                <Input id="bn-title" value={title} onChange={(e) => setTitle(e.target.value)} />
              </div>
              <div>
                <Label htmlFor="bn-subtitle">Subtitle</Label>
                <Input id="bn-subtitle" value={subtitle} onChange={(e) => setSubtitle(e.target.value)} />
              </div>
              <div>
                <Label htmlFor="bn-position">Position</Label>
                <Select value={position} onValueChange={(v) => setPosition(v as BannerPosition)}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {Object.entries(POSITION_LABELS).map(([value, label]) => (
                      <SelectItem key={value} value={value}>
                        {label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label htmlFor="bn-priority">Priority</Label>
                <Input id="bn-priority" type="number" value={priority} onChange={(e) => setPriority(Number(e.target.value))} />
              </div>
              <div className="sm:col-span-2">
                <Label htmlFor="bn-link">Link</Label>
                <Input id="bn-link" value={link} onChange={(e) => setLink(e.target.value)} placeholder="/shop or https://..." />
              </div>
              <div className="sm:col-span-2">
                <Label htmlFor="bn-cta">Button Text</Label>
                <Input id="bn-cta" value={buttonText} onChange={(e) => setButtonText(e.target.value)} placeholder="Shop Now" />
              </div>
            </div>

            <div className="rounded-md border p-3">
              <p className="mb-3 text-sm font-semibold">Responsive Images</p>
              <div className="grid grid-cols-3 gap-3">
                {(['image', 'imageTablet', 'imageMobile'] as const).map((field) => (
                  <div key={field} className="text-center">
                    <button
                      type="button"
                      onClick={() => setPickerTarget(field)}
                      className="relative flex aspect-video w-full flex-col items-center justify-center gap-1 overflow-hidden rounded-md border-2 border-dashed text-muted-foreground hover:border-primary hover:text-primary"
                      aria-label={pickFieldLabel[field]}
                    >
                      {images[field] ? (
                        <>
                          <img src={images[field]} alt="" className="absolute inset-0 h-full w-full object-cover" />
                          <span
                            role="button"
                            tabIndex={0}
                            onClick={(e) => {
                              e.stopPropagation()
                              setImages((prev) => ({ ...prev, [field]: undefined }))
                            }}
                            onKeyDown={(e) => {
                              if (e.key === 'Enter' || e.key === ' ') {
                                e.stopPropagation()
                                setImages((prev) => ({ ...prev, [field]: undefined }))
                              }
                            }}
                            className="absolute right-1 top-1 rounded-full bg-black/60 p-1 text-white"
                          >
                            <X className="h-3 w-3" />
                          </span>
                        </>
                      ) : (
                        <>
                          <ImagePlus className="h-5 w-5" />
                          <span className="text-[10px]">{pickFieldLabel[field]}</span>
                        </>
                      )}
                    </button>
                    <p className="mt-1 flex items-center justify-center gap-1 text-[10px] text-muted-foreground">
                      {field === 'image' ? <Monitor className="h-3 w-3" /> : field === 'imageTablet' ? <Tablet className="h-3 w-3" /> : <Smartphone className="h-3 w-3" />}
                      {field === 'image' ? 'Desktop' : field === 'imageTablet' ? 'Tablet' : 'Mobile'}
                    </p>
                  </div>
                ))}
              </div>
              {!images.image && <p className="mt-2 text-xs text-destructive">Main image is required</p>}
            </div>

            <label className="flex items-center gap-2 text-sm">
              <Switch checked={status === 'active'} onCheckedChange={(v) => setStatus(v ? 'active' : 'inactive')} />
              Active
            </label>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogOpen(false)}>Cancel</Button>
            <Button onClick={handleSave} disabled={saving}>
              {saving && <Loader2 className="h-4 w-4 animate-spin" />}
              {editing ? 'Save Changes' : 'Create Banner'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <AlertDialog open={!!deleteTarget} onOpenChange={(v) => !v && setDeleteTarget(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete banner?</AlertDialogTitle>
            <AlertDialogDescription>This banner will be removed from the storefront.</AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete} className="bg-destructive text-destructive-foreground hover:bg-destructive/90">
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      <MediaPicker
        open={pickerTarget !== null}
        onOpenChange={(v) => !v && setPickerTarget(null)}
        multiple={false}
        onSelect={(assets) => {
          if (pickerTarget && assets[0]) {
            setImages((prev) => ({ ...prev, [pickerTarget]: assets[0].url }))
          }
        }}
      />
    </AdminLayout>
  )
}

export default AdminBannersPage
