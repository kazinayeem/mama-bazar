import { useCallback, useEffect, useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import {
  Check,
  Eye,
  Loader2,
  Phone,
  Search,
  ShieldCheck,
  ShoppingCart,
  StickyNote,
  Truck,
  FileText,
  XCircle,
} from 'lucide-react'
import { toast } from 'sonner'
import AdminLayout from '../../components/layout/AdminLayout'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog'
import { Skeleton } from '@/components/ui/skeleton'
import { adminApi } from '@/lib/adminApi'
import { currency } from '@/lib/format'
import {
  getAllowedNextStatuses,
  getBlockedStatuses,
  ORDER_STATUS_FLOW,
  ORDER_STATUS_LABELS as STATUS_LABELS,
  PAYMENT_STATUS_LABELS,
  shouldShowPaymentVerificationPanel,
} from '@/lib/orderLifecycle'
import type { AdminOrder } from '@/types/admin'
import type { OrderStatus } from '@/types'
import { SEO } from '../../components/common/SEO'

const STATUS_COLORS: Record<string, string> = {
  pending: 'bg-warning/15 text-warning',
  payment_pending: 'bg-warning/15 text-warning',
  payment_verification: 'bg-primary/10 text-primary',
  confirmed: 'bg-primary/10 text-primary',
  processing: 'bg-primary/10 text-primary',
  packed: 'bg-accent/10 text-emerald-600',
  shipped: 'bg-primary/10 text-primary',
  out_for_delivery: 'bg-accent/10 text-emerald-600',
  delivered: 'bg-success/15 text-success',
  returned: 'bg-muted text-muted-foreground',
  cancelled: 'bg-destructive/10 text-destructive',
  refunded: 'bg-destructive/10 text-destructive',
}

const AdminOrdersPage = () => {
  const navigate = useNavigate()
  const [orders, setOrders] = useState<AdminOrder[]>([])
  const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)
  const [total, setTotal] = useState(0)
  const [totalPages, setTotalPages] = useState(1)
  const [status, setStatus] = useState('')
  const [searchInput, setSearchInput] = useState('')
  const [search, setSearch] = useState('')
  const [selectedOrder, setSelectedOrder] = useState<AdminOrder | null>(null)
  const [detailLoading, setDetailLoading] = useState(false)
  const [updatingStatus, setUpdatingStatus] = useState<string | null>(null)
  const [verifying, setVerifying] = useState<'verified' | 'rejected' | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<AdminOrder | null>(null)
  const [noteInput, setNoteInput] = useState('')
  const [noteSaving, setNoteSaving] = useState(false)

  const load = useCallback(
    async (params: { page?: number; status?: string; search?: string } = {}) => {
      setLoading(true)
      try {
        const result = await adminApi.getOrders({
          page: params.page ?? page,
          limit: 20,
          status: params.status ?? status,
          search: params.search !== undefined ? params.search : search,
        })
        setOrders(result.data)
        setTotal(result.total)
        setTotalPages(result.totalPages)
      } catch (err) {
        toast.error(err instanceof Error ? err.message : 'Failed to load orders')
      } finally {
        setLoading(false)
      }
    },
    [page, status, search],
  )

  useEffect(() => {
    load()
  }, [load])

  useEffect(() => {
    const timer = setTimeout(() => {
      setSearch(searchInput)
      setPage(1)
    }, 500)
    return () => clearTimeout(timer)
  }, [searchInput])

  const filtered = useMemo(() => {
    if (!search.trim()) return orders
    const q = search.toLowerCase()
    return orders.filter((o) => o.orderId.toLowerCase().includes(q) || o.customerName.toLowerCase().includes(q) || o.phone.includes(q))
  }, [orders, search])

  const openDetail = async (order: AdminOrder) => {
    setSelectedOrder(order)
    setDetailLoading(true)
    try {
      const detail = await adminApi.getOrder(order.id)
      setSelectedOrder(detail)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to load order')
    } finally {
      setDetailLoading(false)
    }
  }

  const updateStatus = async (next: OrderStatus) => {
    if (!selectedOrder) return
    setUpdatingStatus(next)
    try {
      const updated = await adminApi.updateOrderStatus(selectedOrder.id, { status: next })
      toast.success(`Order marked as ${STATUS_LABELS[next]}`)
      setSelectedOrder(updated)
      setOrders((prev) => prev.map((order) => (order.id === updated.id ? { ...order, ...updated } : order)))
      void load({ page, status, search })
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Update failed')
    } finally {
      setUpdatingStatus(null)
    }
  }

  const verifyPayment = async (action: 'verified' | 'rejected') => {
    if (!selectedOrder) return
    setVerifying(action)
    try {
      const updated = await adminApi.verifyOrderPayment(selectedOrder.id, action)
      toast.success(action === 'verified' ? 'Payment verified — order confirmed' : 'Payment rejected')
      setSelectedOrder(updated)
      setOrders((prev) => prev.map((order) => (order.id === updated.id ? { ...order, ...updated } : order)))
      void load({ page, status, search })
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Verification failed')
    } finally {
      setVerifying(null)
    }
  }

  const addNote = async () => {
    if (!selectedOrder || !noteInput.trim()) return
    setNoteSaving(true)
    try {
      const detail = await adminApi.addOrderAdminNote(selectedOrder.id, noteInput.trim())
      setSelectedOrder(detail)
      setNoteInput('')
      toast.success('Note added')
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to add note')
    } finally {
      setNoteSaving(false)
    }
  }

  const handleDelete = async () => {
    if (!deleteTarget) return
    try {
      await adminApi.deleteOrder(deleteTarget.id)
      toast.success('Order deleted')
      setDeleteTarget(null)
      setSelectedOrder(null)
      load()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Delete failed')
    }
  }

  const timeline = selectedOrder?.statusHistory || []
  const awaitingVerification = selectedOrder ? shouldShowPaymentVerificationPanel(selectedOrder) : false
  const allowedNextStatuses = selectedOrder ? getAllowedNextStatuses(selectedOrder) : []
  const blockedStatuses = selectedOrder ? getBlockedStatuses(selectedOrder) : []

  return (
    <AdminLayout>
      <SEO title="Manage Orders" description="View and manage customer orders. Process orders and track shipments." url="/admin/orders" />
      <div className="space-y-4">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Orders</h1>
            <p className="text-sm text-muted-foreground">{total} orders total</p>
          </div>
          <Button variant="outline" onClick={() => load()} className="w-fit">Refresh</Button>
        </div>

        <Card>
          <CardContent className="p-3">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  placeholder="Search by order ID, customer or phone..."
                  value={searchInput}
                  onChange={(e) => setSearchInput(e.target.value)}
                  className="pl-9"
                />
              </div>
              <Select value={status} onValueChange={(v) => { setStatus(v); setPage(1); load({ page: 1, status: v }) }}>
                <SelectTrigger className="w-48">
                  <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All statuses</SelectItem>
                  {ORDER_STATUS_FLOW.map((s) => (
                    <SelectItem key={s} value={s}>
                      {STATUS_LABELS[s]}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Order</TableHead>
                  <TableHead>Customer</TableHead>
                  <TableHead>Items</TableHead>
                  <TableHead className="text-right">Total</TableHead>
                  <TableHead>Payment</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {loading ? (
                  Array.from({ length: 8 }).map((_, i) => (
                    <TableRow key={i}>
                      <TableCell colSpan={7}>
                        <Skeleton className="h-10 w-full" />
                      </TableCell>
                    </TableRow>
                  ))
                ) : filtered.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7} className="py-12 text-center">
                      <ShoppingCart className="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
                      <p className="text-sm text-muted-foreground">No orders found</p>
                    </TableCell>
                  </TableRow>
                ) : (
                  filtered.map((order) => (
                    <TableRow key={order.id} className="cursor-pointer" onClick={() => openDetail(order)}>
                      <TableCell>
                        <p className="text-sm font-semibold">{order.orderId}</p>
                        <p className="text-xs text-muted-foreground">
                          {new Date(order.createdAt).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' })}
                        </p>
                      </TableCell>
                      <TableCell>
                        <p className="text-sm font-medium">{order.customerName}</p>
                        <p className="text-xs text-muted-foreground">{order.phone}</p>
                      </TableCell>
                      <TableCell className="text-sm text-muted-foreground">
                        {order.items?.length ?? 0} items
                      </TableCell>
                      <TableCell className="text-right text-sm font-semibold">
                        {currency(order.totalPrice)}
                        {Number(order.discount) > 0 && (
                          <p className="text-xs text-success">-{currency(Number(order.discount))}</p>
                        )}
                      </TableCell>
                      <TableCell>
                        <div className="flex flex-col items-start gap-1">
                          <Badge variant={order.paymentMethod === 'cod' ? 'outline' : 'secondary'}>
                            {order.paymentMethod.toUpperCase()}
                          </Badge>
                          {order.paymentStatus === 'payment_verification' && (
                            <Badge className="bg-primary/15 text-primary">Verify needed</Badge>
                          )}
                        </div>
                      </TableCell>
                      <TableCell>
                        <Badge variant="secondary" className={STATUS_COLORS[order.status] || ''}>
                          {STATUS_LABELS[order.status] || order.status}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right">
                        <Button variant="ghost" size="icon" onClick={(e) => { e.stopPropagation(); openDetail(order) }}>
                          <Eye className="h-4 w-4" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>

            {totalPages > 1 && (
              <div className="flex items-center justify-between border-t p-3">
                <span className="text-xs text-muted-foreground">Page {page} of {totalPages} · {total} orders</span>
                <div className="flex items-center gap-2">
                  <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => { const p = page - 1; setPage(p); load({ page: p }) }}>
                    Previous
                  </Button>
                  <Button variant="outline" size="sm" disabled={page >= totalPages} onClick={() => { const p = page + 1; setPage(p); load({ page: p }) }}>
                    Next
                  </Button>
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Order detail sheet */}
      <Sheet open={!!selectedOrder} onOpenChange={(v) => !v && setSelectedOrder(null)}>
        <SheetContent className="w-full overflow-y-auto sm:max-w-lg">
          {selectedOrder && (
            <>
              <SheetHeader>
                <SheetTitle className="flex items-center gap-2">
                  {selectedOrder.orderId}
                  <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    className="h-7 w-7"
                    title="View / print invoice"
                    onClick={() => navigate(`/admin/orders/${selectedOrder.id}/invoice`)}
                  >
                    <FileText className="h-3.5 w-3.5" />
                  </Button>
                </SheetTitle>
                <SheetDescription>
                  Placed {new Date(selectedOrder.createdAt).toLocaleString('en-US', { dateStyle: 'full', timeStyle: 'short' })}
                </SheetDescription>
              </SheetHeader>

              <div className="mt-4 space-y-5">
                {/* Payment verification */}
                {awaitingVerification && (
                  <div className="rounded-lg border border-primary/30 bg-primary/5 p-4">
                    <p className="flex items-center gap-2 text-sm font-semibold">
                      <ShieldCheck className="h-4 w-4 text-primary" /> Payment Verification Required
                    </p>
                    <p className="mt-1 text-xs text-muted-foreground">
                      Customer paid via {selectedOrder.paymentMethod.toUpperCase()}
                      {selectedOrder.amountSent ? ` — Tk ${selectedOrder.amountSent}` : ''}
                      {selectedOrder.senderNumber ? ` from ${selectedOrder.senderNumber}` : ''}.
                      Check the screenshot and TrxID before approving.
                    </p>
                    {selectedOrder.transactionId && (
                      <p className="mt-1 text-xs">TrxID: <span className="font-mono font-semibold">{selectedOrder.transactionId}</span></p>
                    )}
                    {selectedOrder.paymentScreenshot && (
                      <a className="mt-2 block" href={selectedOrder.paymentScreenshot} target="_blank" rel="noreferrer">
                        <img src={selectedOrder.paymentScreenshot} alt="Payment proof" className="max-h-40 rounded-md border object-cover" />
                      </a>
                    )}
                    <div className="mt-3 flex gap-2">
                      <Button size="sm" className="bg-success text-success-foreground hover:bg-success/90" disabled={verifying !== null} onClick={() => verifyPayment('verified')}>
                        {verifying === 'verified' ? <Loader2 className="h-3 w-3 animate-spin" /> : <Check className="h-3 w-3" />} Approve
                      </Button>
                      <Button size="sm" variant="outline" className="text-destructive" disabled={verifying !== null} onClick={() => verifyPayment('rejected')}>
                        {verifying === 'rejected' ? <Loader2 className="h-3 w-3 animate-spin" /> : <XCircle className="h-3 w-3" />} Reject
                      </Button>
                    </div>
                  </div>
                )}

                {/* Status update */}
                <div className="rounded-lg border p-4">
                  <p className="mb-2 text-sm font-semibold">Update Status</p>
                  <div className="flex flex-wrap gap-1.5">
                    {allowedNextStatuses.map((s) => (
                      <Button
                        key={s}
                        size="sm"
                        variant="outline"
                        disabled={updatingStatus !== null}
                        onClick={() => updateStatus(s)}
                      >
                        {updatingStatus === s && <Loader2 className="h-3 w-3 animate-spin" />}
                        {STATUS_LABELS[s]}
                      </Button>
                    ))}
                  </div>
                  <div className="mt-3">
                    <Badge variant="secondary" className={STATUS_COLORS[selectedOrder.status]}>
                      Current: {STATUS_LABELS[selectedOrder.status]}
                    </Badge>
                  </div>
                  <div className="mt-4 rounded-md bg-muted/50 p-3">
                    <p className="text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">Lifecycle overview</p>
                    <div className="mt-3 flex flex-wrap gap-2">
                      {ORDER_STATUS_FLOW.map((statusItem) => {
                        const isCurrent = statusItem === selectedOrder.status
                        const isNext = allowedNextStatuses.includes(statusItem)
                        const isBlocked = !isCurrent && !isNext
                        return (
                          <span
                            key={statusItem}
                            className={`inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold ${
                              isCurrent
                                ? 'border-primary bg-primary text-white'
                                : isNext
                                  ? 'border-primary/30 bg-primary/10 text-primary'
                                  : 'border-slate-200 bg-slate-100 text-slate-400'
                            } ${isBlocked ? 'opacity-80' : ''}`}
                          >
                            {STATUS_LABELS[statusItem]}
                          </span>
                        )
                      })}
                    </div>
                  </div>

                  {blockedStatuses.length > 0 && (
                    <div className="mt-3 text-xs text-muted-foreground">
                      Locked from this state:
                      <span className="ml-2 flex flex-wrap gap-1.5 pt-2">
                        {blockedStatuses.map((statusItem) => (
                          <span key={statusItem} className="rounded-full border border-slate-200 px-2.5 py-1 text-[11px] text-slate-400">
                            {STATUS_LABELS[statusItem]}
                          </span>
                        ))}
                      </span>
                    </div>
                  )}
                </div>

                {/* Items */}
                <div>
                  <p className="mb-2 text-sm font-semibold">Items ({selectedOrder.items?.length || 0})</p>
                  <div className="space-y-2">
                    {detailLoading ? (
                      <Skeleton className="h-20 w-full" />
                    ) : (
                      selectedOrder.items?.map((item) => (
                        <div key={item.id} className="flex items-center gap-3 rounded-lg border p-2.5">
                          {item.product?.image ? (
                            <img src={item.product.image} alt="" className="h-10 w-10 rounded-md object-cover" />
                          ) : (
                            <div className="flex h-10 w-10 items-center justify-center rounded-md bg-muted">
                              <ShoppingCart className="h-4 w-4 text-muted-foreground" />
                            </div>
                          )}
                          <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium">{item.product?.title || `Product #${item.productId}`}</p>
                            <p className="text-xs text-muted-foreground">
                              {item.quantity} × {currency(item.price)}
                              {item.color ? ` · ${item.color}` : ''}
                              {item.size ? ` · ${item.size}` : ''}
                            </p>
                          </div>
                          <span className="text-sm font-semibold">{currency(Number(item.price) * item.quantity)}</span>
                        </div>
                      ))
                    )}
                  </div>
                </div>

                {/* Summary */}
                <div className="rounded-lg bg-muted/50 p-4">
                  <div className="space-y-1.5 text-sm">
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Subtotal</span>
                      <span className="font-medium">{currency(Number(selectedOrder.subtotal) || 0)}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Shipping{selectedOrder.shippingMethodName ? ` (${selectedOrder.shippingMethodName})` : ''}</span>
                      <span className="font-medium">{currency(selectedOrder.shippingCost)}</span>
                    </div>
                    {Number(selectedOrder.discount) > 0 && (
                      <div className="flex justify-between">
                        <span className="text-muted-foreground">Discount{selectedOrder.couponCode ? ` (${selectedOrder.couponCode})` : ''}</span>
                        <span className="font-medium text-success">-{currency(Number(selectedOrder.discount))}</span>
                      </div>
                    )}
                    {Number(selectedOrder.tax) > 0 && (
                      <div className="flex justify-between">
                        <span className="text-muted-foreground">VAT</span>
                        <span className="font-medium">{currency(Number(selectedOrder.tax))}</span>
                      </div>
                    )}
                    <div className="flex justify-between border-t pt-2 text-base font-bold">
                      <span>Total</span>
                      <span>{currency(selectedOrder.totalPrice)}</span>
                    </div>
                  </div>
                </div>

                {/* Payment details */}
                <div className="rounded-lg border p-4">
                  <p className="mb-2 text-sm font-semibold">Payment Details</p>
                  <div className="space-y-1 text-sm">
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Method</span>
                      <span className="font-medium">{selectedOrder.paymentMethod.toUpperCase()}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Status</span>
                      <Badge variant={selectedOrder.paymentStatus === 'success' || selectedOrder.paymentStatus === 'verified' ? 'success' : 'warning'}>
                        {PAYMENT_STATUS_LABELS[selectedOrder.paymentStatus] || selectedOrder.paymentStatus}
                      </Badge>
                    </div>
                    {selectedOrder.transactionId && (
                      <div className="flex justify-between">
                        <span className="text-muted-foreground">TrxID</span>
                        <span className="font-mono font-medium">{selectedOrder.transactionId}</span>
                      </div>
                    )}
                    {selectedOrder.senderNumber && (
                      <div className="flex justify-between">
                        <span className="text-muted-foreground">Sender</span>
                        <span className="font-medium">{selectedOrder.senderNumber}</span>
                      </div>
                    )}
                    {selectedOrder.amountSent && (
                      <div className="flex justify-between">
                        <span className="text-muted-foreground">Amount Sent</span>
                        <span className="font-medium">{currency(Number(selectedOrder.amountSent))}</span>
                      </div>
                    )}
                    {selectedOrder.paymentDate && (
                      <div className="flex justify-between">
                        <span className="text-muted-foreground">Paid At</span>
                        <span className="font-medium">{new Date(selectedOrder.paymentDate).toLocaleString()}</span>
                      </div>
                    )}
                  </div>
                </div>

                {/* Customer */}
                <div className="rounded-lg border p-4">
                  <p className="mb-2 text-sm font-semibold">Customer</p>
                  <p className="text-sm font-medium">{selectedOrder.customerName}</p>
                  <p className="flex items-center gap-1 text-sm text-muted-foreground">
                    <Phone className="h-3.5 w-3.5" /> {selectedOrder.phone}
                    {selectedOrder.alternativePhone ? ` / ${selectedOrder.alternativePhone}` : ''}
                  </p>
                  {selectedOrder.email && <p className="text-xs text-muted-foreground">{selectedOrder.email}</p>}
                  <p className="mt-2 text-sm text-muted-foreground">{selectedOrder.address}</p>
                  {selectedOrder.area && (
                    <p className="text-xs text-muted-foreground">
                      {[selectedOrder.division, selectedOrder.district, selectedOrder.upazila, selectedOrder.area].filter(Boolean).join(', ')}
                      {selectedOrder.postalCode ? ` - ${selectedOrder.postalCode}` : ''}
                    </p>
                  )}
                  {selectedOrder.orderNote && (
                    <p className="mt-2 rounded-md bg-muted/60 p-2 text-xs">
                      <span className="font-semibold">Order note:</span> {selectedOrder.orderNote}
                    </p>
                  )}
                  {selectedOrder.courierTrackingNumber && (
                    <p className="mt-2 flex items-center gap-1 text-xs text-muted-foreground">
                      <Truck className="h-3.5 w-3.5" /> Tracking: {selectedOrder.courierTrackingNumber}
                    </p>
                  )}
                </div>

                {/* Admin notes */}
                <div className="rounded-lg border p-4">
                  <p className="mb-2 flex items-center gap-1.5 text-sm font-semibold">
                    <StickyNote className="h-4 w-4" /> Admin Notes
                  </p>
                  {selectedOrder.adminNotes ? (
                    <div className="mb-3 max-h-32 space-y-1 overflow-y-auto rounded-md bg-muted/60 p-2 text-xs whitespace-pre-line">
                      {selectedOrder.adminNotes}
                    </div>
                  ) : (
                    <p className="mb-3 text-xs text-muted-foreground">No notes yet.</p>
                  )}
                  <div className="flex gap-2">
                    <Input
                      placeholder="Add an internal note..."
                      value={noteInput}
                      onChange={(e) => setNoteInput(e.target.value)}
                      onKeyDown={(e) => e.key === 'Enter' && addNote()}
                    />
                    <Button onClick={addNote} disabled={noteSaving || !noteInput.trim()}>
                      {noteSaving ? <Loader2 className="h-3 w-3 animate-spin" /> : 'Add'}
                    </Button>
                  </div>
                </div>

                {/* Timeline */}
                <div>
                  <p className="mb-2 text-sm font-semibold">Activity</p>
                  {timeline.length === 0 ? (
                    <p className="text-sm text-muted-foreground">No status history recorded</p>
                  ) : (
                    <div className="space-y-0">
                      {[...timeline].reverse().map((entry, index, arr) => (
                        <div key={entry.id} className="flex gap-3">
                          <div className="flex flex-col items-center">
                            <span className={`mt-1 flex h-4 w-4 items-center justify-center rounded-full border ${index === 0 ? 'border-primary bg-primary/15' : 'border-border bg-muted'}`}>
                              {index === 0 && <Check className="h-2.5 w-2.5 text-primary" />}
                            </span>
                            {index !== arr.length - 1 && <span className="w-px flex-1 bg-border" />}
                          </div>
                          <div className={index === arr.length - 1 ? 'pb-0' : 'pb-4'}>
                            <p className="text-sm font-medium">{STATUS_LABELS[entry.status] || entry.status}</p>
                            <p className="text-xs text-muted-foreground">
                              {new Date(entry.createdAt).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' })}
                            </p>
                            {entry.note && <p className="mt-0.5 text-xs text-muted-foreground">{entry.note}</p>}
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>

                <Button
                  variant="destructive"
                  className="w-full"
                  onClick={() => { setSelectedOrder(null); setDeleteTarget(selectedOrder) }}
                >
                  Delete Order
                </Button>
              </div>
            </>
          )}
        </SheetContent>
      </Sheet>

      <AlertDialog open={!!deleteTarget} onOpenChange={(v) => !v && setDeleteTarget(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete order?</AlertDialogTitle>
            <AlertDialogDescription>
              Order "{deleteTarget?.orderId}" will be permanently removed.
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

export default AdminOrdersPage
