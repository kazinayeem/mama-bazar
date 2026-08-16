import { useState } from 'react'
import { AlertCircle, ArrowLeft, Calendar, CheckCircle2, CreditCard, Package, Search, Truck } from 'lucide-react'
import { SEO } from '../components/common/SEO'
import { currency } from '../lib/format'
import { useTrackOrderMutation } from '../store/services/commerceApi'
import type { OrderStatus, PublicOrder, PublicOrderItem } from '../types'

const ORDER_PROGRESS_FLOW: OrderStatus[] = [
  'pending',
  'payment_pending',
  'payment_verification',
  'confirmed',
  'processing',
  'packed',
  'shipped',
  'out_for_delivery',
  'delivered',
]

const CANCELLED_FLOW: OrderStatus[] = ['pending', 'confirmed', 'cancelled']
const RETURNED_FLOW: OrderStatus[] = ['pending', 'confirmed', 'delivered', 'returned']
const REFUNDED_FLOW: OrderStatus[] = ['pending', 'confirmed', 'delivered', 'refunded']

const STATUS_LABELS: Record<string, string> = {
  pending: 'Order Placed',
  payment_pending: 'Payment Pending',
  payment_verification: 'Payment Verification',
  confirmed: 'Confirmed',
  processing: 'Processing',
  packed: 'Packed',
  shipped: 'Shipped',
  out_for_delivery: 'Out for Delivery',
  delivered: 'Delivered',
  returned: 'Returned',
  cancelled: 'Cancelled',
  refunded: 'Refunded',
}

const PAYMENT_STATUS_LABELS: Record<string, string> = {
  pending: 'Pending',
  payment_pending: 'Payment Pending',
  payment_verification: 'Under Verification',
  verified: 'Verified',
  success: 'Paid',
  failed: 'Failed',
  rejected: 'Rejected',
  refunded: 'Refunded',
}

const BD_PHONE_REGEX = /^(\+880|0)[1-9]\d{9}$/

type SearchMode = 'orderId' | 'phone'

const getTimelineFlow = (status: OrderStatus): OrderStatus[] => {
  if (status === 'cancelled') return CANCELLED_FLOW
  if (status === 'returned') return RETURNED_FLOW
  if (status === 'refunded') return REFUNDED_FLOW
  return ORDER_PROGRESS_FLOW
}

