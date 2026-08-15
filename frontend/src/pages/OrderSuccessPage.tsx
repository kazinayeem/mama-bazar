import { useLocation, useNavigate, Link } from 'react-router-dom'
import { useEffect, useRef } from 'react'
import { SEO } from '../components/common/SEO'
import { trackPurchase } from '../lib/pixel'
import type { Order, UserOrderItem } from '../types'
import { currency } from '../lib/format'

const PAYMENT_STATUS_LABELS: Record<string, string> = {
  pending: 'Awaiting payment',
  payment_pending: 'Awaiting payment',
  payment_verification: 'Payment under verification',
  verified: 'Payment verified',
  success: 'Paid',
  failed: 'Payment failed',
  rejected: 'Payment rejected',
  refunded: 'Refunded',
}

type SuccessState = {
  order?: Order & { items?: UserOrderItem[] }
  orderId?: string
  message?: string
}

const OrderSuccessPage = () => {
  const location = useLocation()
  const navigate = useNavigate()
  const state = (location.state || {}) as SuccessState
  const order = state.order

  useEffect(() => {
    if (!state.orderId && !state.order) {
      navigate('/shop', { replace: true })
    }
  }, [state.orderId, state.order, navigate])

  const purchaseTracked = useRef(false)
  useEffect(() => {
    if (!order || purchaseTracked.current) return
    purchaseTracked.current = true
    trackPurchase({
      value: Number(order.totalPrice),
      numItems: order.items?.reduce((sum, item) => sum + item.quantity, 0) || 0,
      contentIds: order.items?.map((item) => item.productId) || [],
      contents: order.items?.map((item) => ({ id: String(item.productId), quantity: item.quantity })) || [],
    })
  }, [order])

  if (!order) {
    return (
      <main className="mx-auto max-w-2xl px-6 py-20 text-center">
        <div className="text-6xl">✅</div>
        <h1 className="mt-4 font-headline text-3xl font-extrabold tracking-tight">Order Placed!</h1>
        <p className="mt-2 text-sm text-on-surface-variant">
          Your order <span className="font-bold">{state.orderId}</span> has been received. {state.message || ''}
        </p>
        <Link className="mt-6 inline-block border border-tertiary px-6 py-3 text-xs font-bold uppercase tracking-[0.2em] text-tertiary hover:bg-tertiary hover:text-white" to="/shop">
          Continue Shopping
        </Link>
      </main>
    )
  }

  const needsVerification = order.paymentStatus === 'payment_verification' || order.paymentStatus === 'payment_pending'
  const needsPayment = order.paymentStatus === 'payment_pending'

  return (
    <main className="mx-auto max-w-3xl px-6 py-12">
      <SEO
        title="Order Placed Successfully"
        description="Your order has been placed successfully at Mama Bazar."
        url="/order/success"
      />
      <div className="text-center">
        <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-success/10 text-4xl">🎉</div>
        <h1 className="mt-4 font-headline text-3xl font-extrabold tracking-tight">Thank You!</h1>
        <p className="mt-2 text-sm text-on-surface-variant">
          Your order <span className="font-bold text-tertiary">{order.orderId}</span> has been placed successfully.
        </p>
      </div>

      {needsPayment && (
        <div className="mt-6 rounded-lg border border-warning/40 bg-warning/10 p-4 text-sm">
          <p className="font-semibold">Payment pending</p>
          <p className="mt-1">
            Please complete your payment for {order.paymentMethod.toUpperCase()} using the instructions shown at checkout, then our team will verify
            your payment to confirm the order.
          </p>
        </div>
      )}

      {needsVerification && (
        <div className="mt-6 rounded-lg border border-tertiary/30 bg-tertiary/10 p-4 text-sm">
          <p className="font-semibold">{PAYMENT_STATUS_LABELS[order.paymentStatus] || order.paymentStatus}</p>
          <p className="mt-1">Once verified, you will receive confirmation. Track your order anytime below.</p>
        </div>
      )}

      <div className="mt-8 rounded-2xl border border-outline-variant/20 bg-white p-6 shadow-panel">
        <h2 className="font-headline text-lg font-bold">Order Summary</h2>

        <div className="mt-4 space-y-3">
          {order.items?.map((item) => (
            <div className="flex items-center gap-3" key={item.id}>
              {item.product?.image ? (
                <img alt="" className="h-12 w-12 rounded-lg object-cover" src={item.product.image} />
              ) : (
                <div className="h-12 w-12 rounded-lg bg-muted" />
              )}
              <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-semibold">{item.product?.title || `Product #${item.productId}`}</p>
                <p className="text-xs text-on-surface-variant">
                  {item.quantity} × {currency(item.price)}
                </p>
              </div>
              <span className="text-sm font-semibold">{currency(Number(item.price) * item.quantity)}</span>
            </div>
          ))}
        </div>

        <div className="mt-5 space-y-2 border-t border-outline-variant/20 pt-4 text-sm">
          {Number(order.subtotal) > 0 && (
            <div className="flex justify-between">
              <span className="text-on-surface-variant">Subtotal</span>
              <span>{currency(Number(order.subtotal) || 0)}</span>
            </div>
          )}
          {Number(order.discount) > 0 && (
            <div className="flex justify-between text-success">
              <span>Discount</span>
              <span>-{currency(Number(order.discount) || 0)}</span>
            </div>
          )}
          <div className="flex justify-between">
            <span className="text-on-surface-variant">Shipping{order.shippingMethodName ? ` (${order.shippingMethodName})` : ''}</span>
            <span>{Number(order.shippingCost) === 0 ? 'FREE' : currency(order.shippingCost)}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-on-surface-variant">Tax</span>
            <span>{currency(Number(order.tax) || 0)}</span>
          </div>
          <div className="flex items-center justify-between border-t border-outline-variant/20 pt-3">
            <span className="font-headline text-lg font-bold">Total</span>
            <span className="font-headline text-xl font-extrabold">{currency(order.totalPrice)}</span>
          </div>
        </div>
      </div>

      <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div className="rounded-xl border border-outline-variant/20 p-4 text-sm">
          <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-on-surface-variant">Payment</p>
          <p className="mt-1 font-semibold">{order.paymentMethod.toUpperCase()}</p>
          <p className="text-xs text-on-surface-variant">{PAYMENT_STATUS_LABELS[order.paymentStatus] || order.paymentStatus}</p>
        </div>
        <div className="rounded-xl border border-outline-variant/20 p-4 text-sm">
          <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-on-surface-variant">Delivery</p>
          <p className="mt-1 font-semibold">{order.shippingMethodName || 'Standard'}</p>
          <p className="text-xs text-on-surface-variant">
            {[order.division, order.district, order.upazila, order.area].filter(Boolean).join(', ') || order.address}
          </p>
        </div>
      </div>

      <div className="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
        <Link className="border border-tertiary px-6 py-3 text-xs font-bold uppercase tracking-[0.2em] text-tertiary hover:bg-tertiary hover:text-white" to="/shop">
          Continue Shopping
        </Link>
        <Link className="bg-tertiary px-6 py-3 text-xs font-bold uppercase tracking-[0.2em] text-white hover:brightness-110" to="/dashboard/orders">
          Track My Order
        </Link>
      </div>
    </main>
  )
}

export default OrderSuccessPage
