import { useCallback, useEffect, useMemo, useState } from 'react'
import { Loader2, UserPlus } from 'lucide-react'
import { toast } from 'sonner'

import AdminLayout from '../../components/layout/AdminLayout'
import { Button } from '@/components/ui/button'
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
import { useAppDispatch } from '@/store/hooks'
import { commerceApi } from '@/store/services/commerceApi'
import type { AdminCustomer } from '@/types/admin'
import { SEO } from '../../components/common/SEO'

const AdminSettingsPage = () => {
  const dispatch = useAppDispatch()

  const [loading, setLoading] = useState(true)
  const [admins, setAdmins] = useState<AdminCustomer[]>([])
  const [removeAdminTarget, setRemoveAdminTarget] =
    useState<AdminCustomer | null>(null)

  const [storeInfo, setStoreInfo] = useState({
    storeName: 'Mama Bazar',
    email: '',
    primaryPhone: '',
    alternativePhone: '',
    contactAddress: '',
    city: '',
    country: '',
  })

  const [taxRate, setTaxRate] = useState('0')
  const [applyTaxToShipping, setApplyTaxToShipping] = useState(true)

  const [pixelId, setPixelId] = useState('')
  const [pixelEnabled, setPixelEnabled] = useState(false)
  const [pixelSaving, setPixelSaving] = useState(false)

  const [adminDialogOpen, setAdminDialogOpen] = useState(false)
  const [adminSaving, setAdminSaving] = useState(false)
  const [adminErrors, setAdminErrors] = useState<Record<string, string>>({})

  const [adminForm, setAdminForm] = useState({
    name: '',
    email: '',
    phone: '',
    password: '',
    confirmPassword: '',
    role: 'admin' as 'admin' | 'manager',
  })

  const settingsMap = useMemo(() => new Map<string, string>(), [])

  const loadInitialData = useCallback(async () => {
    setLoading(true)

    try {
      const [settings, customers] = await Promise.all([
        adminApi.getSettings(),
        adminApi.getCustomers(),
      ])

      settings.forEach((item) => {
        settingsMap.set(item.key, item.value)
      })

      setAdmins(
        customers.data.filter(
          (user) => user.role === 'admin' || user.role === 'manager',
        ),
      )

      const integrations = await adminApi.getTrackingIntegrations()

      const pixel = integrations.find(
        (item) => item.type === 'facebook_pixel',
      )

      setPixelId(pixel?.pixelId || '')
      setPixelEnabled(pixel?.status === 'active')

      const savedStoreInfo = settingsMap.get('store_info')

      if (savedStoreInfo) {
        try {
          const parsed = JSON.parse(savedStoreInfo)

          setStoreInfo({
            storeName: parsed.storeName || 'Mama Bazar',
            email: parsed.email || '',
            primaryPhone: parsed.primaryPhone || '',
            alternativePhone: parsed.alternativePhone || '',
            contactAddress: parsed.contactAddress || '',
            city: parsed.city || '',
            country: parsed.country || '',
          })
        } catch {
          // Ignore malformed value
        }
      }

      const savedTax = settingsMap.get('tax_settings')

      if (savedTax) {
        try {
          const parsed = JSON.parse(savedTax)

          setTaxRate(String(parsed.taxRate || 0))
          setApplyTaxToShipping(Boolean(parsed.applyTaxToShipping))
        } catch {
          // Ignore malformed value
        }
      }
    } catch (err) {
      toast.error(
        err instanceof Error ? err.message : 'Failed to load settings',
      )
    } finally {
      setLoading(false)
    }
  }, [settingsMap])

  useEffect(() => {
    loadInitialData()
  }, [loadInitialData])

  const saveStoreInfo = async () => {
    try {
      await adminApi.setSetting('store_info', JSON.stringify(storeInfo))

      dispatch(
        commerceApi.util.invalidateTags([
          {
            type: 'StoreInfo',
            id: 'DETAIL',
          },
        ]),
      )

      toast.success('Store information updated successfully.')
    } catch (err) {
      toast.error(
        err instanceof Error ? err.message : 'Failed to save store info',
      )
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
      toast.error(
        err instanceof Error ? err.message : 'Failed to save tax settings',
      )
    }
  }

  const savePixelSettings = async () => {
    const id = pixelId.trim()

    if (id && !/^\d{10,20}$/.test(id)) {
      toast.error('Invalid Pixel ID. It should contain 10-20 digits.')
      return
    }

    setPixelSaving(true)

    try {
      const integrations = await adminApi.getTrackingIntegrations()

      const existing = integrations.find(
        (item) => item.type === 'facebook_pixel',
      )

      const status: 'active' | 'inactive' = pixelEnabled
        ? 'active'
        : 'inactive'

      if (existing) {
        await adminApi.updateTrackingIntegration(existing.id, {
          name: 'Facebook Pixel',
          type: 'facebook_pixel',
          pixelId: id || undefined,
          status,
        })
      } else if (id) {
        await adminApi.createTrackingIntegration({
          name: 'Facebook Pixel',
          type: 'facebook_pixel',
          pixelId: id,
          status,
        })
      }

      toast.success('Meta Pixel settings saved')
    } catch (err) {
      toast.error(
        err instanceof Error
          ? err.message
          : 'Failed to save Meta Pixel settings',
      )
    } finally {
      setPixelSaving(false)
    }
  }

  const updateAdminForm = (
    key: keyof typeof adminForm,
    value: string,
  ) => {
    setAdminForm(
      (prev) =>
        ({
          ...prev,
          [key]: value,
        }) as typeof adminForm,
    )

    setAdminErrors((prev) => ({
      ...prev,
      [key]: '',
    }))
  }

  const validateAdminForm = () => {
    const errors: Record<string, string> = {}

    if (!adminForm.name.trim()) {
      errors.name = 'Name is required.'
    }

    if (!adminForm.email.trim()) {
      errors.email = 'Email is required.'
    } else if (
      !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(
        adminForm.email.trim(),
      )
    ) {
      errors.email = 'Invalid email address.'
    }

    if (!adminForm.phone.trim()) {
      errors.phone = 'Phone number is required.'
    } else if (
      !/^(\+880|0)[1-9]\d{9}$/.test(adminForm.phone.trim())
    ) {
      errors.phone =
        'Enter a valid Bangladeshi phone number (01XXXXXXXXX).'
    }

    if (!adminForm.password) {
      errors.password = 'Password is required.'
    } else if (adminForm.password.length < 6) {
      errors.password =
        'Password must be at least 6 characters.'
    }

    if (adminForm.confirmPassword !== adminForm.password) {
      errors.confirmPassword = 'Passwords do not match.'
    }

    return errors
  }

  const handleCreateAdmin = async () => {
    const errors = validateAdminForm()

    setAdminErrors(errors)

    if (Object.keys(errors).length > 0) {
      return
    }

    setAdminSaving(true)

    try {
      await adminApi.createAdmin({
        name: adminForm.name.trim(),
        email: adminForm.email.trim(),
        phone: adminForm.phone.trim(),
        password: adminForm.password,
        role: adminForm.role,
      })

      toast.success('Admin created successfully.')

      setAdminDialogOpen(false)

      setAdminForm({
        name: '',
        email: '',
        phone: '',
        password: '',
        confirmPassword: '',
        role: 'admin',
      })

      setAdminErrors({})

      const customers = await adminApi.getCustomers()

      setAdmins(
        customers.data.filter(
          (user) => user.role === 'admin' || user.role === 'manager',
        ),
      )
    } catch (err) {
      toast.error(
        err instanceof Error
          ? err.message
          : 'Failed to create admin.',
      )
    } finally {
      setAdminSaving(false)
    }
  }

  const removeAdminUser = async () => {
    if (!removeAdminTarget) return

    try {
      await adminApi.deleteCustomer(removeAdminTarget.id)

      toast.success('Admin user removed')

      setAdmins((prev) =>
        prev.filter((u) => u.id !== removeAdminTarget.id),
      )
    } catch (err) {
      toast.error(
        err instanceof Error
          ? err.message
          : 'Failed to remove admin',
      )
    } finally {
      setRemoveAdminTarget(null)
    }
  }

  return (
    <AdminLayout>
      <SEO
        title="Settings"
        description="Configure store settings and preferences."
        url="/admin/settings"
      />

      <div className="min-h-full bg-background">
        {/* Page Header */}
        <header className="mb-8">
          <div>
            <h1 className="text-2xl font-bold tracking-tight text-foreground">
              Settings
            </h1>

            <p className="mt-1 text-sm text-muted-foreground">
              Configure your store, payments, and shipping
            </p>
          </div>
        </header>

        {loading ? (
          <div className="flex min-h-[420px] items-center justify-center rounded-xl border bg-card">
            <div className="flex flex-col items-center gap-3 text-muted-foreground">
              <Loader2 className="h-7 w-7 animate-spin" />

              <p className="text-sm">
                Loading settings...
              </p>
            </div>
          </div>
        ) : (
          <div className="grid grid-cols-1 gap-6 xl:grid-cols-2">
            {/* Store Information */}
            <section className="rounded-xl border bg-card shadow-sm">
              <div className="border-b px-6 py-5">
                <h2 className="text-lg font-semibold text-foreground">
                  Store Contact & Business Information
                </h2>

                <p className="mt-1 text-sm leading-6 text-muted-foreground">
                  Used across the website, including footer,
                  contact page, invoice, and support areas.
                </p>
              </div>

              <div className="space-y-5 p-6">
                <div className="space-y-2">
                  <Label htmlFor="store-name">
                    Store Name
                  </Label>

                  <Input
                    id="store-name"
                    value={storeInfo.storeName}
                    onChange={(e) =>
                      setStoreInfo((prev) => ({
                        ...prev,
                        storeName: e.target.value,
                      }))
                    }
                    placeholder="Mama Bazar"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="store-email">
                    Store Email
                  </Label>

                  <Input
                    id="store-email"
                    type="email"
                    value={storeInfo.email}
                    onChange={(e) =>
                      setStoreInfo((prev) => ({
                        ...prev,
                        email: e.target.value,
                      }))
                    }
                    placeholder="hello@example.com"
                  />
                </div>

                <div className="grid gap-5 sm:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="store-primary-phone">
                      Primary Phone
                    </Label>

                    <Input
                      id="store-primary-phone"
                      type="tel"
                      value={storeInfo.primaryPhone}
                      onChange={(e) =>
                        setStoreInfo((prev) => ({
                          ...prev,
                          primaryPhone: e.target.value,
                        }))
                      }
                      placeholder="01XXXXXXXXX"
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="store-alternative-phone">
                      Alternative Phone
                    </Label>

                    <Input
                      id="store-alternative-phone"
                      type="tel"
                      value={storeInfo.alternativePhone}
                      onChange={(e) =>
                        setStoreInfo((prev) => ({
                          ...prev,
                          alternativePhone: e.target.value,
                        }))
                      }
                      placeholder="01XXXXXXXXX"
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="store-address">
                    Contact Address
                  </Label>

                  <Input
                    id="store-address"
                    value={storeInfo.contactAddress}
                    onChange={(e) =>
                      setStoreInfo((prev) => ({
                        ...prev,
                        contactAddress: e.target.value,
                      }))
                    }
                    placeholder="House/Road/Area, City, Bangladesh"
                  />
                </div>

                <div className="grid gap-5 sm:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="store-city">
                      City
                    </Label>

                    <Input
                      id="store-city"
                      value={storeInfo.city}
                      onChange={(e) =>
                        setStoreInfo((prev) => ({
                          ...prev,
                          city: e.target.value,
                        }))
                      }
                      placeholder="Dhaka"
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="store-country">
                      Country
                    </Label>

                    <Input
                      id="store-country"
                      value={storeInfo.country}
                      onChange={(e) =>
                        setStoreInfo((prev) => ({
                          ...prev,
                          country: e.target.value,
                        }))
                      }
                      placeholder="Bangladesh"
                    />
                  </div>
                </div>

                <Button
                  className="w-full"
                  onClick={saveStoreInfo}
                >
                  Save Store Information
                </Button>
              </div>
            </section>

            {/* Tax Settings */}
            <section className="rounded-xl border bg-card shadow-sm">
              <div className="border-b px-6 py-5">
                <h2 className="text-lg font-semibold text-foreground">
                  Tax Settings
                </h2>

                <p className="mt-1 text-sm text-muted-foreground">
                  Configure tax calculation for your store.
                </p>
              </div>

              <div className="space-y-5 p-6">
                <div className="space-y-2">
                  <Label htmlFor="tax-rate">
                    Tax Rate (%)
                  </Label>

                  <Input
                    id="tax-rate"
                    type="number"
                    min="0"
                    step="0.01"
                    value={taxRate}
                    onChange={(e) =>
                      setTaxRate(e.target.value)
                    }
                    placeholder="0"
                  />
                </div>

                <div className="flex items-center justify-between rounded-lg border bg-muted/30 p-4">
                  <div>
                    <p className="text-sm font-medium">
                      Apply tax to shipping
                    </p>

                    <p className="mt-1 text-xs text-muted-foreground">
                      Include shipping charges in tax calculation.
                    </p>
                  </div>

                  <Switch
                    checked={applyTaxToShipping}
                    onCheckedChange={setApplyTaxToShipping}
                  />
                </div>

                <Button
                  className="w-full"
                  onClick={saveTaxSettings}
                >
                  Save Tax Settings
                </Button>
              </div>
            </section>

            {/* Meta Pixel */}
            <section className="rounded-xl border bg-card shadow-sm">
              <div className="border-b px-6 py-5">
                <h2 className="text-lg font-semibold text-foreground">
                  Meta / Facebook Pixel
                </h2>

                <p className="mt-1 text-sm leading-6 text-muted-foreground">
                  Track visitor activity and ecommerce events
                  on the storefront.
                </p>
              </div>

              <div className="space-y-5 p-6">
                <div className="space-y-2">
                  <Label htmlFor="pixel-id">
                    Pixel ID
                  </Label>

                  <Input
                    id="pixel-id"
                    value={pixelId}
                    onChange={(e) =>
                      setPixelId(e.target.value)
                    }
                    placeholder="e.g. 123456789012345"
                  />

                  <p className="text-xs text-muted-foreground">
                    Your Meta Pixel ID should contain 10–20 digits.
                  </p>
                </div>

                <div className="flex items-center justify-between rounded-lg border bg-muted/30 p-4">
                  <div>
                    <p className="text-sm font-medium">
                      Enable Pixel
                    </p>

                    <p className="mt-1 text-xs text-muted-foreground">
                      Enable tracking on your storefront.
                    </p>
                  </div>

                  <Switch
                    checked={pixelEnabled}
                    onCheckedChange={setPixelEnabled}
                  />
                </div>

                <Button
                  className="w-full"
                  onClick={savePixelSettings}
                  disabled={pixelSaving}
                >
                  {pixelSaving && (
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  )}

                  Save Meta Pixel
                </Button>
              </div>
            </section>

            {/* Admin Users */}
            <section className="rounded-xl border bg-card shadow-sm xl:col-span-2">
              <div className="flex flex-col gap-4 border-b px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <h2 className="text-lg font-semibold text-foreground">
                    Admin Users
                  </h2>

                  <p className="mt-1 text-sm text-muted-foreground">
                    Manage administrators and managers who have
                    access to the dashboard.
                  </p>
                </div>

                <Button
                  onClick={() => setAdminDialogOpen(true)}
                  className="w-full sm:w-auto"
                >
                  <UserPlus className="mr-2 h-4 w-4" />
                  Add Admin
                </Button>
              </div>

              <div className="p-6">
                {admins.length === 0 ? (
                  <div className="rounded-lg border border-dashed p-8 text-center">
                    <p className="text-sm font-medium">
                      No admin users found
                    </p>

                    <p className="mt-1 text-xs text-muted-foreground">
                      Add an admin or manager to give someone
                      dashboard access.
                    </p>
                  </div>
                ) : (
                  <div className="overflow-hidden rounded-lg border">
                    <div className="hidden grid-cols-[1fr_180px_100px] gap-4 bg-muted/40 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground md:grid">
                      <span>User</span>
                      <span>Contact</span>
                      <span>Role</span>
                    </div>

                    <div className="divide-y">
                      {admins.map((user) => (
                        <div
                          key={user.id}
                          className="flex flex-col gap-4 px-4 py-4 md:grid md:grid-cols-[1fr_180px_100px] md:items-center md:gap-4"
                        >
                          <div className="min-w-0">
                            <p className="truncate font-medium text-foreground">
                              {user.name}
                            </p>

                            <p className="mt-1 truncate text-xs text-muted-foreground md:hidden">
                              {user.email || user.phone}
                            </p>
                          </div>

                          <div className="hidden min-w-0 md:block">
                            <p className="truncate text-sm text-foreground">
                              {user.phone}
                            </p>

                            {user.email && (
                              <p className="mt-1 truncate text-xs text-muted-foreground">
                                {user.email}
                              </p>
                            )}
                          </div>

                          <div className="flex items-center justify-between gap-3 md:justify-start">
                            <span className="inline-flex rounded-full bg-muted px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                              {user.role}
                            </span>

                            <Button
                              variant="ghost"
                              size="sm"
                              className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                              onClick={() =>
                                setRemoveAdminTarget(user)
                              }
                            >
                              Remove
                            </Button>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            </section>
          </div>
        )}
      </div>

      {/* Add Admin Dialog */}
      <Dialog
        open={adminDialogOpen}
        onOpenChange={(open) => {
          setAdminDialogOpen(open)

          if (!open) {
            setAdminErrors({})
          }
        }}
      >
        <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Add Admin</DialogTitle>
          </DialogHeader>

          <div className="space-y-5">
            <div className="space-y-2">
              <Label htmlFor="admin-name">
                Full Name
              </Label>

              <Input
                id="admin-name"
                value={adminForm.name}
                onChange={(e) =>
                  updateAdminForm('name', e.target.value)
                }
                placeholder="e.g. Rahim Uddin"
              />

              {adminErrors.name && (
                <p className="text-xs text-destructive">
                  {adminErrors.name}
                </p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="admin-email">
                Email
              </Label>

              <Input
                id="admin-email"
                type="email"
                value={adminForm.email}
                onChange={(e) =>
                  updateAdminForm('email', e.target.value)
                }
                placeholder="e.g. admin@mamabazar.com"
              />

              {adminErrors.email && (
                <p className="text-xs text-destructive">
                  {adminErrors.email}
                </p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="admin-phone">
                Phone Number
              </Label>

              <Input
                id="admin-phone"
                type="tel"
                value={adminForm.phone}
                onChange={(e) =>
                  updateAdminForm('phone', e.target.value)
                }
                placeholder="e.g. 01712345678"
              />

              {adminErrors.phone && (
                <p className="text-xs text-destructive">
                  {adminErrors.phone}
                </p>
              )}
            </div>

            <div className="grid gap-5 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="admin-password">
                  Password
                </Label>

                <Input
                  id="admin-password"
                  type="password"
                  value={adminForm.password}
                  onChange={(e) =>
                    updateAdminForm(
                      'password',
                      e.target.value,
                    )
                  }
                  placeholder="Min. 6 characters"
                />

                {adminErrors.password && (
                  <p className="text-xs text-destructive">
                    {adminErrors.password}
                  </p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="admin-confirm-password">
                  Confirm Password
                </Label>

                <Input
                  id="admin-confirm-password"
                  type="password"
                  value={adminForm.confirmPassword}
                  onChange={(e) =>
                    updateAdminForm(
                      'confirmPassword',
                      e.target.value,
                    )
                  }
                  placeholder="Repeat password"
                />

                {adminErrors.confirmPassword && (
                  <p className="text-xs text-destructive">
                    {adminErrors.confirmPassword}
                  </p>
                )}
              </div>
            </div>

            <div className="space-y-2">
              <Label>Role</Label>

              <Select
                value={adminForm.role}
                onValueChange={(value) =>
                  updateAdminForm('role', value)
                }
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select role" />
                </SelectTrigger>

                <SelectContent>
                  <SelectItem value="admin">
                    Admin
                  </SelectItem>

                  <SelectItem value="manager">
                    Manager
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <DialogFooter className="mt-2 flex-col-reverse gap-2 sm:flex-row">
            <Button
              variant="outline"
              onClick={() => setAdminDialogOpen(false)}
              className="w-full sm:w-auto"
            >
              Cancel
            </Button>

            <Button
              onClick={handleCreateAdmin}
              disabled={adminSaving}
              className="w-full sm:w-auto"
            >
              {adminSaving && (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              )}

              Add Admin
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Remove Admin Confirmation */}
      <AlertDialog
        open={removeAdminTarget !== null}
        onOpenChange={(open) =>
          !open && setRemoveAdminTarget(null)
        }
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>
              Remove Admin User
            </AlertDialogTitle>

            <AlertDialogDescription>
              Remove{' '}
              <strong>{removeAdminTarget?.name}</strong>{' '}
              from admin access? This will also remove their
              account.
            </AlertDialogDescription>
          </AlertDialogHeader>

          <AlertDialogFooter>
            <AlertDialogCancel>
              Cancel
            </AlertDialogCancel>

            <AlertDialogAction
              className="bg-destructive text-white hover:bg-destructive/90"
              onClick={removeAdminUser}
            >
              Remove
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </AdminLayout>
  )
}

export default AdminSettingsPage