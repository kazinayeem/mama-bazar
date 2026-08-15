import { useState, useCallback, useRef } from 'react'
import { Search, Package, CheckCircle2, AlertCircle, FileText, Download, Printer, MapPin, CreditCard, Calendar } from 'lucide-react'
import { SEO } from '../components/common/SEO'
import InvoiceTemplate from '../components/invoice/InvoiceTemplate'
import { printInvoice, downloadInvoicePdf, generateInvoiceFilename } from '../lib/invoiceUtils'
import { api } from '../lib/api'
import { currency } from '../lib/format'
import type { UserOrderWithItems, UserOrderItem, OrderStatus } from '../types'

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

const getTimelineFlow = (status: OrderStatus): OrderStatus[] => {
  if (status === 'cancelled') return CANCELLED_FLOW
  if (status === 'returned') return RETURNED_FLOW
  if (status === 'refunded') return REFUNDED_FLOW
  return ORDER_PROGRESS_FLOW
}

const OrderTrackingPage = () => {
  const [orderId, setOrderId] = useState('')
  const [phone, setPhone] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [order, setOrder] = useState<UserOrderWithItems | null>(null)
  const [searched, setSearched] = useState(false)
  const [showInvoice, setShowInvoice] = useState(false)
  const invoiceRef = useRef<HTMLDivElement>(null)

  const handleSearch = useCallback(async (e: React.FormEvent) => {
    e.preventDefault()
    if (!orderId.trim() || !phone.trim()) {
      setError('Please enter both Order ID and Phone number')
      return
    }

    setLoading(true)
    setError('')
    setOrder(null)
    setSearched(true)
    setShowInvoice(false)

    try {
      const result = await api.trackOrder(orderId.trim(), phone.trim())
      setOrder(result)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Order not found. Please check your Order ID and Phone number and try again.')
    } finally {
      setLoading(false)
    }
  }, [orderId, phone])

  const handlePrint = () => {
    printInvoice('invoice-content')
  }

  const handleDownload = async () => {
    if (!order) return
    try {
      await downloadInvoicePdf('invoice-content', generateInvoiceFilename(order.orderId))
    } catch (err) {
      console.error('PDF download failed:', err)
    }
  }

  const timelineFlow = order ? getTimelineFlow(order.status) : []
  const statusHistory = order?.statusHistory || []

  return (
    <main className="min-h-screen bg-gradient-to-b from-slate-50 to-white">
      <SEO title="Track Your Order" description="Track your Mama Bazar order status and download invoice." url="/track" />

      <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="mb-8 text-center">
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-lg">
            <Package className="h-8 w-8" />
          </div>
          <h1 className="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
            Track Your Order
          </h1>
          <p className="mt-3 text-sm text-slate-500">
            Enter your Order ID and phone number to track your order status
          </p>
        </div>

        {/* Search Form */}
        <form onSubmit={handleSearch} className="mb-8">
          <div className="mx-auto max-w-xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
            <div className="p-6">
              <div className="space-y-4">
                <div>
                  <label htmlFor="orderId" className="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">
                    Order ID
                  </label>
                  <div className="relative">
                    <Package className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                      id="orderId"
                      type="text"
                      value={orderId}
                      onChange={(e) => setOrderId(e.target.value)}
                      placeholder="e.g. GHB-U2QTD"
                      className="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-sm font-semibold text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                    />
                  </div>
                </div>
                <div>
                  <label htmlFor="phone" className="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">
                    Phone Number
                  </label>
                  <div className="relative">
                    <svg className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                    </svg>
                    <input
                      id="phone"
                      type="tel"
                      value={phone}
                      onChange={(e) => setPhone(e.target.value)}
                      placeholder="Enter your phone number"
                      className="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-sm font-semibold text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                    />
                  </div>
                </div>
              </div>

              {error && (
                <div className="mt-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                  <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                  {error}
                </div>
              )}

              <button
                type="submit"
                disabled={loading || !orderId.trim() || !phone.trim()}
                className="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
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

        {/* Results */}
        {searched && !loading && !order && !error && (
          <div className="mx-auto max-w-xl rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <Package className="mx-auto h-12 w-12 text-slate-300" />
            <p className="mt-4 text-lg font-semibold text-slate-700">Order not found</p>
            <p className="mt-2 text-sm text-slate-500">
              Please check your Order ID and Phone number and try again.
            </p>
          </div>
        )}

        {order && (
          <div className="space-y-6">
            {/* Order Header */}
            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
              <div className="h-1.5 bg-gradient-to-r from-emerald-400 via-slate-900 to-emerald-500" />
              <div className="p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                  <div>
                    <p className="text-[10px] font-bold uppercase tracking-[0.25em] text-slate-500">Order</p>
                    <p className="mt-1 text-2xl font-extrabold tracking-tight text-slate-950">#{order.orderId}</p>
                    <p className="mt-1 flex items-center gap-1.5 text-sm text-slate-500">
                      <Calendar className="h-3.5 w-3.5" />
                      Placed on {new Date(order.createdAt).toLocaleDateString('en-US', { dateStyle: 'medium' })}
                    </p>
                  </div>
                  <div className="text-right">
                    <p className="text-[10px] font-bold uppercase tracking-[0.25em] text-slate-500">Total</p>
                    <p className="mt-1 text-2xl font-extrabold text-slate-950">{currency(order.totalPrice)}</p>
                  </div>
                </div>

                <div className="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                  <div className="rounded-xl border border-slate-100 bg-slate-50 p-3">
                    <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">Status</p>
                    <p className="mt-1 text-sm font-bold text-slate-900">{STATUS_LABELS[order.status] || order.status}</p>
                  </div>
                  <div className="rounded-xl border border-slate-100 bg-slate-50 p-3">
                    <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">Payment</p>
                    <p className="mt-1 text-sm font-bold text-slate-900">{(order.paymentMethod || 'cod').toUpperCase()}</p>
                    <p className="text-[10px] text-slate-500">{PAYMENT_STATUS_LABELS[order.paymentStatus] || order.paymentStatus}</p>
                  </div>
                  <div className="rounded-xl border border-slate-100 bg-slate-50 p-3">
                    <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">Shipping</p>
                    <p className="mt-1 text-sm font-bold text-slate-900">{order.shippingMethodName || 'Standard'}</p>
                  </div>
                  <div className="rounded-xl border border-slate-100 bg-slate-50 p-3">
                    <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">Tracking</p>
                    <p className="mt-1 text-sm font-bold text-slate-900">{order.courierTrackingNumber || 'Pending'}</p>
                  </div>
                </div>
              </div>
            </div>

            {/* Tracking Timeline */}
            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <h2 className="mb-6 text-lg font-bold text-slate-900">Order Timeline</h2>
              <div className="relative">
                {timelineFlow.map((step, index) => {
                  const isLast = index === timelineFlow.length - 1
                  const isCurrent = order.status === step
                  const historyEntry = statusHistory.find((h) => h.status === step)
                  const isCompleted = historyEntry !== undefined || index < timelineFlow.indexOf(order.status)
                  const isFuture = index > timelineFlow.indexOf(order.status) && order.status !== 'cancelled' && order.status !== 'returned' && order.status !== 'refunded'

                  return (
                    <div key={step} className="flex gap-4 pb-6 last:pb-0">
                      <div className="flex flex-col items-center">
                        <div
                          className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 transition-all ${
                            isCurrent
                              ? 'border-slate-900 bg-slate-900 text-white shadow-lg shadow-slate-900/20'
                              : isCompleted
                                ? 'border-emerald-500 bg-emerald-500 text-white'
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
                          <div className={`w-0.5 flex-1 ${isCompleted && !isFuture ? 'bg-emerald-300' : 'bg-slate-100'}`} />
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
                        {historyEntry?.note && (
                          <p className="mt-0.5 text-xs text-slate-500">{historyEntry.note}</p>
                        )}
                      </div>
                    </div>
                  )
                })}
              </div>
            </div>

            {/* Order Items */}
            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <h2 className="mb-4 text-lg font-bold text-slate-900">Order Items ({order.items?.length || 0})</h2>
              <div className="space-y-3">
                {order.items?.map((item: UserOrderItem) => (
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
                        {item.quantity} x {currency(item.price)}
                        {item.color ? ` · Color: ${item.color}` : ''}
                        {item.size ? ` · Size: ${item.size}` : ''}
                      </p>
                    </div>
                    <span className="text-sm font-bold text-slate-950">{currency(Number(item.price) * item.quantity)}</span>
                  </div>
                ))}
              </div>

              {/* Order Total */}
              <div className="mt-4 border-t border-slate-100 pt-4">
                <div className="space-y-1.5 text-sm">
                  {Number(order.subtotal) > 0 && (
                    <div className="flex justify-between">
                      <span className="text-slate-500">Subtotal</span>
                      <span className="font-medium">{currency(Number(order.subtotal) || 0)}</span>
                    </div>
                  )}
                  {Number(order.discount) > 0 && (
                    <div className="flex justify-between text-emerald-600">
                      <span>Discount</span>
                      <span className="font-medium">-{currency(Number(order.discount))}</span>
                    </div>
                  )}
                  <div className="flex justify-between">
                    <span className="text-slate-500">Shipping{order.shippingMethodName ? ` (${order.shippingMethodName})` : ''}</span>
                    <span className="font-medium">{Number(order.shippingCost) === 0 ? 'FREE' : currency(order.shippingCost)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-slate-500">Tax</span>
                    <span className="font-medium">{currency(Number(order.tax) || 0)}</span>
                  </div>
                  <div className="flex justify-between border-t border-slate-200 pt-2 text-base font-bold">
                    <span>Total</span>
                    <span>{currency(order.totalPrice)}</span>
                  </div>
                </div>
              </div>
            </div>

            {/* Invoice Section */}
            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <div className="flex flex-wrap items-center justify-between gap-4">
                <div>
                  <h2 className="text-lg font-bold text-slate-900">Invoice</h2>
                  <p className="mt-1 text-sm text-slate-500">View or download your invoice for this order</p>
                </div>
                <div className="flex gap-2">
                  <button
                    onClick={() => setShowInvoice(!showInvoice)}
                    className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                  >
                    <FileText className="h-4 w-4" />
                    {showInvoice ? 'Hide Invoice' : 'View Invoice'}
                  </button>
                  <button
                    onClick={handlePrint}
                    className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                  >
                    <Printer className="h-4 w-4" />
                    Print
                  </button>
                  <button
                    onClick={handleDownload}
                    className="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                  >
                    <Download className="h-4 w-4" />
                    Download PDF
                  </button>
                </div>
              </div>

              {showInvoice && (
                <div className="mt-6 overflow-x-auto" ref={invoiceRef}>
                  <InvoiceTemplate order={order} />
                </div>
              )}
            </div>

            {/* Customer Info (masked) */}
            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <h2 className="mb-4 text-lg font-bold text-slate-900">Shipping Details</h2>
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div className="rounded-xl border border-slate-100 bg-slate-50 p-4">
                  <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <MapPin className="h-3.5 w-3.5" />
                    Delivery Address
                  </div>
                  <p className="mt-2 text-sm font-medium text-slate-900">{order.customerName}</p>
                  <p className="mt-1 text-sm text-slate-600">{order.address}</p>
                  {(order.division || order.district || order.upazila) && (
                    <p className="mt-1 text-xs text-slate-500">
                      {[order.division, order.district, order.upazila].filter(Boolean).join(', ')}
                    </p>
                  )}
                </div>
                <div className="rounded-xl border border-slate-100 bg-slate-50 p-4">
                  <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <CreditCard className="h-3.5 w-3.5" />
                    Payment
                  </div>
                  <p className="mt-2 text-sm font-medium text-slate-900">{(order.paymentMethod || 'cod').toUpperCase()}</p>
                  <p className="mt-1 text-xs text-slate-500">
                    Status: {PAYMENT_STATUS_LABELS[order.paymentStatus] || order.paymentStatus}
                  </p>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </main>
  )
}

export default OrderTrackingPage
