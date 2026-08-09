import { useCallback, useEffect, useState } from 'react'
import { Calendar, Loader2, Pencil, Plus, TicketPercent, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import AdminLayout from '../../components/layout/AdminLayout'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Switch } from '@/components/ui/switch'
import { Card, CardContent } from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
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
import { adminApi } from '@/lib/adminApi'
import { currency } from '@/lib/format'
import type { AdminCoupon } from '@/types'
import { SEO } from '../../components/common/SEO'

const AdminCouponsPage = () => {
  const [coupons, setCoupons] = useState<AdminCoupon[]>([])
  const [loading, setLoading] = useState(true)
  const [dialogOpen, setDialogOpen] = useState(false)
  const [editing, setEditing] = useState<AdminCoupon | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<AdminCoupon | null>(null)
  const [saving, setSaving] = useState(false)

  const [code, setCode] = useState('')
  const [discountType, setDiscountType] = useState<'percentage' | 'fixed'>('percentage')
  const [discountValue, setDiscountValue] = useState('')
  const [minOrderAmount, setMinOrderAmount] = useState('')
  const [expiryDate, setExpiryDate] = useState('')
  const [active, setActive] = useState(true)

  const load = useCallback(async () => {
    setLoading(true)
    try {
      setCoupons(await adminApi.getCoupons())
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to load coupons')
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load()
  }, [load])

  const openCreate = () => {
    setEditing(null)
    setCode('')
    setDiscountType('percentage')
    setDiscountValue('')
    setMinOrderAmount('')
    setExpiryDate('')
    setActive(true)
    setDialogOpen(true)
  }

  const openEdit = (coupon: AdminCoupon) => {
    setEditing(coupon)
    setCode(coupon.code)
    setDiscountType(coupon.discountType)
    setDiscountValue(String(coupon.discountValue))
    setMinOrderAmount(coupon.minOrderAmount ? String(coupon.minOrderAmount) : '')
    setExpiryDate(coupon.expiryDate ? new Date(coupon.expiryDate).toISOString().slice(0, 10) : '')
    setActive(coupon.status === 'active')
    setDialogOpen(true)
  }

  const handleSave = async () => {
    if (!code.trim() || !discountValue || Number(discountValue) <= 0) {
      toast.error('Code and a valid discount value are required')
      return
    }
    setSaving(true)
    try {
      const payload = {
        code: code.trim().toUpperCase(),
        discountType,
        discountValue: Number(discountValue),
        minOrderAmount: minOrderAmount ? Number(minOrderAmount) : undefined,
        expiryDate: expiryDate ? new Date(expiryDate).toISOString() : undefined,
        status: active ? 'active' as const : 'inactive' as const,
      }
      if (editing) {
        await adminApi.updateCoupon(editing.id, payload)
        toast.success('Coupon updated')
      } else {
        await adminApi.createCoupon(payload)
        toast.success('Coupon created')
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
      await adminApi.deleteCoupon(deleteTarget.id)
      toast.success('Coupon deleted')
      setDeleteTarget(null)
      load()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Delete failed')
    }
  }

  const isExpired = (coupon: AdminCoupon) =>
    coupon.expiryDate ? new Date(coupon.expiryDate) < new Date() : false

  return (
    <AdminLayout>
      <SEO title="Manage Coupons" description="Create and manage discount coupons." url="/admin/coupons" />
      <div className="space-y-4">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Coupons</h1>
            <p className="text-sm text-muted-foreground">{coupons.length} discount codes</p>
          </div>
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4" /> Create Coupon
          </Button>
        </div>

        <Card>
          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Code</TableHead>
                  <TableHead>Discount</TableHead>
                  <TableHead>Min Order</TableHead>
                  <TableHead>Expiry</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {loading ? (
                  Array.from({ length: 5 }).map((_, i) => (
                    <TableRow key={i}>
                      <TableCell colSpan={6}>
                        <Skeleton className="h-10 w-full" />
                      </TableCell>
                    </TableRow>
                  ))
                ) : coupons.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={6} className="py-12 text-center">
                      <TicketPercent className="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
                      <p className="text-sm text-muted-foreground">No coupons yet</p>
                    </TableCell>
                  </TableRow>
                ) : (
                  coupons.map((coupon) => (
                    <TableRow key={coupon.id}>
                      <TableCell>
                        <span className="rounded bg-primary/10 px-2 py-1 font-mono text-sm font-bold text-primary">
                          {coupon.code}
                        </span>
                      </TableCell>
                      <TableCell className="text-sm font-medium">
                        {coupon.discountType === 'percentage'
                          ? `${coupon.discountValue}%`
                          : currency(coupon.discountValue)}
                      </TableCell>
                      <TableCell className="text-sm text-muted-foreground">
                        {coupon.minOrderAmount ? currency(coupon.minOrderAmount) : '—'}
                      </TableCell>
                      <TableCell className="text-sm text-muted-foreground">
                        {coupon.expiryDate ? (
                          <span className="flex items-center gap-1">
                            <Calendar className="h-3.5 w-3.5" />
                            {new Date(coupon.expiryDate).toLocaleDateString()}
                          </span>
                        ) : (
                          'No expiry'
                        )}
                      </TableCell>
                      <TableCell>
                        {isExpired(coupon) ? (
                          <Badge variant="destructive">Expired</Badge>
                        ) : (
                          <Badge variant={coupon.status === 'active' ? 'success' : 'muted'}>
                            {coupon.status}
                          </Badge>
                        )}
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex justify-end gap-1">
                          <Button variant="ghost" size="icon" onClick={() => openEdit(coupon)}>
                            <Pencil className="h-4 w-4" />
                          </Button>
                          <Button variant="ghost" size="icon" className="text-destructive" onClick={() => setDeleteTarget(coupon)}>
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>
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
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{editing ? 'Edit Coupon' : 'Create Coupon'}</DialogTitle>
          </DialogHeader>
          <div className="space-y-4">
            <div>
              <Label htmlFor="cp-code">Coupon Code *</Label>
              <Input
                id="cp-code"
                value={code}
                onChange={(e) => setCode(e.target.value.toUpperCase())}
                placeholder="SAVE20"
                className="font-mono uppercase"
              />
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <Label>Discount Type</Label>
                <Select value={discountType} onValueChange={(v) => setDiscountType(v as 'percentage' | 'fixed')}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="percentage">Percentage (%)</SelectItem>
                    <SelectItem value="fixed">Fixed (Tk)</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label htmlFor="cp-value">Discount Value *</Label>
                <Input
                  id="cp-value"
                  type="number"
                  step="0.01"
                  value={discountValue}
                  onChange={(e) => setDiscountValue(e.target.value)}
                  placeholder={discountType === 'percentage' ? '20' : '500'}
                />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <Label htmlFor="cp-min">Min Order Amount</Label>
                <Input
                  id="cp-min"
                  type="number"
                  value={minOrderAmount}
                  onChange={(e) => setMinOrderAmount(e.target.value)}
                  placeholder="0"
                />
              </div>
              <div>
                <Label htmlFor="cp-expiry">Expiry Date</Label>
                <Input id="cp-expiry" type="date" value={expiryDate} onChange={(e) => setExpiryDate(e.target.value)} />
              </div>
            </div>
            <label className="flex items-center gap-2 text-sm">
              <Switch checked={active} onCheckedChange={setActive} />
              Active
            </label>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogOpen(false)}>Cancel</Button>
            <Button onClick={handleSave} disabled={saving}>
              {saving && <Loader2 className="h-4 w-4 animate-spin" />}
              {editing ? 'Save Changes' : 'Create Coupon'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <AlertDialog open={!!deleteTarget} onOpenChange={(v) => !v && setDeleteTarget(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete coupon?</AlertDialogTitle>
            <AlertDialogDescription>
              The "{deleteTarget?.code}" coupon will no longer be redeemable.
            </AlertDialogDescription>
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

export default AdminCouponsPage
