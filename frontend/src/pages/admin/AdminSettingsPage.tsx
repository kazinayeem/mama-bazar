import { useCallback, useEffect, useMemo, useState } from 'react'
import { ImagePlus, Loader2, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import AdminLayout from '../../components/layout/AdminLayout'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Switch } from '@/components/ui/switch'
import { Skeleton } from '@/components/ui/skeleton'
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
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { adminApi } from '@/lib/adminApi'
import MediaPicker from '@/components/admin/MediaPicker'
import type { AdminCustomer, Banner } from '@/types/admin'
import { SEO } from '../../components/common/SEO'

type ShippingZone = {
  zone: string
  rate: string
  deliveryDays: string
}

const defaultPaymentMethods = [
  { name: 'bKash', enabled: true, icon: '💳' },
  { name: 'Nagad', enabled: true, icon: '📱' },
  { name: 'Cash on Delivery', enabled: true, icon: '🏪' },
  { name: 'Credit Card', enabled: false, icon: '💰' },
]

const defaultShipping: ShippingZone[] = [
  { zone: 'Dhaka', rate: '50', deliveryDays: '1-2' },
  { zone: 'Outside Dhaka', rate: '100', deliveryDays: '3-5' },
  { zone: 'Hill Tracts', rate: '150', deliveryDays: '5-7' },
]

