import { useCallback, useEffect, useState } from 'react'
import { CreditCard, Loader2, MoreHorizontal, Pencil, Plus, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import AdminLayout from '@/components/layout/AdminLayout'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import { Card, CardContent } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import MediaPicker from '@/components/admin/MediaPicker'
import type { AdminPaymentMethod } from '@/types/admin'
import { adminApi } from '@/lib/adminApi'

const TYPE_LABELS: Record<string, string> = {
  cod: 'Cash on Delivery',
  mobile_banking: 'Mobile Banking',
  bank: 'Bank Transfer',
  online: 'Online Gateway',
}

const PAYMENT_ICONS: Record<string, string> = {
  cod: '💵',
  bkash: '৳',
  nagad: '৳',
  rocket: '৳',
  bank: '🏦',
  stripe: '💳',
  sslcommerz: '🔒',
  paypal: '🅿️',
}

interface FormState {
  code: string
  name: string
  type: 'cod' | 'mobile_banking' | 'bank' | 'online'
  enabled: boolean
  maintenanceMode: boolean
  sortOrder: number
  config: Record<string, string | number>
}

const emptyForm = (): FormState => ({
  code: '',
  name: '',
  type: 'cod',
  enabled: true,
  maintenanceMode: false,
  sortOrder: 0,
  config: {},
})

const AdminPaymentMethodsPage = () => {
  const [methods, setMethods] = useState<AdminPaymentMethod[]>([])
  const [loading, setLoading] = useState(true)
  const [dialogOpen, setDialogOpen] = useState(false)
  const [editing, setEditing] = useState<AdminPaymentMethod | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<AdminPaymentMethod | null>(null)
  const [saving, setSaving] = useState(false)
  const [form, setForm] = useState<FormState>(emptyForm())
  const [pickerOpen, setPickerOpen] = useState(false)
  const [selected, setSelected] = useState<Set<number>>(new Set())
  const [bulkUpdating, setBulkUpdating] = useState(false)

  const load = useCallback(async () => {
    setLoading(true)
    try {
      setMethods(await adminApi.getPaymentMethodsAdmin())
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to load')
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load()
  }, [load])

  const toggleRow = (id: number) => {
    const next = new Set(selected)
    if (next.has(id)) next.delete(id)
    else next.add(id)
    setSelected(next)
  }

  const toggleAll = () => {
    if (selected.size === methods.length) setSelected(new Set())
    else setSelected(new Set(methods.map((m) => m.id)))
  }

  const bulkToggle = async (enabled: boolean) => {
    if (!selected.size) return
    setBulkUpdating(true)
    try {
      await adminApi.setPaymentMethodsStatus([...selected], enabled)
      toast.success(`${enabled ? 'Enabled' : 'Disabled'} ${selected.size} method(s)`)
      setSelected(new Set())
      load()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Update failed')
    } finally {
      setBulkUpdating(false)
    }
  }

  const openCreate = () => {
    setForm(emptyForm())
    setEditing(null)
    setDialogOpen(true)
  }

  const openEdit = (m: AdminPaymentMethod) => {
    const config = m.config && typeof m.config === 'object' ? (m.config as Record<string, string | number>) : {}
    setForm({
      code: m.code,
      name: m.name,
      type: m.type,
      enabled: m.enabled,
      maintenanceMode: m.maintenanceMode,
      sortOrder: m.sortOrder,
      config: { ...config },
    })
    setEditing(m)
    setDialogOpen(true)
  }

  const setCfg = (key: string, value: string | number) => {
    setForm((prev) => ({ ...prev, config: { ...prev.config, [key]: value } }))
  }

  const handleSave = async () => {
    if (!form.code.trim() || !form.name.trim()) {
      toast.error('Code and name are required')
      return
    }
    setSaving(true)
    try {
      const payload = {
        code: form.code.trim().toLowerCase(),
        name: form.name.trim(),
        type: form.type,
        enabled: form.enabled,
        maintenanceMode: form.maintenanceMode,
        sortOrder: Number(form.sortOrder) || 0,
        config: form.config,
      }
      if (editing) {
        await adminApi.updatePaymentMethod(editing.id, payload)
        toast.success('Payment method updated')
      } else {
        await adminApi.createPaymentMethod(payload)
        toast.success('Payment method created')
      }
      setDialogOpen(false)
      load()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  const toggleEnabled = async (m: AdminPaymentMethod, enabled: boolean) => {
    try {
      await adminApi.updatePaymentMethod(m.id, { enabled })
      load()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Update failed')
    }
  }

  const handleDelete = async () => {
    if (!deleteTarget) return
    try {
      await adminApi.deletePaymentMethod(deleteTarget.id)
      toast.success('Payment method deleted')
      setDeleteTarget(null)
      load()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Delete failed')
    }
  }

  const configFields = () => {
    if (form.type === 'mobile_banking') {
      return (
        <>
          <div>
            <Label>Merchant Number</Label>
            <Input value={String(form.config.merchantNumber || '')} onChange={(e) => setCfg('merchantNumber', e.target.value)} placeholder="01711111111" />
          </div>
          <div>
            <Label>Merchant Name</Label>
            <Input value={String(form.config.merchantName || '')} onChange={(e) => setCfg('merchantName', e.target.value)} placeholder="Mama Bazar" />
          </div>
          <div>
            <Label>QR Code Image</Label>
            {form.config.qrCode ? (
              <div className="flex items-center gap-3">
                <img src={String(form.config.qrCode)} alt="" className="h-16 w-16 rounded-md border object-cover" />
                <Button variant="outline" size="sm" onClick={() => setCfg('qrCode', '')}>Remove</Button>
              </div>
            ) : (
              <Button variant="outline" size="sm" onClick={() => setPickerOpen(true)}>Pick QR image</Button>
            )}
          </div>
          <div>
            <Label>Min Amount (Tk)</Label>
            <Input type="number" value={String(form.config.minAmount || '')} onChange={(e) => setCfg('minAmount', e.target.value)} placeholder="50" />
          </div>
          <div>
            <Label>Max Amount (Tk)</Label>
            <Input type="number" value={String(form.config.maxAmount || '')} onChange={(e) => setCfg('maxAmount', e.target.value)} placeholder="200000" />
          </div>
          <div>
            <Label>Extra Fee (Tk)</Label>
            <Input type="number" value={String(form.config.extraFee || '')} onChange={(e) => setCfg('extraFee', e.target.value)} placeholder="0" />
          </div>
          <div>
            <Label>Extra Fee (%)</Label>
            <Input type="number" value={String(form.config.extraFeePercent || '')} onChange={(e) => setCfg('extraFeePercent', e.target.value)} placeholder="e.g. 2" />
          </div>
        </>
      )
    }
    if (form.type === 'bank') {
      return (
        <>
          <div>
            <Label>Bank Name</Label>
            <Input value={String(form.config.bankName || '')} onChange={(e) => setCfg('bankName', e.target.value)} placeholder="DBBL" />
          </div>
          <div>
            <Label>Account Name</Label>
            <Input value={String(form.config.accountName || '')} onChange={(e) => setCfg('accountName', e.target.value)} placeholder="Mama Bazar" />
          </div>
          <div>
            <Label>Account Number</Label>
            <Input value={String(form.config.accountNumber || '')} onChange={(e) => setCfg('accountNumber', e.target.value)} placeholder="1234567890" />
          </div>
          <div>
            <Label>Routing Number</Label>
            <Input value={String(form.config.routingNumber || '')} onChange={(e) => setCfg('routingNumber', e.target.value)} placeholder="123456789" />
          </div>
          <div>
            <Label>Branch</Label>
            <Input value={String(form.config.branch || '')} onChange={(e) => setCfg('branch', e.target.value)} placeholder="Gulshan" />
          </div>
        </>
      )
    }
    return null
  }

  return (
    <AdminLayout>
      <div className="space-y-4">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Payment Methods</h1>
            <p className="text-sm text-muted-foreground">
              Payment options shown at checkout. Online gateways can be marked as "coming soon".
            </p>
          </div>
          <div className="flex items-center gap-2">
            {selected.size > 0 && (
              <>
                <Button variant="outline" size="sm" onClick={() => bulkToggle(true)} disabled={bulkUpdating}>
                  {bulkUpdating && <Loader2 className="mr-1 h-3 w-3 animate-spin" />}Enable ({selected.size})
                </Button>
                <Button variant="outline" size="sm" onClick={() => bulkToggle(false)} disabled={bulkUpdating}>
                  {bulkUpdating && <Loader2 className="mr-1 h-3 w-3 animate-spin" />}Disable ({selected.size})
                </Button>
              </>
            )}
            <Button onClick={openCreate}>
              <Plus className="h-4 w-4" /> Add Method
            </Button>
          </div>
        </div>

        <Card>
          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-[40px]">
                    <input type="checkbox" checked={selected.size === methods.length && methods.length > 0} onChange={toggleAll} />
                  </TableHead>
                  <TableHead>Method</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead>Sort</TableHead>
                  <TableHead>Enabled</TableHead>
                  <TableHead>Maintenance</TableHead>
                  <TableHead className="w-[70px] text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {loading ? (
                  Array.from({ length: 5 }).map((_, i) => (
                    <TableRow key={i}>
                      <TableCell colSpan={7}><Skeleton className="h-10 w-full" /></TableCell>
                    </TableRow>
                  ))
                ) : methods.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7} className="py-12 text-center">
                      <div className="mx-auto mb-2 flex justify-center text-muted-foreground"><CreditCard className="h-8 w-8" /></div>
                      <p className="text-sm text-muted-foreground">No payment methods yet.</p>
                    </TableCell>
                  </TableRow>
                ) : (
                  methods.map((m) => (
                    <TableRow key={m.id}>
                      <TableCell>
                        <input type="checkbox" checked={selected.has(m.id)} onChange={() => toggleRow(m.id)} />
                      </TableCell>
                      <TableCell>
                        <span className="flex items-center gap-2 font-medium">
                          <span className="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-sm">{PAYMENT_ICONS[m.code] || '💳'}</span>
                          {m.name}
                          <span className="text-xs font-normal text-muted-foreground">({m.code})</span>
                        </span>
                      </TableCell>
                      <TableCell><span className="text-sm text-muted-foreground">{TYPE_LABELS[m.type]}</span></TableCell>
                      <TableCell className="text-muted-foreground">{m.sortOrder}</TableCell>
                      <TableCell>
                        <Switch checked={m.enabled} onCheckedChange={(v) => toggleEnabled(m, v)} />
                      </TableCell>
                      <TableCell>
                        {m.maintenanceMode ? <Badge variant="warning">Maintenance</Badge> : <Badge variant="outline">Live</Badge>}
                      </TableCell>
                      <TableCell className="text-right">
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon"><MoreHorizontal className="h-4 w-4" /></Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuItem onClick={() => openEdit(m)} className="cursor-pointer">
                              <Pencil className="mr-2 h-4 w-4" /> Edit
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => setDeleteTarget(m)} className="cursor-pointer text-destructive focus:text-destructive">
                              <Trash2 className="mr-2 h-4 w-4" /> Delete
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{editing ? `Edit ${editing.name}` : 'Add Payment Method'}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-3 sm:grid-cols-2">
            <div>
              <Label>Code</Label>
              <Input
                value={form.code}
                disabled={!!editing}
                onChange={(e) => setForm((prev) => ({ ...prev, code: e.target.value }))}
                placeholder="bkash, nagad, cod..."
              />
            </div>
            <div>
              <Label>Name</Label>
              <Input value={form.name} onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))} placeholder="bKash" />
            </div>
            <div>
              <Label>Type</Label>
              <Select value={form.type} onValueChange={(v) => setForm((prev) => ({ ...prev, type: v as FormState['type'] }))}>
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="cod">Cash on Delivery</SelectItem>
                  <SelectItem value="mobile_banking">Mobile Banking</SelectItem>
                  <SelectItem value="bank">Bank Transfer</SelectItem>
                  <SelectItem value="online">Online Gateway</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label>Sort Order</Label>
              <Input type="number" value={String(form.sortOrder)} onChange={(e) => setForm((prev) => ({ ...prev, sortOrder: Number(e.target.value) || 0 }))} />
            </div>
            <div className="flex items-center justify-between rounded-md border p-3">
              <div>
                <p className="text-sm font-medium">Enabled</p>
                <p className="text-xs text-muted-foreground">Show at checkout</p>
              </div>
              <Switch checked={form.enabled} onCheckedChange={(v) => setForm((prev) => ({ ...prev, enabled: v }))} />
            </div>
            <div className="flex items-center justify-between rounded-md border p-3">
              <div>
                <p className="text-sm font-medium">Maintenance Mode</p>
                <p className="text-xs text-muted-foreground">Temporarily unavailable ("coming soon")</p>
              </div>
              <Switch checked={form.maintenanceMode} onCheckedChange={(v) => setForm((prev) => ({ ...prev, maintenanceMode: v }))} />
            </div>

            {configFields()}

            <div className="sm:col-span-2">
              <Label>Instructions (shown to customer)</Label>
              <Textarea
                rows={3}
                value={String(form.config.instructions || '')}
                onChange={(e) => setCfg('instructions', e.target.value)}
                placeholder="How the customer should pay..."
              />
            </div>
          </div>
          <DialogFooter className="sticky bottom-0 bg-background pt-4">
            <Button variant="outline" onClick={() => setDialogOpen(false)}>Cancel</Button>
            <Button onClick={handleSave} disabled={saving}>
              {saving && <Loader2 className="h-4 w-4 animate-spin" />}
              {editing ? 'Save Changes' : 'Create'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <MediaPicker
        open={pickerOpen}
        onOpenChange={setPickerOpen}
        onSelect={(assets) => {
          if (assets[0]) setCfg('qrCode', assets[0].url)
          setPickerOpen(false)
        }}
        multiple={false}
      />

      <AlertDialog open={!!deleteTarget} onOpenChange={(v) => !v && setDeleteTarget(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete payment method?</AlertDialogTitle>
            <AlertDialogDescription>This action cannot be undone.</AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete} className="bg-destructive text-destructive-foreground hover:bg-destructive/90">
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </AdminLayout>
  )
}

export default AdminPaymentMethodsPage