const OrderTrackingPage = () => {
  const [mode, setMode] = useState<SearchMode>('orderId')
  const [query, setQuery] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [orders, setOrders] = useState<PublicOrder[]>([])
  const [selected, setSelected] = useState<PublicOrder | null>(null)
  const [searched, setSearched] = useState(false)
  const [trackOrder] = useTrackOrderMutation()

  const handleSearch = async (e: React.FormEvent) => {
    e.preventDefault()

    const value = query.trim()
    if (mode === 'orderId') {
      if (!value) {
        setError('Please enter a valid Order ID.')
        return
      }
    } else {
      if (!BD_PHONE_REGEX.test(value)) {
        setError('Please enter a valid mobile number.')
        return
      }
    }

    setLoading(true)
    setError('')
    setOrders([])
    setSelected(null)
    setSearched(true)

    try {
      const result = await trackOrder(mode === 'orderId' ? { orderId: value } : { phone: value }).unwrap()
      const list = result.orders || []
      setOrders(list)
      if (list.length === 1) {
        setSelected(list[0])
      } else if (mode === 'orderId' && list.length > 0) {
        setSelected(list[0])
      }
    } catch (err) {
      setOrders([])
      setSelected(null)
      setError(err instanceof Error ? err.message : 'No order found. Please check your Order ID or Mobile Number and try again.')
    } finally {
      setLoading(false)
    }
  }

  const inputPlaceholder = mode === 'orderId' ? 'Enter your Order ID' : 'Enter your mobile number'
  const inputLabel = mode === 'orderId' ? 'Order ID' : 'Mobile Number'

  return (
    <main className="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
      <SEO title="Track Your Order" description="Track your Mama Bazar order status using your Order ID or Mobile Number." url="/track" />

      {/* Header */}
      <div className="mb-8 text-center">
        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-green-600 text-white shadow-lg shadow-brand-green-600/20">
          <Package className="h-8 w-8" />
        </div>
        <h1 className="font-headline text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Track Your Order</h1>
        <p className="mt-3 text-sm text-slate-500">Track your order using Order ID or Mobile Number</p>
      </div>

      {/* Search Card */}
      <form onSubmit={handleSearch} className="mb-8">
        <div className="mx-auto max-w-xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft">
          <div className="p-6">
            {/* Search By */}
            <fieldset className="mb-5">
              <legend className="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Search By</legend>
              <div className="flex gap-4">
                <label className="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                  <input
                    checked={mode === 'orderId'}
                    className="h-4 w-4 accent-brand-green-600"
                    name="searchBy"
                    onChange={() => {
                      setMode('orderId')
                      setQuery('')
                      setError('')
                    }}
                    type="radio"
                  />
                  Order ID
                </label>
                <label className="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                  <input
                    checked={mode === 'phone'}
                    className="h-4 w-4 accent-brand-green-600"
                    name="searchBy"
                    onChange={() => {
                      setMode('phone')
                      setQuery('')
                      setError('')
                    }}
                    type="radio"
                  />
                  Mobile Number
                </label>
              </div>
            </fieldset>

            <label className="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500" htmlFor="track-input">
              {inputLabel}
            </label>
            <div className="relative">
              <Package className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <input
                id="track-input"
                inputMode={mode === 'phone' ? 'tel' : 'text'}
                onChange={(e) => {
                  setQuery(e.target.value)
                  setError('')
                }}
                placeholder={inputPlaceholder}
                value={query}
                className="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-sm font-semibold text-slate-900 placeholder:text-slate-400 focus:border-brand-green-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-green-100"
              />
            </div>

            {error && (
              <div className="mt-4 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                {error}
              </div>
            )}

            <button
              type="submit"
              disabled={loading || !query.trim()}
              className="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-brand-orange-500 py-3 text-sm font-bold text-white shadow-lg shadow-brand-orange-500/20 transition hover:bg-brand-orange-600 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {loading ? (
                <>
                  <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white" />
                  Searching...
                </>
              ) : (
                <>
                  <Search className="h-4 w-4" />
                  Track Order
                </>
              )}
            </button>
          </div>
        </div>
      </form>

      {/* No order found */}
      {searched && !loading && orders.length === 0 && !error && (
        <div className="mx-auto max-w-xl rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
          <Package className="mx-auto h-12 w-12 text-slate-300" />
          <p className="mt-4 text-lg font-semibold text-slate-700">No order found.</p>
          <p className="mt-2 text-sm text-slate-500">Please check your Order ID or Mobile Number and try again.</p>
        </div>
      )}

      {/* Multiple orders list (phone search) */}
      {searched && !loading && orders.length > 1 && !selected && !error && (
        <div className="mx-auto max-w-3xl">
          <h2 className="mb-4 font-headline text-lg font-bold text-slate-900">Orders Found ({orders.length})</h2>
          <div className="space-y-3">
            {orders.map((order) => (
              <div key={order.orderId} className="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-soft transition hover:border-brand-green-300">
                <div className="min-w-0 flex-1">
                  <p className="font-headline text-sm font-extrabold text-slate-900">#{order.orderId}</p>
                  <p className="mt-0.5 flex items-center gap-1.5 text-xs text-slate-500">
                    <Calendar className="h-3.5 w-3.5" />
                    {new Date(order.createdAt).toLocaleDateString('en-US', { dateStyle: 'medium' })}
                  </p>
                  <div className="mt-2 flex flex-wrap items-center gap-2">
                    <span className="inline-flex items-center gap-1 rounded-full bg-brand-green-50 px-2.5 py-1 text-xs font-semibold text-brand-green-700">
                      <Truck className="h-3 w-3" />
                      {STATUS_LABELS[order.status] || order.status}
                    </span>
                    <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                      <CreditCard className="h-3 w-3" />
                      Payment: {PAYMENT_STATUS_LABELS[order.paymentStatus] || order.paymentStatus}
                    </span>
                  </div>
                </div>
                <div className="text-right">
                  <p className="text-base font-extrabold text-slate-900">{currency(order.totalPrice)}</p>
                  <button
                    className="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-brand-green-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-brand-green-700"
                    onClick={() => setSelected(order)}
                    type="button"
                  >
                    View Order
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Order detail */}
      {selected && !loading && (
        <div className="space-y-6">
          {orders.length > 1 && (
            <button
              className="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-green-600 transition hover:text-brand-green-700"
              onClick={() => setSelected(null)}
              type="button"
            >
              <ArrowLeft className="h-4 w-4" />
              Back to all orders
            </button>
          )}

          {/* Order Header */}
          <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft">
            <div className="h-1.5 bg-gradient-to-r from-brand-green-500 via-brand-orange-500 to-brand-green-500" />
            <div className="p-6">
              <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                  <p className="text-xs font-bold uppercase tracking-widest text-slate-500">Order</p>
                  <p className="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">#{selected.orderId}</p>
                  <p className="mt-1 flex items-center gap-1.5 text-sm text-slate-500">
                    <Calendar className="h-3.5 w-3.5" />
                    Order Date: {new Date(selected.createdAt).toLocaleDateString('en-US', { dateStyle: 'medium' })}
                  </p>
                </div>
                <div className="text-right">
                  <p className="text-xs font-bold uppercase tracking-widest text-slate-500">Total</p>
                  <p className="mt-1 text-2xl font-extrabold text-slate-900">{currency(selected.totalPrice)}</p>
                </div>
              </div>

              <div className="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div className="rounded-xl border border-slate-100 bg-slate-50 p-3">
                  <p className="text-xs font-bold uppercase tracking-wider text-slate-500">Order Status</p>
                  <p className="mt-1 text-sm font-bold text-slate-900">{STATUS_LABELS[selected.status] || selected.status}</p>
                </div>
                <div className="rounded-xl border border-slate-100 bg-slate-50 p-3">
                  <p className="text-xs font-bold uppercase tracking-wider text-slate-500">Payment Status</p>
                  <p className="mt-1 text-sm font-bold text-slate-900">{PAYMENT_STATUS_LABELS[selected.paymentStatus] || selected.paymentStatus}</p>
                  <p className="text-xs text-slate-500">{(selected.paymentMethod || 'cod').toUpperCase()}</p>
                </div>
                <div className="rounded-xl border border-slate-100 bg-slate-50 p-3">
                  <p className="text-xs font-bold uppercase tracking-wider text-slate-500">Shipping</p>
                  <p className="mt-1 text-sm font-bold text-slate-900">{selected.shippingMethodName || 'Standard'}</p>
                  <p className="text-xs text-slate-500">{selected.courierTrackingNumber ? `Tracking: ${selected.courierTrackingNumber}` : 'Tracking number pending'}</p>
                </div>
              </div>
            </div>
          </div>

          {/* Timeline */}
          <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-soft">
            <h2 className="mb-6 text-lg font-bold text-slate-900">Order Timeline</h2>
            <div className="relative">
              {getTimelineFlow(selected.status).map((step, index) => {
                const isLast = index === getTimelineFlow(selected.status).length - 1
                const isCurrent = selected.status === step
                const historyEntry = selected.statusHistory.find((h) => h.status === step)
                const isCompleted = historyEntry !== undefined || index < getTimelineFlow(selected.status).indexOf(selected.status)
                const isFuture = index > getTimelineFlow(selected.status).indexOf(selected.status) && selected.status !== 'cancelled' && selected.status !== 'returned' && selected.status !== 'refunded'

                return (
                  <div key={step} className="flex gap-4 pb-6 last:pb-0">
                    <div className="flex flex-col items-center">
                      <div
                        className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 transition-all ${
                          isCurrent
                            ? 'border-brand-orange-500 bg-brand-orange-500 text-white shadow-lg shadow-brand-orange-500/20'
                            : isCompleted
                              ? 'border-brand-green-500 bg-brand-green-500 text-white'
                              : 'border-slate-200 bg-white text-slate-300'
                        }`}
                      >
                        {isCompleted && !isCurrent ? (
                          <CheckCircle2 className="h-4 w-4" />
                        ) : isCurrent ? (
                          <div className="h-2.5 w-2.5 rounded-full bg-white" />
                        ) : (
                          <div className="h-2 w-2 rounded-full bg-slate-200" />
                        )}
                      </div>
                      {!isLast && (
                        <div className={`w-0.5 flex-1 ${isCompleted && !isFuture ? 'bg-brand-green-300' : 'bg-slate-100'}`} />
                      )}
                    </div>
                    <div className={`flex-1 ${isFuture ? 'opacity-40' : ''}`}>
                      <div className="flex flex-wrap items-center justify-between gap-2">
                        <p className={`text-sm font-semibold ${isCurrent ? 'text-slate-900' : 'text-slate-700'}`}>
                          {STATUS_LABELS[step] || step}
                        </p>
                        {historyEntry && (
                          <span className="text-xs text-slate-400">
                            {new Date(historyEntry.createdAt).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' })}
                          </span>
                        )}
                      </div>
                    </div>
                  </div>
                )
              })}
            </div>
          </div>

          {/* Items + Totals */}
          <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-soft">
            <h2 className="mb-4 text-lg font-bold text-slate-900">Order Items ({selected.items?.length || 0})</h2>
            <div className="space-y-3">
              {selected.items?.map((item: PublicOrderItem) => (
                <div key={item.id} className="flex items-center gap-4 rounded-xl border border-slate-100 bg-slate-50 p-3">
                  {item.product?.image ? (
                    <img src={item.product.image} alt="" className="h-14 w-14 rounded-lg object-cover" />
                  ) : (
                    <div className="flex h-14 w-14 items-center justify-center rounded-lg bg-slate-200">
                      <Package className="h-6 w-6 text-slate-400" />
                    </div>
                  )}
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-semibold text-slate-900">{item.product?.title || `Product #${item.productId}`}</p>
                    <p className="mt-0.5 text-xs text-slate-500">
                      Qty: {item.quantity} × {currency(item.price)}
                      {item.color ? ` · Color: ${item.color}` : ''}
                      {item.size ? ` · Size: ${item.size}` : ''}
                    </p>
                  </div>
                  <span className="text-sm font-bold text-slate-900">{currency(Number(item.price) * item.quantity)}</span>
                </div>
              ))}
            </div>

            <div className="mt-4 border-t border-slate-100 pt-4">
              <div className="space-y-1.5 text-sm">
                <div className="flex justify-between">
                  <span className="text-slate-500">Subtotal</span>
                  <span className="font-medium">{currency(Number(selected.subtotal) || 0)}</span>
                </div>
                {Number(selected.discount) > 0 && (
                  <div className="flex justify-between text-brand-green-600">
                    <span>Discount</span>
                    <span className="font-medium">-{currency(Number(selected.discount))}</span>
                  </div>
                )}
                <div className="flex justify-between">
                  <span className="text-slate-500">Shipping</span>
                  <span className="font-medium">{Number(selected.shippingCost) === 0 ? 'FREE' : currency(selected.shippingCost)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-slate-500">Tax</span>
                  <span className="font-medium">{currency(Number(selected.tax) || 0)}</span>
                </div>
                <div className="flex justify-between border-t border-slate-200 pt-2 text-base font-bold">
                  <span>Total</span>
                  <span>{currency(selected.totalPrice)}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </main>
  )
}

export default OrderTrackingPage