const AdminSettingsPage = () => {
  const [loading, setLoading] = useState(true)
  const [admins, setAdmins] = useState<AdminCustomer[]>([])
  const [removeAdminTarget, setRemoveAdminTarget] = useState<AdminCustomer | null>(null)
  const [heroBanners, setHeroBanners] = useState<Banner[]>([])
  const [heroSaving, setHeroSaving] = useState(false)
  const [bannerDialogOpen, setBannerDialogOpen] = useState(false)
  const [pickerOpen, setPickerOpen] = useState(false)
  const [bannerTitle, setBannerTitle] = useState('')
  const [bannerLink, setBannerLink] = useState('')
  const [bannerImage, setBannerImage] = useState('')

  const [paymentMethods, setPaymentMethods] = useState(defaultPaymentMethods)
  const [shippingZones, setShippingZones] = useState(defaultShipping)
  const [storeInfo, setStoreInfo] = useState({
    storeName: 'Mama Bazar',
    email: '',
    phone: '',
  })
  const [taxRate, setTaxRate] = useState('0')
  const [applyTaxToShipping, setApplyTaxToShipping] = useState(true)

  const settingsMap = useMemo(() => new Map<string, string>(), [])

  const loadHeroBanners = useCallback(async () => {
    const banners = await adminApi.getBanners()
    setHeroBanners(banners.filter((b) => b.position === 'hero'))
  }, [])

  const loadInitialData = useCallback(async () => {
    setLoading(true)
    try {
      const [settings, customers] = await Promise.all([adminApi.getSettings(), adminApi.getCustomers()])
      settings.forEach((item) => settingsMap.set(item.key, item.value))
      await loadHeroBanners()
      setAdmins(customers.data.filter((user) => user.role === 'admin' || user.role === 'manager'))

      const savedStoreInfo = settingsMap.get('store_info')
      if (savedStoreInfo) {
        try {
          const parsed = JSON.parse(savedStoreInfo)
          setStoreInfo({
            storeName: parsed.storeName || 'Mama Bazar',
            email: parsed.email || '',
            phone: parsed.phone || '',
          })
        } catch {
          /* ignore malformed value */
        }
      }

      const savedTax = settingsMap.get('tax_settings')
      if (savedTax) {
        try {
          const parsed = JSON.parse(savedTax)
          setTaxRate(String(parsed.taxRate || 0))
          setApplyTaxToShipping(Boolean(parsed.applyTaxToShipping))
        } catch {
          /* ignore malformed value */
        }
      }

      const savedPayments = settingsMap.get('payment_methods')
      if (savedPayments) {
        try {
          const parsed = JSON.parse(savedPayments)
          if (Array.isArray(parsed)) setPaymentMethods(parsed)
        } catch {
          /* ignore malformed value */
        }
      }

      const savedShipping = settingsMap.get('shipping_zones')
      if (savedShipping) {
        try {
          const parsed = JSON.parse(savedShipping)
          if (Array.isArray(parsed)) setShippingZones(parsed)
        } catch {
          /* ignore malformed value */
        }
      }
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to load settings')
    } finally {
      setLoading(false)
    }
  }, [loadHeroBanners, settingsMap])

  useEffect(() => {
    loadInitialData()
  }, [loadInitialData])

  const saveStoreInfo = async () => {
    try {
      await adminApi.setSetting('store_info', JSON.stringify(storeInfo))
      toast.success('Store information saved')
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to save store info')
    }
  }

  const saveTaxSettings = async () => {
    try {
      await adminApi.setSetting(
        'tax_settings',
        JSON.stringify({
          taxRate: Number(taxRate || 0),
          applyTaxToShipping,
        }),
      )
      toast.success('Tax settings saved')
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to save tax settings')
    }
  }

  const savePaymentSettings = async () => {
    try {
      await adminApi.setSetting('payment_methods', JSON.stringify(paymentMethods))
      toast.success('Payment settings saved')
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to save payment settings')
    }
  }

  const saveShippingSettings = async () => {
    try {
      await adminApi.setSetting('shipping_zones', JSON.stringify(shippingZones))
      toast.success('Shipping settings saved')
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to save shipping settings')
    }
  }

  const handleCreateBanner = async () => {
    if (!bannerTitle.trim() || !bannerImage) {
      toast.error('A title and an image are required')
      return
    }
    setHeroSaving(true)
    try {
      const banners = await adminApi.getBanners()
      const nextPriority = Math.max(0, ...banners.map((b) => b.priority)) + 1
      await adminApi.createBanner({
        title: bannerTitle.trim(),
        link: bannerLink.trim() || null,
        image: bannerImage,
        position: 'hero',
        priority: nextPriority,
        status: 'active',
      })
      toast.success('Hero banner added')
      setBannerDialogOpen(false)
      setBannerTitle('')
      setBannerLink('')
      setBannerImage('')
      await loadHeroBanners()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to add banner')
    } finally {
      setHeroSaving(false)
    }
  }

  const toggleBanner = async (banner: Banner) => {
    try {
      await adminApi.updateBanner(banner.id, { status: banner.status === 'active' ? 'inactive' : 'active' })
      setHeroBanners((prev) => prev.map((b) => (b.id === banner.id ? { ...b, status: banner.status === 'active' ? 'inactive' : 'active' } : b)))
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to update banner')
    }
  }

  const deleteBanner = async (banner: Banner) => {
    try {
      await adminApi.deleteBanner(banner.id)
      toast.success('Banner removed')
      setHeroBanners((prev) => prev.filter((b) => b.id !== banner.id))
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to remove banner')
    }
  }

  const removeAdminUser = async () => {
    if (!removeAdminTarget) return
    try {
      await adminApi.deleteCustomer(removeAdminTarget.id)
      toast.success('Admin user removed')
      setAdmins((prev) => prev.filter((u) => u.id !== removeAdminTarget.id))
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to remove admin')
    } finally {
      setRemoveAdminTarget(null)
    }
  }

  return (
    <AdminLayout>
      <SEO title="Settings" description="Configure store settings and preferences." url="/admin/settings" />
      <header className="mb-8">
        <h1 className="text-2xl font-bold text-foreground">Settings</h1>
        <p className="mt-1 text-sm text-muted-foreground">Configure your store, payments, and shipping</p>
      </header>

      {loading ? (
        <div className="space-y-4">
          <Skeleton className="h-40 w-full" />
          <Skeleton className="h-72 w-full" />
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div className="rounded-xl border bg-card p-6 shadow-sm md:col-span-2">
            <div className="flex flex-wrap items-start justify-between gap-4">
              <div>
                <h2 className="text-lg font-bold">Hero Slides</h2>
                <p className="mt-1 text-sm text-muted-foreground">
                  Control the homepage carousel from admin. Add banners from the media library, and toggle or remove slides when needed.
                </p>
              </div>
              <div className="rounded-lg border border-warning/30 bg-warning/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-warning">
                {heroBanners.length} slides
              </div>
            </div>

            <div className="mt-6">
              <Button onClick={() => setBannerDialogOpen(true)} disabled={heroSaving}>
                {heroSaving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                <ImagePlus className="mr-2 h-4 w-4" /> Add Hero Slide
              </Button>
            </div>

            <div className="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
              {heroBanners.length === 0 && (
                <p className="text-sm text-muted-foreground md:col-span-2">No hero slides configured yet.</p>
              )}
              {heroBanners.map((banner) => (
                <article key={banner.id} className="overflow-hidden rounded-xl border bg-background shadow-sm">
                  <div className="aspect-[4/5] overflow-hidden bg-muted">
                    {banner.image ? (
                      <img alt={banner.title || `Hero slide ${banner.id}`} className="h-full w-full object-cover" src={banner.image} />
                    ) : (
                      <div className="flex h-full items-center justify-center text-xs text-muted-foreground">No image</div>
                    )}
                  </div>
                  <div className="flex items-center justify-between gap-3 p-3">
                    <div className="min-w-0">
                      <p className="truncate text-xs font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                        {banner.title || `Slide ${banner.id}`}
                      </p>
                      <Badge variant={banner.status === 'active' ? 'success' : 'secondary'} className="mt-1">
                        {banner.status}
                      </Badge>
                    </div>
                    <div className="flex items-center gap-2">
                      <Switch checked={banner.status === 'active'} onCheckedChange={() => toggleBanner(banner)} />
                      <Button variant="ghost" size="icon" onClick={() => deleteBanner(banner)}>
                        <Trash2 className="h-4 w-4 text-destructive" />
                      </Button>
                    </div>
                  </div>
                </article>
              ))}
            </div>
          </div>

          <div className="rounded-xl border bg-card p-6 shadow-sm">
            <h2 className="mb-4 text-lg font-bold">Payment Methods</h2>
            <div className="space-y-4">
              {paymentMethods.map((method, index) => (
                <div key={method.name} className="flex items-center justify-between border-b pb-3 last:border-0">
                  <div className="flex items-center gap-3">
                    <span className="text-2xl">{method.icon}</span>
                    <p className="font-medium">{method.name}</p>
                  </div>
                  <Switch
                    checked={method.enabled}
                    onCheckedChange={(checked) =>
                      setPaymentMethods((prev) => prev.map((item, idx) => (idx === index ? { ...item, enabled: checked } : item)))
                    }
                  />
                </div>
              ))}
            </div>
            <Button className="mt-4 w-full" onClick={savePaymentSettings}>
              Save Payment Methods
            </Button>
          </div>

          <div className="rounded-xl border bg-card p-6 shadow-sm">
            <h2 className="mb-4 text-lg font-bold">Shipping Zones</h2>
            <div className="space-y-3">
              {shippingZones.map((zone, index) => (
                <div key={zone.zone} className="rounded-lg border p-3">
                  <p className="mb-2 font-medium">{zone.zone}</p>
                  <div className="grid grid-cols-2 gap-2 text-sm">
                    <Input
                      value={zone.rate}
                      onChange={(e) =>
                        setShippingZones((prev) => prev.map((item, idx) => (idx === index ? { ...item, rate: e.target.value } : item)))
                      }
                      placeholder="Rate"
                    />
                    <Input
                      value={zone.deliveryDays}
                      onChange={(e) =>
                        setShippingZones((prev) =>
                          prev.map((item, idx) => (idx === index ? { ...item, deliveryDays: e.target.value } : item)),
                        )
                      }
                      placeholder="Delivery days"
                    />
                  </div>
                </div>
              ))}
            </div>
            <Button className="mt-4 w-full" onClick={saveShippingSettings}>
              Save Shipping Rules
            </Button>
          </div>

          <div className="rounded-xl border bg-card p-6 shadow-sm">
            <h2 className="mb-4 text-lg font-bold">Store Information</h2>
            <div className="space-y-4">
              <div>
                <Label htmlFor="store-name">Store Name</Label>
                <Input
                  id="store-name"
                  value={storeInfo.storeName}
                  onChange={(e) => setStoreInfo((prev) => ({ ...prev, storeName: e.target.value }))}
                  className="mt-1"
                />
              </div>
              <div>
                <Label htmlFor="store-email">Email</Label>
                <Input
                  id="store-email"
                  type="email"
                  value={storeInfo.email}
                  onChange={(e) => setStoreInfo((prev) => ({ ...prev, email: e.target.value }))}
                  className="mt-1"
                />
              </div>
              <div>
                <Label htmlFor="store-phone">Phone</Label>
                <Input
                  id="store-phone"
                  type="tel"
                  value={storeInfo.phone}
                  onChange={(e) => setStoreInfo((prev) => ({ ...prev, phone: e.target.value }))}
                  className="mt-1"
                />
              </div>
              <Button className="w-full" onClick={saveStoreInfo}>
                Save Store Info
              </Button>
            </div>
          </div>

          <div className="rounded-xl border bg-card p-6 shadow-sm">
            <h2 className="mb-4 text-lg font-bold">Tax Settings</h2>
            <div className="space-y-4">
              <div>
                <Label htmlFor="tax-rate">Tax Rate (%)</Label>
                <Input
                  id="tax-rate"
                  type="number"
                  value={taxRate}
                  onChange={(e) => setTaxRate(e.target.value)}
                  className="mt-1"
                />
              </div>
              <div className="flex items-center justify-between">
                <span className="text-sm font-medium">Apply tax to shipping</span>
                <Switch checked={applyTaxToShipping} onCheckedChange={setApplyTaxToShipping} />
              </div>
              <Button className="w-full" onClick={saveTaxSettings}>
                Save Tax Settings
              </Button>
            </div>
          </div>

          <div className="rounded-xl border bg-card p-6 shadow-sm md:col-span-2">
            <h2 className="mb-4 text-lg font-bold">Admin Users</h2>
            <div className="space-y-3">
              {admins.length === 0 && <p className="text-sm text-muted-foreground">No admin users found.</p>}
              {admins.map((user) => (
                <div key={user.id} className="flex items-center justify-between border-b pb-3 last:border-0">
                  <div>
                    <p className="font-medium">{user.name}</p>
                    <p className="text-xs text-muted-foreground">{user.phone}</p>
                  </div>
                  <div className="flex items-center gap-4">
                    <span className="text-xs font-medium uppercase text-muted-foreground">{user.role}</span>
                    <Button variant="ghost" size="sm" className="text-destructive hover:text-destructive" onClick={() => setRemoveAdminTarget(user)}>
                      Remove
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      <Dialog open={bannerDialogOpen} onOpenChange={setBannerDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Add Hero Slide</DialogTitle>
          </DialogHeader>
          <div className="space-y-4">
            <div>
              <Label htmlFor="slide-title">Title</Label>
              <Input id="slide-title" value={bannerTitle} onChange={(e) => setBannerTitle(e.target.value)} placeholder="e.g. New Season Arrivals" />
            </div>
            <div>
              <Label htmlFor="slide-link">Link</Label>
              <Input id="slide-link" value={bannerLink} onChange={(e) => setBannerLink(e.target.value)} placeholder="e.g. /shop" />
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
            <Button onClick={handleCreateBanner} disabled={heroSaving}>
              {heroSaving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              Add Slide
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

      <AlertDialog open={removeAdminTarget !== null} onOpenChange={(open) => !open && setRemoveAdminTarget(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Remove Admin User</AlertDialogTitle>
            <AlertDialogDescription>
              Remove {removeAdminTarget?.name} from admin access? This will also remove their account.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction className="bg-destructive text-white" onClick={removeAdminUser}>
              Remove
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </AdminLayout>
  )
}

export default AdminSettingsPage
