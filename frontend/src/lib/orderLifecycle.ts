import type { OrderStatus, PaymentMethod, PaymentStatus } from '../types'

export const ORDER_STATUS_FLOW: OrderStatus[] = [
  'pending',
  'payment_pending',
  'payment_verification',
  'confirmed',
  'processing',
  'packed',
  'shipped',
  'out_for_delivery',
  'delivered',
  'returned',
  'cancelled',
  'refunded',
]

export const ORDER_STATUS_LABELS: Record<OrderStatus, string> = {
  pending: 'Pending',
  payment_pending: 'Payment Pending',
  payment_verification: 'Payment Verification',
  confirmed: 'Confirmed',
  processing: 'Processing',
  packed: 'Packed',
  shipped: 'Shipped',
  out_for_delivery: 'Out for delivery',
  delivered: 'Delivered',
  returned: 'Returned',
  cancelled: 'Cancelled',
  refunded: 'Refunded',
}

export const PAYMENT_STATUS_LABELS: Record<PaymentStatus, string> = {
  pending: 'Pending',
  payment_pending: 'Payment pending',
  payment_verification: 'Under verification',
  verified: 'Verified',
  success: 'Success',
  failed: 'Failed',
  rejected: 'Rejected',
  refunded: 'Refunded',
}

export const ORDER_STATUS_TRANSITIONS: Record<OrderStatus, OrderStatus[]> = {
  pending: ['payment_pending', 'confirmed', 'cancelled'],
  payment_pending: ['payment_verification', 'confirmed', 'cancelled'],
  payment_verification: ['confirmed', 'cancelled'],
  confirmed: ['processing', 'cancelled'],
  processing: ['packed', 'cancelled'],
  packed: ['shipped', 'cancelled'],
  shipped: ['out_for_delivery', 'cancelled'],
  out_for_delivery: ['delivered', 'returned', 'cancelled'],
  delivered: ['returned', 'refunded'],
  returned: ['refunded', 'cancelled'],
  cancelled: [],
  refunded: [],
}

const COD_PAYMENT_METHODS = new Set<PaymentMethod>(['cod'])

export type OrderLifecycleTarget = {
  status: OrderStatus
  paymentMethod: PaymentMethod
  paymentStatus: PaymentStatus
}

export const isCODOrder = (paymentMethod: PaymentMethod) => COD_PAYMENT_METHODS.has(paymentMethod)

export const shouldShowPaymentVerificationPanel = (order: OrderLifecycleTarget) =>
  !isCODOrder(order.paymentMethod) && (order.status === 'payment_verification' || order.paymentStatus === 'payment_verification')

export const getAllowedNextStatuses = (order: OrderLifecycleTarget) => {
  const next = ORDER_STATUS_TRANSITIONS[order.status] || []
  if (order.status === 'payment_pending' && isCODOrder(order.paymentMethod)) {
    return next.filter((status) => status !== 'payment_verification')
  }
  return next
}

export const getBlockedStatuses = (order: OrderLifecycleTarget) => {
  const allowed = new Set(getAllowedNextStatuses(order))
  return ORDER_STATUS_FLOW.filter((status) => status !== order.status && !allowed.has(status))
}
