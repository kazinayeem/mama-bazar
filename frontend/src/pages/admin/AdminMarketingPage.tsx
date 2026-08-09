import { useCallback, useEffect, useMemo, useState } from 'react'
import { Calendar, ImagePlus, Loader2, Megaphone, Plus, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import AdminLayout from '../../components/layout/AdminLayout'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Switch } from '@/components/ui/switch'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
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
import { adminApi } from '@/lib/adminApi'
import MediaPicker from '@/components/admin/MediaPicker'
import type { Banner } from '@/types/admin'
import type { AdminCoupon } from '@/types'
import { SEO } from '../../components/common/SEO'

const AdminMarketingPage = () => {
  const [banners, setBanners] = useState<Banner[]>([])
  const [coupons, setCoupons] = useState<AdminCoupon[]>([])
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)

  const [bannerDialogOpen, setBannerDialogOpen] = useState(false)
  const [pickerOpen, setPickerOpen] = useState(false)
  const [bannerTitle, setBannerTitle] = useState('')
  const [bannerSubtitle, setBannerSubtitle] = useState('')
  const [bannerLink, setBannerLink] = useState('')
  const [bannerButtonText, setBannerButtonText] = useState('')
  const [bannerImage, setBannerImage] = useState('')
  const [deleteBannerTarget, setDeleteBannerTarget] = useState<Banner | null>(null)

  const [campaignDialogOpen, setCampaignDialogOpen] = useState(false)
  const [code, setCode] = useState('')
  const [discountType, setDiscountType] = useState<'percentage' | 'fixed'>('percentage')
  const [discountValue, setDiscountValue] = useState('')
  const [minOrderAmount, setMinOrderAmount] = useState('')
  const [expiryDate, setExpiryDate] = useState('')

  const load = useCallback(async () => {
    setLoading(true)
    try {
      const [b, c] = await Promise.all([adminApi.getBanners(), adminApi.getCoupons()])
      setBanners(b)
      setCoupons(c)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to load marketing data')
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load()
  }, [load])

  const heroBanners = useMemo(() => banners.filter((b) => b.position === 'hero'), [banners])
  const activeBanners = useMemo(() => banners.filter((b) => b.status === 'active').length, [banners])
  const activeCoupons = useMemo(() => coupons.filter((c) => c.status === 'active').length, [coupons])

  const openBannerDialog = () => {
    setBannerTitle('')
    setBannerSubtitle('')
    setBannerLink('')
    setBannerButtonText('')
    setBannerImage('')
    setBannerDialogOpen(true)
  }

  const handleSaveBanner = async () => {
    if (!bannerTitle.trim() || !bannerImage) {
      toast.error('A title and an image are required')
      return
    }
    setSaving(true)
    try {
      const banners = await adminApi.getBanners()
      const nextPriority = Math.max(0, ...banners.map((b) => b.priority)) + 1
      await adminApi.createBanner({
        title: bannerTitle.trim(),
        subtitle: bannerSubtitle.trim() || null,
        link: bannerLink.trim() || null,
        buttonText: bannerButtonText.trim() || null,
        image: bannerImage,
        position: 'hero',
        priority: nextPriority,
        status: 'active',
      })
      toast.success('Hero banner created')
      setBannerDialogOpen(false)
      load()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to create banner')
    } finally {
      setSaving(false)
    }
  }

  const toggleBanner = async (banner: Banner) => {
    try {
      await adminApi.updateBanner(banner.id, { status: banner.status === 'active' ? 'inactive' : 'active' })
      setBanners((prev) => prev.map((b) => (b.id === banner.id ? { ...b, status: banner.status === 'active' ? 'inactive' : 'active' } : b)))
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to update banner')
    }
  }

  const handleDeleteBanner = async () => {
    if (!deleteBannerTarget) return
    try {
      await adminApi.deleteBanner(deleteBannerTarget.id)
      toast.success('Banner deleted')
      setBanners((prev) => prev.filter((b) => b.id !== deleteBannerTarget.id))
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to delete banner')
    } finally {
      setDeleteBannerTarget(null)
    }
  }

  const openCampaignDialog = () => {
    setCode('')
    setDiscountType('percentage')
    setDiscountValue('')
    setMinOrderAmount('')
    setExpiryDate('')
    setCampaignDialogOpen(true)
  }

  const handleCreateCampaign = async () => {
    if (!code.trim() || !discountValue || Number(discountValue) <= 0) {
      toast.error('A code and a valid discount value are required')
      return
    }
    setSaving(true)
    try {
      await adminApi.createCoupon({
        code: code.trim().toUpperCase(),
        discountType,
        discountValue: Number(discountValue),
        minOrderAmount: minOrderAmount ? Number(minOrderAmount) : undefined,
        expiryDate: expiryDate ? new Date(expiryDate).toISOString() : undefined,
        status: 'active',
      })
      toast.success('Promo campaign launched')
      setCampaignDialogOpen(false)
      load()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to create campaign')
    } finally {
      setSaving(false)
    }
  }

  return (
    <AdminLayout>
      <SEO title="Marketing" description="Manage marketing campaigns and promotions." url="/admin/marketing" />
      <header className="mb-6">
        <h1 className="text-2xl font-bold text-foreground">Marketing</h1>
        <p className="mt-1 text-sm text-muted-foreground">Homepage promotions and discount campaigns</p>
      </header>

      {loading ? (
        <div className="space-y-6">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
            {Array.from({ length: 3 }).map((_, i) => (
              <Skeleton key={i} className="h-28 w-full" />
            ))}
          </div>
          <Skeleton className="h-72 w-full" />
        </div>
      ) : (
        <>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <Card>
              <CardContent className="flex items-center gap-4 p-5">
                <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
                  <Megaphone className="h-6 w-6" />
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">Hero Banners</p>
                  <p className="text-2xl font-bold">{heroBanners.length}</p>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="flex items-center gap-4 p-5">
                <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-success/10 text-success">
                  <ImagePlus className="h-6 w-6" />
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">Live Banners</p>
                  <p className="text-2xl font-bold">{activeBanners}</p>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="flex items-center gap-4 p-5">
                <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-accent/10 text-accent">
                  <Calendar className="h-6 w-6" />
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">Active Campaigns</p>
                  <p className="text-2xl font-bold">{activeCoupons}</p>
                </div>
              </CardContent>
            </Card>
          </div>

          <div className="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
            <Card>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <CardTitle className="text-base">Hero Banners</CardTitle>
                  <Button size="sm" onClick={openBannerDialog}>
                    <Plus className="mr-1 h-4 w-4" /> Add Banner
                  </Button>
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                {heroBanners.length === 0 && (
                  <p className="py-6 text-center text-sm text-muted-foreground">No hero banners yet. Add one to promote a campaign.</p>
                )}
                {heroBanners.map((banner) => (
                  <div key={banner.id} className="flex items-center gap-4 rounded-lg border p-3">
                    {banner.image ? (
                      <img src={banner.image} alt={banner.title || 'Banner'} className="h-14 w-24 rounded object-cover" />
                    ) : (
                      <div className="flex h-14 w-24 items-center justify-center rounded bg-muted text-xs text-muted-foreground">No image</div>
                    )}
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-sm font-medium">{banner.title || 'Untitled banner'}</p>
                      <p className="truncate text-xs text-muted-foreground">{banner.link || 'No link set'}</p>
                      <Badge variant={banner.status === 'active' ? 'success' : 'secondary'} className="mt-1">
                        {banner.status}
                      </Badge>
                    </div>
                    <div className="flex items-center gap-2">
                      <Switch
                        checked={banner.status === 'active'}
                        onCheckedChange={() => toggleBanner(banner)}
                      />
                      <Button variant="ghost" size="icon" onClick={() => setDeleteBannerTarget(banner)}>
                        <Trash2 className="h-4 w-4 text-destructive" />
                      </Button>
                    </div>
                  </div>
                ))}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <CardTitle className="text-base">Promo Campaigns</CardTitle>
                  <Button size="sm" onClick={openCampaignDialog}>
                    <Plus className="mr-1 h-4 w-4" /> New Campaign
                  </Button>
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                {coupons.length === 0 && (
                  <p className="py-6 text-center text-sm text-muted-foreground">No promo campaigns yet. Launch a discount code campaign.</p>
                )}
                {coupons.slice(0, 8).map((coupon) => (
                  <div key={coupon.id} className="flex items-center justify-between rounded-lg border p-3">
                    <div className="min-w-0 flex-1">
                      <p className="font-mono text-sm font-semibold">{coupon.code}</p>
                      <p className="text-xs text-muted-foreground">
                        {coupon.discountType === 'percentage' ? `${coupon.discountValue}% off` : `Tk ${Number(coupon.discountValue).toLocaleString()} off`}
                        {coupon.minOrderAmount ? ` · min order ${Number(coupon.minOrderAmount).toLocaleString()}` : ''}
                        {coupon.expiryDate ? ` · expires ${new Date(coupon.expiryDate).toLocaleDateString()}` : ''}
                      </p>
                    </div>
                    <Badge variant={coupon.status === 'active' ? 'success' : 'secondary'}>{coupon.status}</Badge>
                  </div>
                ))}
              </CardContent>
            </Card>
          </div>
        </>
      )}

      <Dialog open={bannerDialogOpen} onOpenChange={setBannerDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Add Hero Banner</DialogTitle>
          </DialogHeader>
          <div className="space-y-4">
            <div>
              <Label htmlFor="banner-title">Title</Label>
              <Input id="banner-title" value={bannerTitle} onChange={(e) => setBannerTitle(e.target.value)} placeholder="e.g. Flash Sale - Up to 40% Off" />
            </div>
            <div>
              <Label htmlFor="banner-subtitle">Subtitle</Label>
              <Input id="banner-subtitle" value={bannerSubtitle} onChange={(e) => setBannerSubtitle(e.target.value)} placeholder="e.g. Limited time offer on home appliances" />
            </div>
            <div>
              <Label htmlFor="banner-link">Link (destination)</Label>
              <Input id="banner-link" value={bannerLink} onChange={(e) => setBannerLink(e.target.value)} placeholder="e.g. /shop?category=refrigerators" />
            </div>
            <div>
              <Label htmlFor="banner-cta">Button Text</Label>
              <Input id="banner-cta" value={bannerButtonText} onChange={(e) => setBannerButtonText(e.target.value)} placeholder="Shop Now" />
            </div>
            <div>
              <Label>Image</Label>
              {bannerImage ? (
                <div className="mt-1 flex items-center gap-3 rounded-lg border p-2">
                  <img src={bannerImage} alt="Selected" className="h-14 w-24 rounded object-cover" />
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-xs text-muted-foreground">{bannerImage}</p>
                    <Button variant="ghost" size="sm" className="mt-1 h-7 px-2 text-xs" onClick={() => setBannerImage('')}>
                      Remove
                    </Button>
                  </div>
                </div>
              ) : (
                <Button variant="outline" className="mt-1 w-full" onClick={() => setPickerOpen(true)}>
                  <ImagePlus className="mr-2 h-4 w-4" /> Choose from media library
                </Button>
              )}
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setBannerDialogOpen(false)}>Cancel</Button>
            <Button onClick={handleSaveBanner} disabled={saving}>
              {saving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              Create Banner
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={campaignDialogOpen} onOpenChange={setCampaignDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Launch Promo Campaign</DialogTitle>
          </DialogHeader>
          <div className="space-y-4">
            <div>
              <Label htmlFor="campaign-code">Discount Code</Label>
              <Input id="campaign-code" value={code} onChange={(e) => setCode(e.target.value)} placeholder="e.g. SUMMER40" />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label>Discount Type</Label>
                <Select value={discountType} onValueChange={(v) => setDiscountType(v as 'percentage' | 'fixed')}>
                  <SelectTrigger className="mt-1">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="percentage">Percentage</SelectItem>
                    <SelectItem value="fixed">Fixed amount</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label htmlFor="campaign-value">Discount Value</Label>
                <Input
                  id="campaign-value"
                  type="number"
                  value={discountValue}
                  onChange={(e) => setDiscountValue(e.target.value)}
                  placeholder={discountType === 'percentage' ? 'e.g. 20' : 'e.g. 500'}
                />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label htmlFor="campaign-min">Minimum Order</Label>
                <Input id="campaign-min" type="number" value={minOrderAmount} onChange={(e) => setMinOrderAmount(e.target.value)} placeholder="Optional" />
              </div>
              <div>
                <Label htmlFor="campaign-expiry">Expires On</Label>
                <Input id="campaign-expiry" type="date" value={expiryDate} onChange={(e) => setExpiryDate(e.target.value)} />
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCampaignDialogOpen(false)}>Cancel</Button>
            <Button onClick={handleCreateCampaign} disabled={saving}>
              {saving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              Launch Campaign
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <MediaPicker
        open={pickerOpen}
        onOpenChange={setPickerOpen}
        onSelect={(assets) => {
          if (assets[0]) setBannerImage(assets[0].url)
          setPickerOpen(false)
        }}
      />

      <AlertDialog open={deleteBannerTarget !== null} onOpenChange={(open) => !open && setDeleteBannerTarget(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Hero Banner</AlertDialogTitle>
            <AlertDialogDescription>
              Delete "{deleteBannerTarget?.title || 'Untitled banner'}"? This cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction className="bg-destructive text-white" onClick={handleDeleteBanner}>
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </AdminLayout>
  )
}

export default AdminMarketingPage
